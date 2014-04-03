<?php
//we already connected to WP

class Ecwid_SS_Handler {

	function __construct() {
		global $ecwid_ss_optionname , $ecwid_ss_domain;

		$this->domain = $ecwid_ss_domain;
		$options = get_option( $ecwid_ss_optionname );
		$this->options = $options;

		//no options?
		if ( empty( $options['username'] ) || empty( $options['password'] ) )
			$this->die_log( __( "Please, setup username & password to access xml", $this->domain ) );

		$login_ok = false;

		if ( !empty ( $_GET['auth_key'] ) )
		{
			if ( $this->options['auth_key']  != $_GET['auth_key'] )
				$this->die_log( __( 'Wrong auth_key passed.', $this->domain ) );
			$login_ok = true;
		}

		if ( !$login_ok )
		{
			if( isset( $_SERVER['HTTP_SS_AUTH_USER'] ) ) 
				$_SERVER['PHP_AUTH_USER'] = $_SERVER['HTTP_SS_AUTH_USER'];
			if( isset( $_SERVER['HTTP_SS_AUTH_PW'] ) ) 
				$_SERVER['PHP_AUTH_PW'] = $_SERVER['HTTP_SS_AUTH_PW'];

			//401 check
			if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
				header( "WWW-Authenticate: Basic realm=\"XML Api for Ecwid\"" );
				header( "HTTP/1.0 401 Unauthorized" );
				_e( "User/pass are required!", $this->domain );
				exit;
			} 
			if ( empty( $_SERVER['PHP_AUTH_USER'] ) || empty( $_SERVER['PHP_AUTH_PW'] ) )
				 $this->die_log( __( "Basic HTTP Authentication is required. Please, add  http://username:password@ to current url.", $this->domain ) );
			//bad user/pass?
			if ( $_SERVER['PHP_AUTH_USER'] != $options['username'] || $_SERVER['PHP_AUTH_PW'] != $options['password'] )
				$this->die_log( __( "Basic HTTP Authentication failed. Please, update username/password in 'Custom Store Setup' at ShipStation site", $this->domain ) );
		}

		if ( !empty ( $options['log_requests'] ) )
			$this->write_log( "input parameters->" . http_build_query( $_GET ), @$_GET['action'] == 'export' );



		//action missed or wrong?
		if ( !isset( $_GET['action'] ) ) 
			$this->die_log( __( "Missing 'action' parameter", $this->domain ) );
		if ( !in_array( $_GET['action'], array( 'export', 'shipnotify' ) ) )
			die_log ( "Incorrect 'action' parameter" );

		if ( $_GET['action'] == 'export' )
			$this->export_orders();

