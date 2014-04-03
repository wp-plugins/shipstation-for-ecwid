=== ShipStation for Ecwid  ===
Contributors: rtddev
Donate link: 
Tags: ecwid, shipstation, shipping, fulfillment
Requires at least: 3.5
Tested up to: 3.8.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The plugin helps store owners significantly speed up the shipping process by offering an easy way to seamlessly integrate an Ecwid Store with ShipStation.

== Description ==

This plugin provides a bridge between your Ecwid Store and ShipStation. Your Ecwid store does not need to be running within a WordPress site, however, you do need a WordPress installation (somewhere) to run this plugin. Possible options include: a subdomain or a sub-directory on your existing server, or a WordPress install on a totally separate server.

ShipStation is a web-based shipping solution that streamlines the order fulfillment process for online retailers. With real-time integrations into popular marketplaces and shopping cart platforms, ShipStation handles everything from order import and batch label creation to customer communication. Advanced customization features allow ShipStation to fit businesses with any number of users or locations.

== Installation ==

1. Download/Save Plugin Extension Zip file to your local computer
1. Within WP Admin, Go to Plugins > Add New > Upload
1. Select the plugin zip file from your local computer
1. Click Install Now Button
1. Once install is complete, Click "Activate Plugin" link

== Frequently asked questions ==

**Can This Plugin Be Used With the Free Version of Ecwid?**
No. This plugin leverages the Ecwid API, which requires a paid subscription at Ecwid.com

**Do I need to run my Ecwid Store on WordPress?**
No. Wordpress is only needed to run the plugin. It does not have to be used to run your main site, however, you do need to have at least one WordPress installation running where this plugin can be installed and configured.


== Screenshots ==

1. ShipStation for Ecwid Configuration screen

== Changelog ==

= 2.0 =
* Initial public release.
* Migrated to auth_key implementation
* Updated Configuration Screen

= 1.5 =
* CGI/FAST CGI authentication fix.
* Added TestAuthKey functionality. Bypasses regular authentication for testing export.

= 1.0 =
* First release

== Upgrade notice ==

None

== Setup, Configuration, and Testing ==

Once plugin is activated, go to **Tools > ShipStation for Ecwid** 
Fill out Settings Section Username / Pwd / URL: 
This is the information you'll put inside of ShipStation when configuring an "Ecwid Store" - define values that are secure 

**Ecwid Store Id:** 
Grab your Ecwid store ID and put it in this field 

**Order API Key:**
Grab your Ecwid Order API Key and put it in this field 

**Store Time:**
Set your store time zone Payment/Fulfillment 

**Config:**
Create the combination of Payment/Fulfillment statues that you want to expose to ShipStation. 

Accepted/New is the default since these are orders in Ecwid that require fulfillment. If you have other combinations that require fulfillment, create a Rule for it

SAVE Changes, and you're ready to GO! Just go into your ShipStation Account and setup a new Ecwid Store.


== Configure Ecwid Store inside ShipStation  ==

**Get Endpoint & Access Info**
Write down the following values from ShipStation for Eciwd screen
1. Username & Password values
1. Authentication Key value
1. URL to Custom XML page value

**Create account at ShipStation.com**
1. Login to your account
1. Visit Settings Page
1. Click Stores link. Click "Add a New Store.." button
Choose 'Ecwid' button from the available 'Add Store' list
Setup Store at ShipStation

**Fill out the available fields as follows:**
1. Username > Enter Username from Settings Section
1. Password > Enter Password from Settings Section
1. Authentication Key > Enter Authentication Key from Settings Section
1. URL to Custom XML Page > Enter URL from Settings  Section

Follow the prompts on the screen to complete the setup.