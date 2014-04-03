<?php
/*
Plugin Name: ShipStation for Ecwid Bridge
Plugin URI: mailto:dev@ribbedtee.com
Description: Provides ShipStation Support on Ecwid Stores. Acts as a bridge between Ecwid and ShipStation.
Version: 2.1
Author: RTD LLC Development
Author URI: http://ribbedtee.com/ 
*/

$ecwid_ss_optionname = 'ecwid_ss';
$ecwid_ss_domain = 'ecwid_ss';
load_plugin_textdomain( $ecwid_ss_domain, false, basename( dirname( __FILE__ ) ) . '/languages' );

// add the the options to the admin menu
add_action( 'admin_menu', 'ecwid_ss_menu' );

// to keep access via hook
add_action( "parse_request", "ecwid_ss_parse_request" );
function ecwid_ss_parse_request( &$wp ) {
	if ( $wp->request == "ecwid-shipstation-api" ) {
		include_once dirname( __FILE__ ) . "/handler.php";
		$handler = new Ecwid_SS_Handler();
		die();
	}
}
//end init

function ecwid_ss_menu()
{
	// adds to plugins Menu
	add_management_page( 'ShipStation for Ecwid Bridge', 'ShipStation for Ecwid', 'manage_options', 'menuecwid_ss', 'ecwid_ss_configure' );
}

$ecwid_ss_tz = array(
	"-12.0" => "(GMT -12:00) Eniwetok, Kwajalein",
	"-11.0" => "(GMT -11:00) Midway Island, Samoa",
	"-10.0" => "(GMT -10:00) Hawaii",
	"-9.0" => "(GMT -9:00) Alaska",
	"-8.0" => "(GMT -8:00) Pacific Time (US &amp; Canada)",
	"-7.0" => "(GMT -7:00) Mountain Time (US &amp; Canada)",
	"-6.0" => "(GMT -6:00) Central Time (US &amp; Canada), Mexico City",
	"-5.0" => "(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima",
	"-4.0" => "(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz",
	"-3.5" => "(GMT -3:30) Newfoundland",
	"-3.0" => "(GMT -3:00) Brazil, Buenos Aires, Georgetown",
	"-2.0" => "(GMT -2:00) Mid-Atlantic",
	"-1.0" => "(GMT -1:00 hour) Azores, Cape Verde Islands",
	"0.0" => "(GMT) Western Europe Time, London, Lisbon, Casablanca",
	"1.0" => "(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris",
	"2.0" => "(GMT +2:00) Kaliningrad, South Africa",
	"3.0" => "(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg",
	"3.5" => "(GMT +3:30) Tehran",
	"4.0" => "(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi",
	"4.5" => "(GMT +4:30) Kabul",
	"5.0" => "(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent",
	"5.5" => "(GMT +5:30) Bombay, Calcutta, Madras, New Delhi",
	"5.75" => "(GMT +5:45) Kathmandu",
	"6.0" => "(GMT +6:00) Almaty, Dhaka, Colombo",
	"7.0" => "(GMT +7:00) Bangkok, Hanoi, Jakarta",
	"8.0" => "(GMT +8:00) Beijing, Perth, Singapore, Hong Kong",
	"9.0" => "(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk",
	"9.5" => "(GMT +9:30) Adelaide, Darwin",
	"10.0" => "(GMT +10:00) Eastern Australia, Guam, Vladivostok",
	"11.0" => "(GMT +11:00) Magadan, Solomon Islands, New Caledonia",
	"12.0" => "(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka"
);