		if ( $_GET['action'] == 'shipnotify' )
			$this->update_order();
	}

	// we must  return XML as reply
	function export_orders() {

		$options = $this->options;

		$this->validate_input( array( "start_date", "end_date" ) );

		// some extra information
		$reply = file_get_contents( "http://app.ecwid.com/api/v1/{$options['store_id']}/profile" );
		$js = json_decode( $reply );
		if ( !$js )
			$this->die_log( __( "Can't connect Ecwid API for #{$options['store_id']}", $this->domain ) );

		//weight units Ecwid:CARAT, GRAM, OUNCE, POUND, KILOGRAM -> SS: Pounds, Ounces, Grams
		$wu =  $js->weightUnit;
		if ( $wu == "CARAT" ) {
			$wu = "Grams";
			$wu_multi = 5;
		}elseif ( $wu == "GRAM" ){
			$wu = "Grams"; 
			$wu_multi = 1;
		}elseif ( $wu == "OUNCE" ){
			$wu = "Ounces";
			$wu_multi = 1;
		}elseif ( $wu == "POUND" ){
			$wu = "Pounds";
			$wu_multi = 1;
		}elseif ( $wu == "KILOGRAM" ){
			$wu = "Grams";
			$wu_multi = 1000;
		}

		// format MM/dd/yyyy HH:mm GMT to timestamp for store timezone
		$start_time = strtotime( $_GET['start_date'] ) + $options['store_tz'] * 3600;
		$end_time = strtotime( $_GET['end_date'] ) + $options['store_tz'] * 3600;

		$xml = new SimpleXMLElement( "<Orders></Orders>" );

		$page_size = 200;
		$exported = 0;
		foreach ( $options['export_rules'] as $rule ) {
			$offset = 0;
			$orders = $this->get_orders( $rule, $start_time, $end_time, $offset, $next );
			$exported += $this->output_orders( $xml, $orders, $wu, $wu_multi );
			while( $next ) {
				$offset += $page_size;
				$orders = $this->get_orders( $rule, $start_time, $end_time, $offset, $next );
				$exported += $this->output_orders( $xml, $orders, $wu, $wu_multi );
			}
		}
		
		Header( 'Content-type: text/xml' );
		//format it!
		$dom = dom_import_simplexml( $xml )->ownerDocument;
		$dom->formatOutput = true;
		echo $dom->saveXML();

		$this->write_log( "$exported exported" , true );
	}

	function get_orders( $rule, $start_time, $end_time, $offset, &$next ) {

		$options = $this->options;

		$url = "https://app.ecwid.com/api/v1/{$options['store_id']}/orders?&secure_auth_key={$options['order_api_key']}";
		$url .= "&from_update_date=$start_time&to_update_date=$end_time";
		$url .= "&statuses={$rule['payment']},{$rule['fulfillment']}";
		$url .= "&limit=200&offset=$offset"; 


		$reply = file_get_contents( $url );
		if ( !$reply )
			$this->die_log( __( "Can't get orders via app.ecwid.com", $this->domain ) );

		$js = json_decode( $reply );
		if ( !$js )
			$this->die_log( __( "Can't parse reply from app.ecwid.com", $this->domain ) );

		$next = isset( $js->nextUrl );
		return $js->orders;
	}

	function output_orders( &$xml, $orders, $wu, $wu_multi ) {
		$options = $this->options;
		foreach ( $orders as $order ) {

			$o=$xml->addChild( 'Order' );
			$o->OrderNumber = $order->number;
			$o->OrderDate = date( "m/d/Y H:i", strtotime( $order->created ) - $options['store_tz'] * 3600 );
			$o->OrderStatus = $order->paymentStatus . "|" . $order->fulfillmentStatus;
			$o->LastModified = date( "m/d/Y H:i", strtotime( $order->lastChangeDate ) - $options['store_tz'] * 3600 );
			$o->ShippingMethod = $order->shippingMethod;
			$o->OrderTotal = $order->totalCost;
			$o->TaxAmount = $order->taxCost;
			$o->ShippingAmount = $order->shippingCost;
			$o->CustomerNotes = $order->orderComments; 
			$o->InternalNotes = "";

			$c = $o->addChild( "Customer" );
			$c->CustomerCode = isset( $order->customerId ) ? $order->customerId : $order->customerEmail;

			$bill = $c->addChild( "BillTo" );
			$bill->Name = $order->billingPerson->name;
			$bill->Company = $order->billingPerson->companyName;
			$bill->Phone = $order->billingPerson->phone;
			$bill->Email = $order->customerEmail;

			$ship = $c->addChild( "ShipTo" );
			$ship->Name = $order->shippingPerson->name;
			$ship->Company = $order->shippingPerson->companyName;
			$ship->Address1 = $order->shippingPerson->street;
			$ship->Address2 = "";
			$ship->City = $order->shippingPerson->city;
			$ship->State = $order->shippingPerson->stateOrProvinceCode;
			$ship->PostalCode = $order->shippingPerson->postalCode;
			$ship->Country = $order->shippingPerson->countryCode;
			$ship->Phone = $order->shippingPerson->phone;

			$items = $o->addChild( "Items" );
			foreach ( $order->items as $i ) {
				$item = $items->addChild( "Item" );

				$item->SKU = $i->sku;
				$item->Name = $i->name;
				$item->Weight = round( $i->weight * $wu_multi, 2 );
				$item->WeightUnits = $wu;
				$item->Quantity = $i->quantity;
				$item->UnitPrice = $i->price;

				if ( !empty( $i->options ) ) {
					$opts = $item->addChild( "Options" );
					foreach ( $i->options as $meta ) {
						if ( !$meta->type == "FILE" ) 
							continue; //skip file
						if ( !$meta->value ) 
							continue;
				 
						$opt = $opts->addChild( "Option" );
						$opt->Name = $meta->name;
						$opt->Value = $meta->value;
					}
				}
			}// end item
		}
		return count( $orders );
	}

	//SS passed order_number, carrier(USPS, UPS, FedEx),service,tracking_number
	function update_order() {

		$options = $this->options;

		$this->validate_input( array( "order_number", "carrier", "service", "tracking_number" ) );
		$orderid = intval( $_GET['order_number'] );

		$url = "https://app.ecwid.com/api/v1/{$options['store_id']}/orders";
		$data = "secure_auth_key={$options['order_api_key']}&order=$orderid&new_shipping_tracking_code=" . urlencode( $_GET['tracking_number'] );
		
		//POST using php functions
		$params = array( 'http' => array(
			'header' => 'Content-type: application/x-www-form-urlencoded', 
			'method' => 'POST', 
			'content' => $data ) 
		);
		$context = stream_context_create( $params );
		$stream = fopen( $url, 'rb', false, $context );
		if ( !$stream )
			$this->die_log( __( "Can't update order via app.ecwid.com", $this->domain ) );

		$response = stream_get_contents( $stream );
		fclose( $stream );

		// json object 
		if ( !preg_match( '#"count"\s*:\s*1#s', $response ) )
			$this->die_log( __( "Can't update order - wrong reply from api", $this->domain ) );

		$this->die_log( sprintf( __( "Order %s updated", $this->domain ) , $orderid ) );
	}



	//common functions
	function validate_input( $req_flds ) {
		$missed = array();
		foreach ( $req_flds as $f ) {
			if ( empty( $_GET[ $f ] ) )
				$missed[] = $f;
		}

		if ( $missed )
			$this->die_log( __( "Missed parameters:", $this->domain ) . join( ",", $missed ) );
	}

	function write_log( $msg, $noEcho = false ) {
		
		$msg = date( "Y-m-d H:i:s" ) . "|{$_SERVER['REMOTE_ADDR']}|$msg\n";

		error_log( $msg, 3, dirname( __FILE__ ) . "/handler.txt" );

		if ( $noEcho ) 
			return;

		echo $msg . "<br>";
		flush();
	}

	function die_log( $msg ) {
		$this->write_log( $msg );
		die();
	}
}
?>