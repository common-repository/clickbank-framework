<?php
/**
 * Plugin Name: Clickbank Framework
 * Plugin URI: http://onemanonelaptop.com/docs/clickbank-framework
 * Description: Clickbank Integration Framework
 * Version: 0.0.2
 * Author: Rob Holmes
 * Author URI: http://onemanonelaptop.com/
 */

/*  Copyright 2011 Rob Holmes ( email: rob@onemanonelaptop.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Include the plugin framework api 
require_once( 'framework/framework.php' );

// Extend the framework and make a plugin
class wpClickbankFramework extends PluginFramework {
	
	// Force singelton
	static $instance = false;
	public static function getInstance() {
		return (self::$instance ? self::$instance : self::$instance = new self);
	} // function
		
	// Called at the start of the constructor
	public function plugin_construction() {	
		// Give the options page a title
		$this->options_page_title = "Clickbank Options";
	
		// The settings menu link text
		$this->options_page_name = "Clickbank Framework";
		$this->options_name = "clickbank-framework-options";
		$this->options_group = "clickbank-framework";
		
		// The slug of the plugin
		$this->slug = "clickbank-framework";

		// Definte the plugin defaults
		$this->defaults = array ();

		// Actions & Filters
		// Assign the query variables and the handlers
		add_action( 'template_redirect', array( &$this, 'receive_clickbank_ipn' ));
		add_filter( 'query_vars', array( &$this, 'clickbank_listener' ));
		
	} // function
	
	// Section callback 
	function clickbank_settings_section_callback() { 
		return 'Your Clickbank Instant Payment Notification URL is <span style="color:red;">' . site_url() . '?clickbank=IPN</span>';
	} // function
	
	// Section callback 
	function clickbank_settings_actions_callback() { 
		return '<table border="0"><tbody><tr><td style="width:200px;"><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td>clickbank-sale</td><td>The purchase of a standard product or the initial purchase of recurring billing product.</td></tr><tr><td>clickbank-bill</td><td>A rebill for a recurring billing product.</td></tr><tr><td>clickbank-rfnd</td><td>The refunding of a standard or recurring billing product. Recurring billing products that are refunded also result in a "cancel-rebill" action.</td></tr><tr><td>clickbank-cgbk</td><td>A chargeback for a standard or recurring product.</td></tr><tr><td>clickbank-insf</td><td>An eCheck chargeback for a standard or recurring product.</td></tr><tr><td>clickbank-cancel-rebill</td><td>The cancellation of a recurring billing product. Recurring billing products that are canceled do not result in any other action.</td></tr><tr><td>clickbank-uncancel-rebill</td><td>Reversing the cancellation of a recurring billing product.</td></tr><tr><td>clickbank-test</td><td>Triggered by using the test link on the site page.</td></tr></tbody></table>';
	} // function
	
	// Define the options page meta boxes
	function plugin_define_options_meta_boxes() {	
		$this->register_options_metabox('admin-section-general','Clickbank IPN Settings',$this->clickbank_settings_section_callback());
		$this->register_options_metabox('admin-section-actions','Transaction Hooks',$this->clickbank_settings_actions_callback());
	
		$this->register_options_field( array ('title'=>'Secret Key',	'id' => 'key', 'type'=>'text', 'section'=>'admin-section-general', 'description' => '') );
		$this->register_options_field( array ('title'=>'Debug Email Address',	'id' => 'email', 'type'=>'text', 'section'=>'admin-section-general', 'description' => '') );
		$this->register_options_field( array ('title'=>'Enable Debug Mode',	'id' => 'debug', 'type'=>'checkbox', 'section'=>'admin-section-general', 'description' => '') );	
	} // function

	
	function plugin_define_post_meta_boxes() { } // end function
	
	// Setup the query variables for the clickbank IPN
	public function clickbank_listener($public_query_vars) {
		$public_query_vars[] = 'clickbank';
		return $public_query_vars;
	} // function
	
	// Setup the lister for the clickbank IPN and do stuff when it happens
	public function receive_clickbank_ipn() {
		
		// Check that the query var is set and is the correct value.
		if (get_query_var( 'clickbank' ) == 'IPN') {
			$_POST = stripslashes_deep($_POST);
			
			// Try to validate the response to make sure it's from clickbank
		
			if ($this->clickbank_ipn_verification($_POST)) {
				$this->process_clickbank_message();
			}
			exit;
		}
	} // function
	
	// Verify the IPN - http://www.clickbank.com/help/affiliate-help/affiliate-tools/instant-notification-service/
	function clickbank_ipn_verification($postdata) {	
	   $secretKey=$this->options['key'];
		$pop = "";
		$ipnFields = array();
		foreach ($_POST as $key => $value) {
			if($key == "cverify") {
				continue;
			}
			$ipnFields[] = $key;
		}
		sort($ipnFields);
		foreach ($ipnFields as $field) {
					// if Magic Quotes are enabled $_POST[$field] will need to be
			// un-escaped before being appended to $pop
			$pop = $pop . $_POST[$field] . "|";
		}
		$pop = $pop . $secretKey;
		
		$calcedVerify = sha1(mb_convert_encoding($pop, "UTF-8"));
		$calcedVerify = strtoupper(substr($calcedVerify,0,8));
		if ($calcedVerify == $_POST["cverify"]) {
			return 1;
		} else {
			$this->debug_email('Clickbank IPN Debug (Validation Fail)',"Calculated cVerify : " . $calcedVerify . "\r\nPosted cVerify : " .$_POST["cverify"] . "\r\nPrehash :" . $pop, $_POST);
			return 0;
		}
	} // function
		


		
					
	// Clickbank : Throw an action based off the transaction type of the message - Based on Paypal Framework by Aaron Cambell (Xavisys)
	private function process_clickbank_message() {
		do_action("clickbank-ipn", $_POST);
		if ( !empty($_POST['ctransaction']) ) {
			$specificAction = strtolower(" and clickbank-{$_POST['ctransaction']}");
			do_action(strtolower("clickbank-{$_POST['ctransaction']}"), $_POST);
		}
		$this->debug_email('Clickbank IPN Debug (Message Processed)', "Actions thrown: clickbank-ipn{$specificAction}\r\n\r\n",$_POST);
	} // function	
		
	// Helper function to send out the debugging email
	private function debug_email($subject,$message = '',$post = '') {
		if ( $this->options['debug'] == '1' && !empty($this->options['email'] ) ) {
			wp_mail($this->options['email'] , $subject, $message ."\r\n\r\nPost Data:\r\n\r\n".print_r($post, true));
		}
	} // function

} // end class

// Instantiate
$wpClickbankFramework = wpClickbankFramework::getInstance();

?>