function ecwid_ss_configure() {
	global $ecwid_ss_optionname, $ecwid_ss_tz, $ecwid_ss_domain;

	$domain = $ecwid_ss_domain;

	if ( !current_user_can( 'manage_options' ) ) {
		die( __( 'Access Denied', $domain ) );
	}

	$options = get_option( $ecwid_ss_optionname );
	if ( $options == null )
		$options = array();

	if ( !empty( $_POST ) ) {
		$export_rules = array();
		if ( isset( $_POST['rule_payment'] ) ) {
			foreach ( $_POST['rule_payment'] as $id => $value ) {
				$export_rules[ $id ] = array( 'payment' => $value, 'fulfillment' => $_POST['rule_fulfillment'][ $id ] );
			}
		}
		if ( @$_POST['AddRule'] ) {
			$export_rules[] = array( 'payment' => $_POST['addPayment'], 'fulfillment' => $_POST['addFulfillment'] );
			//unique rules
			$export_rules = array_map( 'unserialize', array_unique( array_map( 'serialize', $export_rules ) ) );
		}
		$_POST['options']['export_rules'] = $export_rules;

		update_option( $ecwid_ss_optionname, $_POST['options'] );
		$msg = __( 'Options Updated', $domain );
		echo "<script>alert('$msg')</script>";
		die( "<meta http-equiv='refresh' content='0,$_SERVER[REQUEST_URI]'>" );
	}	

	if ( isset( $_GET['remove_rule'] ) ) {  //remove rule?
		$idx = intval( $_GET['remove_rule'] );
		unset( $options['export_rules'][ $idx ] );
		$options['export_rules'] = array_values( $options['export_rules'] );

		update_option( $ecwid_ss_optionname, $options );
		$msg = __( 'Rule Removed', $ecwid_ss_domain );
		echo "<script>alert('$msg')</script>";
		$url = str_replace( "&remove_rule={$_GET['remove_rule']}", "", $_SERVER['REQUEST_URI'] );
		die( "<meta http-equiv='refresh' content='0,$url'>" );
	}

	$url = site_url();
	if ( substr( $url, -1 ) != "/" )
		$url .= "/";
	$url .= "ecwid-shipstation-api";

	$folder = basename( dirname( __FILE__ ) );
	$url_log = site_url() . "/wp-content/plugins/$folder/handler.txt";


	$username = !empty( $options['username'] ) ? $options['username'] : "";
	$password = !empty( $options['password'] ) ? $options['password'] : "";
	$store_id = !empty( $options['store_id'] ) ? $options['store_id'] : "";
	$store_tz = !empty( $options['store_tz'] ) ? $options['store_tz'] : "";
	$order_api_key = !empty( $options['order_api_key'] ) ? $options['order_api_key'] : "";
	$log_requests = !empty( $options['log_requests'] ) ? $options['log_requests'] : "";
	$export_rules = !empty( $options['export_rules'] ) ? $options['export_rules'] : array();
	$auth_key = !empty( $options['auth_key'] ) ? $options['auth_key'] : "";
	
	// Ecwid references
	$payment_status = array( "ACCEPTED", "DECLINED", "CANCELLED", "QUEUED", "CHARGEABLE", "INCOMPLETE" );	
	$fulfillment_status = array( "NEW", "PROCESSING", "SHIPPED", "DELIVERED", "WILL_NOT_DELIVER" );
	// html form bellow
?>
<div class="wrap">
	<h2><?php _e( 'ShipStation for Ecwid Bridge', $domain ) ?></h2>
	<p><?php _e( 'Plugin Features', $domain ) ?>:<br>
	<?php _e( '1) Exports orders ready for shipment to ShipStation. Multiple Payment/Fulfillment statuses may be chosen to export to ShipStation', $domain ) ?><br>
	<?php _e( '2) Updates orders inside Ecwid with shipping and tracking information.', $domain ) ?><br>
	</p>
	<form method="post" action="">
		<input type="hidden" name="action" value="update" />
		<?php wp_nonce_field( 'update-options' ); ?>

		<fieldset style="border:thin black solid;padding:5px;">
			<legend style="font-weight:bold"><?php _e( 'Settings', $domain ) ?></legend>
			<b><font color=red><?php _e( 'Enter same values in ShipStation -> Settings -> Stores -> Add New Store -> Ecwid Store', $domain ) ?></font></b><br>
			<p><strong><?php _e( 'Username', $domain ) ?>: </strong><input  name="options[username]" size=20 type="text" value="<?php echo $username; ?>" />
			<strong><?php _e( 'Password', $domain ) ?>: </strong><input name="options[password]" size=20 type="text" value="<?php echo $password; ?>" /> <br>
			<strong><?php _e( 'Url to custom XML page', $domain ) ?>:</strong> <input size=80 type="text" readonly value="<?php echo $url; ?>" />  Set Permalinks in Settings > Permalinks. Do NOT use Default
			<br><br>
			<input type=hidden name=options[log_requests] value="0">
			<input type=checkbox name=options[log_requests] <?php if ( $log_requests ) echo "checked"; ?> value="1">
			<strong><?php _e( 'Log requests', $domain ) ?></strong>
			<a href="<?php echo $url_log; ?>" target=_blank>View log</a><br>
			<br>
			<b>Alternate Authentication</b><br>
			<font color=red>(<?php _e( 'For use on webservers which run PHP in CGI mode. Add "?auth_key=value" to test url', $domain ) ?>)</font><br>
			<b><?php _e( 'Authentication Key', $domain ) ?></b>:
				<input name="options[auth_key]" size=30 type="text" value="<?php echo $auth_key; ?>" />
				<i><font color=gray>Enter 'Auth Key' value from the ShipStation Ecwid Setup screen within ShipStation.com. </font></i>
			<br>
			<hr>
			<b><font color=blue><?php _e( 'Ecwid', $domain ) ?></font></b> <strong><?php _e( 'store_id', $domain ) ?>: </strong><input  name="options[store_id]" size=20 type="text" value="<?php echo $store_id; ?>" />
			<strong><?php _e( 'Order API Key', $domain ) ?>: </strong><input name="options[order_api_key]" size=20 type="text" value="<?php echo $order_api_key; ?>" />
			<br>
			<?php _e( 'Store Timezone', $domain ) ?>:
			<select name=options[store_tz]>
			<?php foreach ( $ecwid_ss_tz as $val => $name ) {
				$sel = ( $val == $store_tz ) ? "selected" : "";
			?>
				<option value="<?php echo $val; ?>" <?php echo $sel; ?> ><?php echo $name; ?></option>
			<?php } ?>
			</select>
		</fieldset>
		<br>
		<fieldset style="border:thin black solid;padding:5px;">
			<legend style="font-weight:bold"><?php _e( 'Payment/FulFillment Statuses to expose to ShipStation', $domain ) ?></legend>

			<table border=0 width="100%">
				<tr>
					<td>&nbsp;</td>
					<td><?php _e( 'Payment Status', $domain ) ?></td>
					<td><?php _e( 'Fulfillment Status', $domain ) ?></td>
					<td>&nbsp;</td>
				</tr>
				<?php foreach ( $export_rules as $id => $rule ) { ?>
				<tr>
					<td>#&nbsp; <?php echo $id + 1;?> </td>
					<td>
						<select name=rule_payment[<?php echo $id; ?>]>
						<?php
						foreach ( $payment_status as $status ) {
							$sel = ( $rule['payment'] == $status ) ? "selected" : "";
						?>
							<option value="<?php echo $status;?>" <?php echo $sel;?> > <?php echo $status;?> </option>
						<?php
						}
						?>
						</select>
					</td>
					<td>
						<select name=rule_fulfillment[<?php echo $id; ?>]>
						<?php
						foreach ( $fulfillment_status as $status ) {
							$sel = ( $rule['fulfillment'] == $status ) ? "selected" : ""; ?>
							<option value="<?php echo $status; ?>" <?php echo $sel; ?> > <?php echo $status; ?> </option>
						<?php
						}
						?>
						</select>
					</td>
					<td>
						<input type="submit" name="RemoveRule" class="button-primary" value="<?php _e( 'Remove Rule', $domain ) ?>" onclick="location.href='<?php echo $_SERVER['REQUEST_URI'] . '&remove_rule=' . $id; ?>'; return false;"/>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<td>&nbsp;</td>
					<td>
						<select name=addPayment>
						<?php foreach ( $payment_status as $status ) {  ?>
							<option value="<?php echo $status; ?>" > <?php echo $status;?> </option>
						<?php } ?>
						</select>
					</td>
					<td>
						<select name=addFulfillment>
						<?php foreach ( $fulfillment_status as $status ) {  ?>
							<option value="<?php echo $status; ?>" > <?php echo $status; ?> </option>
						<?php } ?>
						</select>
					</td>
					<td>
						<input type="submit" name="AddRule" class="button-primary" value="<?php _e( 'Add Rule', $domain ) ?>" />
					</td>
				</tr>
			</table>
			<br>
		</fieldset>
		<br>
		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php _e( 'Save Changes', $domain ) ?>" />
		</p>
	</form>
<?php
}
?>