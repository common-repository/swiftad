<?php
/*
 *  COPYRIGHT AND TRADEMARK NOTICE
 *  Copyright 2008-2015 Blog Nirvana. All Rights Reserved.
 *  SwiftImpresions is a trademark of BlogNirvana.

 *  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
 *  By using this code you agree to indemnify Blog Nirvana from any
 *  liability that might arise from it's use.
 */


function swiftad_license_activate() {
	$swiftad_config	= get_option('swiftad_config');
	global $wpsa_notifications;
	$license = get_option('swiftpost_license');
	
	
	if ( ! isset($license['license_key']) || !isset($license['server_key']) || (isset($license['license_key']) && strlen($license['license_key']) < 16 ) || ( isset($license['server_key']) && strlen($license['server_key']) < 128 ) ) {

		$user_id  = get_current_user_id();
		$current_user = wp_get_current_user();
		$site = get_bloginfo("url");
		
		$handle = preg_replace("(^https?://)", "", $site );
		$handle = preg_replace('/[\s\W]+/', '', $handle);
		$data = array (
						'blog' => get_bloginfo("name"),
						'url' => $site,
						'email' => get_bloginfo("admin_email"),
						'handle' => $handle,
						'server' => $_SERVER['SERVER_NAME'],
						'user' => $current_user->user_login ,
						'date' => date("Y-m-d H:i:s"),
						'accepted_terms' => $_POST['swiftad-accept-terms']
						);
		
		
		$post = array (
			'body' => array('data' => $data),
			'timeout' => 20
		);
	
		$url ="http://api.swiftimpressions.com/plugin_activate";
		$result = wp_safe_remote_post($url, $post);

		$result1 = wp_remote_retrieve_body($result);

		if ($reg = json_decode($result1)) {
			if (isset($reg->error)) {
				$wpfn_notifications->add("License Reg Error 3", __($reg->error),array('status' => 'error','icon' => 'thumbs-down'));
			} else {
				$license['license_key'] 	= $reg->license->license_key;
				$license['server_key'] 		= $reg->license->server_key;
				$license['reg_user'] 		= $current_user->user_login;
				$license['reg_date'] 		= date("Y-m-d H:i:s");
				$license['parent_code'] 	= $reg->license->ParentAdCode;
				$license['status'] 			= "registered";
				$license['level'] 			= $reg->license->level;
				
				$wpsa_notifications->add("Plugin Registered", __( 'The plugin was successfully registered to this installation.'),array('status' => 'success','icon' => 'thumbs-up'));
				update_option('swiftpost_license', $license);
			}
		} else {
			$wpsa_notifications->add("License activate reg error", __('There was a problem registering your plugin. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		}
		
		set_transient("swiftad_save_errors_{$user_id}", $wpsa_notifications, 45);
	}
}



function swiftad_license_check() {
	global $wpsa_notifications;
	$license = get_option('swiftpost_license');
	$swiftad_config	= get_option('swiftad_config');
	if(isset($license['license_key']) && isset($license['server_key']) && !get_transient("swiftimpressions_license_check")) {
		$post = array("timeout" => 200, "body" => array( "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		$url ="http://api.swiftimpressions.com/license_check";
		$reply = wp_safe_remote_post($url, $post);
		$response  = wp_remote_retrieve_body($reply);
		
		if($reg = json_decode($response)) {
			$license['parent_code'] 	= $reg->ParentAdCode;
			$license['level'] 			= $reg->level;
			update_option('swiftpost_license', $license);
			set_transient("swiftimpressions_license_check", true, 84600);
		}
		//if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>License Check Result:\n" . var_export($reg, true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
	}
}

/*function swiftad_make_draft( $postid ) {
	remove_action( 'save_post',  'swiftad_save_meta_box_data');
	$current_post = get_post( $post_id, 'ARRAY_A' );
    $current_post['post_status'] = "draft";
    wp_update_post($current_post);
	add_action('save_post','swiftad_save_meta_box_data');
}*/
	
	
function swiftadStartSession() {
    swiftad_license_check();
}

function swiftadEndSession() {
   delete_transient("swiftimpressions_license_check");
}
  
 
/*
 * placeholder, not used yet
 * 
 *  
 */

function swiftad_admin_notices() {
	echo "<div class=\"swiftad-notices\">\n";
	$user_id = get_current_user_id();
	global $wpsa_notifications;
	do_action('wpsa_notifications');
	
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}  else if ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) {
		return;
	} else if ( $errors = get_transient("swiftad_save_errors_{$user_id}") ) { 
		$wpsa_notifications = $errors;
	    do_action('wpsa_notifications');
	    delete_transient("swiftad_save_errors_{$user_id}");
	}	
	echo "</div>\n";
}

/*
 * load dashboard/admin scripts
 * 
 *  
 */

function swiftad_admin_scripts() {
	wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui' , "//ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/themes/base/jquery-ui.css?ver=4.4.2"); 
	wp_enqueue_style( 'swiftad-admin-stylesheet', plugins_url( '/css/swiftad_admin.css', __FILE__ ) );
	wp_enqueue_script( 'swiftad-admin-script', plugins_url( '/js/swiftad_admin.js', __FILE__ ),null,1,true);
	wp_enqueue_script( 'jquery-timepicker', plugins_url( '/js/jquery.timepicker.min.js', __FILE__ ),null,1,true);
	wp_enqueue_style( 'jquery-timepicker', plugins_url( '/css/jquery.timepicker.css', __FILE__ ) );
	wp_localize_script( 'swiftad-admin-script', 'swiftad_locals', array('license_url' => plugins_url( '/plugin-activate-terms.html', __FILE__ )));
}

function swiftad_pause_post($postid) {
	global $wpsa_notifications;
	// unhook this function so it doesn't loop infinitely
	remove_action( 'save_post', 'swiftad_save_meta_box_data' );
	remove_action('save_post', 'save_swiftad_fileds');
	// update the post, which calls save_post again
	wp_update_post( array( 'ID' => $postid, 'post_status' => 'draft' ) );
	$wpsa_notifications->add("Swift Ad DB Update Error", __( 'Swift Ad status set to draft.'),array('status' => 'success','icon' => 'thumbs-up'));
	// re-hook this function
	add_action('save_post', 'swiftad_save_meta_box_data', 10, 3);
	add_action('save_post', 'save_swiftad_fileds');
}


/**
 * Add Swift Ad edit box to Post Edit.
 */
function swiftad_add_meta_box() {

	$screens = array( 'swiftad_post_type' );

	foreach ( $screens as $screen ) {
		add_meta_box(
			'swiftad_fields',
			__( 'Swift Ad Display', 'swiftad_textdomain' ),
			'swiftad_fields_callback',
			$screen, 'normal', 'high'
		);
		add_meta_box(
			'swiftad_serve_edit',
			__( 'Swift Ad Serve Setup', 'swiftad_textdomain' ),
			'swiftad_meta_box_callback',
			$screen, 'normal', 'low'
		);
	}
}
add_action( 'add_meta_boxes', 'swiftad_add_meta_box' );

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function swiftad_fields_callback( $post ) {
	
	global $wpdb;
	$adcode = "";
	$target = "";
	$custom = get_post_custom($post->ID);
	if (isset($custom["ad_code"]) ) $adcode = $custom["ad_code"][0];
	if (isset($custom["swiftad_target"]) ) {
		 $target = $custom["swiftad_target"][0];
	}
	//echo "<p><label>Ad Code:</label><br /> <textarea  rows=\"7\" name=\"ad_code\" class=\"ad_code\">$adcode</textarea></p>";

     $settings = array(
    'teeny' => true,
    'textarea_rows' => 10,
    'tabindex' => 1,
    'media_buttons' => true,
    'drag_drop_upload' => true
     );
     
     wp_editor( __($adcode), 'ad_code', $settings);

	echo "<i>All links need to have the attribute target=&quot;_blank&quot; included or they will not work. When using the visual editor the &quot;Open link in a new tab&quot; box needs to be checked.</i><br />";
	
	$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}swiftad_inventory WHERE status='active' ", OBJECT );

	echo "<p><label>Target Slot: </label><select name=\"swiftad_target\" id=\"swiftad-target\" >\n";
	
	foreach ($results as $key => $slot) {
		echo "<option value=\"{$slot->slotid}|{$slot->size}|{$slot->name}\" " . ($slot->slotid == $target ? " selected" : "" ) . ">{$slot->name} ({$slot->size})</option>\n";
	}
	echo "</select><br /><i>This is where the ad will display</i>\n";

}


/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function swiftad_meta_box_callback( $post ) {
	global $wpdb;
	$user_id = get_current_user_id();
	$query = "SELECT * FROM ".$wpdb->prefix."swiftad_displayad WHERE `postid` = " . $post->ID;
	$disabled = "";
	$startclass = "datepicker";
	$license = get_option('swiftpost_license');
	$not_reg = "";
	$disabled = "";
	$date_disabled 	= "";
	
	if ($license['status'] != "registered" ) {
		$not_reg = '<p>The Swift Ad plugin has not yet been registered. Please enter the unique license code your received after registering on SwiftImpressions.com. If you are having problems setting up your account or registering the plugin please contact support by phone at 208.991.4865 or by email at njones@swiftimpressions.com</p>';
		$disabled = " disabled=\"disabled\"  readonly=\"readonly\"  ";
		
	}
	
	if ( $default = $wpdb->get_results($query)) {
		$startdate = $default[0]->startdate;
		$starttime = $default[0]->starttime;
		if (!($startdate > date("Y-m-d"))) {
			$date_disabled 	= ' readonly="readonly" ';
			$startclass = "";
		}
		$enddate 		= $default[0]->enddate;
		$endtime 		= $default[0]->endtime;
		$post_status 	= "checked";
		$impressions 	= $default[0]->impressions;
		$fc 			= $default[0]->fc;
		$fc_impressions = $default[0]->fc_impressions ;
		$fc_howmany 	= $default[0]->fc_howmany ;
		$fc_type	 	= $default[0]->fc_type;
		$fc_lifetime 	= $default[0]->fc_lifetime;
		$gd				= $default[0]->gd;
		$geo		 	= ( empty($default[0]->geo) ? "" : unserialize($default[0]->geo));
		$geo_json 		= (empty($geo) ? "" : json_encode($geo));
		$status			= $default[0]->status;
	} else {
		$post_status 	= "unchecked";
		$startdate 		= "";
		$starttime 		= "";
		$enddate	 	= "";
		$endtime	 	= "";
		$post_status 	= "";
		$impressions 	= "";
		$fc 			= "off";
		$fc_impressions = "";
		$fc_howmany 	= "";
		$fc_type	 	= "";
		$fc_lifetime 	= "";
		$gd				= "off";
		$geo		 	= "";
		$geo_json 		= "";
		$status			= "";
	}
	

	// Add a nonce field so we can check for it later.
	wp_nonce_field( 'swiftad_save_meta_box_data', 'swiftad_meta_box_nonce' );
	
	
    echo $not_reg;
   ?>

	<?php if ($post_status == "checked") :?>
		<p>
	     <input type="checkbox" id="swiftad_pp_pasued" name="swiftad_pp_paused" <?php echo $disabled; ?> <?php echo ($status != "live" ? "checked" : "") ; ?> size="25" />
		 <label for="swiftad_new_field">Pause</label>
		</p>
	<?php endif; ?>
	<div class="swiftad-meta-section" id="swiftad-meta-general">
	<p><label>Start Date: </label> <input type="text" id="pp-start-date" class="<?php echo $startclass; ?>" name="swiftad_pp_start_date" value="<?php echo $startdate; ?>"  <?php echo $disabled; ?> <?php echo $date_disabled; ?> /></p>
	<p><label>Start time: </label> <input type="text" id="pp-start-time" class="timepicker" name="swiftad_pp_start_time" value="<?php echo $starttime; ?>"  <?php echo $disabled; ?> <?php echo $date_disabled; ?> /></p>
	<p><label>End Date: </label> <input type="text" id="pp-end-date" class="datepicker" name="swiftad_pp_end_date" value="<?php echo $enddate; ?>" <?php echo $disabled; ?> /></p>
	<p><label>End Time: </label> <input type="text" id="pp-end-time" class="timepicker" name="swiftad_pp_end_time" value="<?php echo $endtime; ?>"  <?php echo $disabled; ?> /></p>
	<p><label>Total Impressions: </label> <input type="text" id="pp-quanity" class="" name="swiftad_pp_quanity" value="<?php echo $impressions; ?>" <?php echo $disabled; ?> /></p>
	<?php if (isset($default[0]->post_status)): ?>
		<p><input type="checkbox" name="swiftad-newline" id="swiftad-newline" style="width: 10px;"  <?php echo $disabled; ?> /> Start New Run?</p>
	<?php endif; ?>
	
	</div>
  


	<div class="swiftad-meta-section" id="swiftad-meta-pp-fc-section">
		<h4>Frequency</h4> 
		
		<p>
			<input type="checkbox" value="on" id="UserFrequency-input" name="user-frequency-input" <?php echo ($fc=="on" ? "checked" : ""); ?>  <?php echo $disabled; ?> />
			<label  id="chkPerUserFrequency-label">Set per user frequency cap</label>
		</p>
		<p>Impressions per user:</p>
		<p>
			<input type="text" class="swiftad-fc-shorttext" tabindex="0" maxlength="5" name="swiftad_fc_impress"  <?php echo ($fc=="on" ? " value='$fc_impressions' " : ""); ?> length="3" <?php echo $disabled; ?> /> per 
			<input type="text" class="swiftad-fc-shorttext" tabindex="0" maxlength="5" name="swiftad_fc_howmany" <?php echo ($fc=="on" ? " value='$fc_howmany' " : ""); ?> length="3" <?php echo $disabled; ?> /> 
			<select name="swiftad_fc_type" class="">
				<option value="MINUTE" <?php echo ($fc=="on" && $fc_type=="MINUTE" ? " selected " : ""); ?> <?php echo $disabled; ?> >minutes</option>
				<option value="HOUR"<?php echo ($fc=="on" && $fc_type=="HOUR" ? " selected " : ""); ?> <?php echo $disabled; ?> >hours</option>
				<option value="DAY"<?php echo ($fc=="on" && $fc_type=="DAY" ? " selected " : ""); ?> <?php echo $disabled; ?> >days</option>
				<option value="WEEK"<?php echo ($fc=="on" && $fc_type=="WEK" ? " selected " : ""); ?> <?php echo $disabled; ?> >weeks</option>
				<option value="MONTH"<?php echo ($fc=="on" && $fc_type=="MONTH" ? " selected " : ""); ?> <?php echo $disabled; ?> >months</option>
			</select>
   		</p>
		<p>and/or</p>
		
			<input type="text" class="swiftad-fc-shorttext" tabindex="0" maxlength="5" name="swiftad_fc_impress_lifetime"  length="3"<?php echo ($fc=="on" ? " value='$fc_lifetime' " : ""); ?>  <?php echo $disabled; ?> /> max impressions per visitor
		
	</div>

	<div class="swiftad-meta-section" id="swiftad-meta-pp-gt-section">
	    <h4>Geo Targeting</h4>
		<p>
			<input type="checkbox" value="on" id="geo-data-input" name="geo-data-input" <?php echo ($gd=="on" ? "checked" : ""); ?>  <?php echo $disabled; ?> />
			<label  id="chkGeoData-label">Limit to geographical areas</label>
		</p>
		<div id="pp-targeting-searchbox">
		     Target Type:
		     <select name="swiftad_gt_type" id="swiftad_gt_type" <?php echo $disabled; ?> >
		     	<option>--Choose Type--</option>
		     	<option value="City">City</option>
			     	<option value="Country">Country</option>
			     	<option value="Postal_Code">Postal Code</option>
			     	<option value="State">State</option>
		     </select>
            <br />		
			<input id="swiftad_gt_search" type="text"  <?php echo $disabled; ?> /><a id="swiftad_gt_button" class="button btn" <?php echo $disabled; ?> >Search</a>
		</div>
		<div id="swiftad_gt_results"></div>
		<p>Targets:</p>
		<div id="swiftad_gt_selections"></div>
	</div>
	
		<input type="hidden" name="swiftad-geotargets" id="swiftad-geotargets" value='<?php echo $geo_json;?>' />
	<div style="clear:both;"><!--Clear--></div>
	<?php
	
}

/**
 * When the swift ad is saved, save settings custom data.
 *
 * 
 */
 
function  save_swiftad_fileds($post_id){

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if(  wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'swiftad_post_type' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

	} else {
			return;
	}

 	update_post_meta($post_id, "ad_code",  $_POST["ad_code"]);
 	update_post_meta($post_id, "swiftad_target", strtok($_POST["swiftad_target"], "|"));
 	update_post_meta($post_id, "swiftad_size",strtok( "|"));
 	update_post_meta($post_id, "swiftad_target_type",strtok( "|"));
 	update_post_meta($post_id, "swiftad_target_name",strtok( "|"));
}


/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function swiftad_save_meta_box_data($post_id, $post, $update, $abtest = false) {
	$return = "";
	$user_id = get_current_user_id();
    $swiftad_config	= get_option('swiftad_config');
	$license = get_option('swiftpost_license');
	// add the notifications class
	global $wpsa_notifications;
	// get db class
	global $wpdb;
	//Check License
	if ($license['status'] != "registered" && isset( $_POST['swiftad_pp_on']) ) {
		if(	$license['status'] == "unregistered" ) {
			$wpsa_notifications->add("Swift Ad License Unregistered", __( 'Error: The  Swift Ad plugin has not yet been registered. Please enter the unique license code your received after registering on SwiftImpressions.com. If you are having problems setting up your account or registering the plugin please contact support by phone at 208.991.4865 or by email at njones@swiftimpressions.com' ),array('status' => 'error','icon' => 'thumbs-down'));
		} else {
			$wpsa_notifications->add("Swift Ad License Unregistered", __( 'Error: The  Swift Ad plugin has not yet been registered. Please enter the unique license code your received after registering on SwiftImpressions.com. If you are having problems setting up your account or registering the plugin please contact support by phone at 208.991.4865 or by email at njones@swiftimpressions.com' ),array('status' => 'error','icon' => 'thumbs-down'));
		}
		set_transient("swiftad_save_errors_{$user_id}", $wpsa_notifications, 45);
		return;
	}
	
	$query = "SELECT * FROM ".$wpdb->prefix."swiftad_displayad WHERE `postid` = " . $post_id;
	if (!$data = $wpdb->get_row($query,ARRAY_A,0)) {
		$data = array();
		$data['created'] = date("Y-m-d");
	}

	// We need to verify this came from our screen and with proper authorization, because the save_post action can be triggered at other times.


	// Check if our nonce is set.
	if ( ! isset( $_POST['swiftad_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['swiftad_meta_box_nonce'], 'swiftad_save_meta_box_data' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if(  wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	

	// Check the user's permissions.
	if ( (isset( $_POST['post_type'] ) && 'swiftad_post_type' == $_POST['post_type'])  || $abtest === true ) {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

	} else {
			return;
	}

	// OK, it's safe for us to save the data now. 
	// Is a Swift Ad?.

	$post_status = "on";
	
	$status = 'live';	
	
	if (isset( $_POST['swiftad_pp_paused'])) $status = 'paused';
    if ($post->post_status == "trashed") $post_status = "off";
    
    if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("\nSwift Ad Debug", "<pre>Swift Ad Status: {$post->post_status}\n\n</pre>",array('status' => 'debug','icon' => 'hammer'));
	
	if ( $post_status == "off" ) {
		if(isset($data["postid"]) ) {
			$data["post_status"] = "off";
			if(!($wpdb->replace( "{$wpdb->prefix}swiftad_displayad", $data))) {
				$wpsa_notifications->add("Swift Ad DB Update Error", __( 'There was an error disabling your Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				// add error reporting
				swiftad_pause_post($post_id);
				return;
			} 
			
			// Archive Order, poweroff 
			$request = array("postid" => $post_id, "swiftad" => true);
			$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
			// Update Ad Server
			$url ="http://api.swiftimpressions.com/poweroff";
			$reply = wp_safe_remote_post($url, $post);
			$response  = wp_remote_retrieve_body($reply);
			
			if($res = json_decode($response)) {
				if(isset($res->error)) {
					$wpsa_notifications->add("Swift Disable Error1", __('There was an error disabling your Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
					//error reporting  $res->error
					swiftad_pause_post($post_id);
				} else {
					$wpsa_notifications->add("Swift Ad Disabled", __('Swift Ad has been disabled for this post'),array('status' => 'success','icon' => 'thumbs-up'));

				}
			} else {
				$wpsa_notifications->add("Swift Disable Error2", __('There was an error disabling your Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				swiftad_pause_post($post_id);
				//error reporting  $res->error
			}
			if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>PostOff Query =>\n\n" . var_export($post, true) . "\n\nPostoff Response =>\n\n" . var_export($res, true) . "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer')); 
		} else {
			if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>PostOff Not Set, nothing to turn off</pre>",array('status' => 'debug','icon' => 'hammer')); 
		}
	} else if ($status == 'paused' && !isset( $_POST['swiftad-newline'])) {
		if(isset($data["postid"]) ) {
			$data["status"] = "paused";
			if(!($wpdb->replace( "{$wpdb->prefix}swiftad_displayad", $data))) {
				$wpsa_notifications->add("Swift Ad DB Update Error", __( 'There was an error pausing your Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				swiftad_pause_post($post_id);
				// report error  $wpdb->print_error()
				return;
			} 
			
			// Pause Order, poweroff 
			$request = array("postid" => $post_id, "swiftad" => true);
			$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
			// Update Ad Server
			$url ="http://api.swiftimpressions.com/poweroff";
			$reply = wp_safe_remote_post($url, $post);
			$response  = wp_remote_retrieve_body($reply);
			
			if($res = json_decode($response)) {
				if(isset($res->error)) {
					$wpsa_notifications->add("Swift Disable Error1", __('There was an error pausing your Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
					// report error $res->error
					swiftad_pause_post($post_id);
				} else {
					$wpsa_notifications->add("Swift Ad Paused", __('Swift Ad has been paused for this post'),array('status' => 'success','icon' => 'thumbs-up'));
					swiftad_pause_post($post_id);
				}
			} else {
				$wpsa_notifications->add("Swift Disable Error2", __('There was an error pausing your Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $res->error),array('status' => 'error','icon' => 'thumbs-down'));
				// report error
				swiftad_pause_post($post_id);
			}
			if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>PostOff Query =>\n\n" . var_export($post, true) . "\n\nPostoff Response =>\n\n" . var_export($res, true) . "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer')); 
		} else {
			if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>Swift Ad Not Set, nothing to pause</pre>",array('status' => 'debug','icon' => 'hammer'));
		}
	} else {
		// Sanitize user input.
		$startdate 		= sanitize_text_field( $_POST['swiftad_pp_start_date'] );
		$enddate 		= sanitize_text_field( $_POST['swiftad_pp_end_date'] );
		$starttime 		= sanitize_text_field( $_POST['swiftad_pp_start_time'] );
		$endtime 		= sanitize_text_field( $_POST['swiftad_pp_end_time'] );
		$impressions 	= sanitize_text_field( $_POST['swiftad_pp_quanity'] );
		if (isset($_POST['user-frequency-input']) && $_POST['user-frequency-input']=="on") {
			$fc 			= sanitize_text_field( $_POST['user-frequency-input'] );
			$fc_impress 	= sanitize_text_field( $_POST['swiftad_fc_impress'] );
			$fc_howmany 	= sanitize_text_field( $_POST['swiftad_fc_howmany'] );
			$fc_type	 	= sanitize_text_field( $_POST['swiftad_fc_type'] );
			$fc_lifetime 	= sanitize_text_field( $_POST['swiftad_fc_impress_lifetime'] );
		} else {
			$fc = "off";
			$fc_impress 	= "";
			$fc_howmany 	= "";
			$fc_type	 	= "";
			$fc_lifetime 	= "";
		}
		if (isset( $_POST['swiftad-newline'])) {
			$data['run'] = "newrun";
		} else if (!isset($data['run']) ||  $data['run'] <  1) {
			$data['run'] = 1;
		}
		
		
		//Check values then break and return errors if there are any.
		$errors = 0;
		if (!is_numeric($impressions) || $impressions > 1000000) {
    		$errors++;
    		$wpsa_notifications->add("SPFormError1", __('Invalid Swift Ad value - Total Impressions  must be numeric and less than 1,000,000'),array('status' => 'error','icon' => 'thumbs-down'));
    		
    		return;
    	}
	    if ($fc=="on") {
	    	if (isset($fc_impress) && (!is_numeric($fc_impress) || $fc_impress > 1000)) {
	    		$errors++;
	    		$wpsa_notifications->add("SPFormError2", __('Invalid Swift Ad value - 1rst box in Impressions per user: must be numeric and less than 1000'),array('status' => 'error','icon' => 'thumbs-down'));
	    	}
		    if (isset($fc_impress) && (!is_numeric($fc_howmany) || $fc_howmany > 1000)) {
	    		$errors++;
	    		$wpsa_notifications->add("SPFormError3", __('Invalid Swift Ad value - 2nd box in Impressions per user: must be numeric and less than 1000' ),array('status' => 'error','icon' => 'thumbs-down'));
	    	}
	    }
	    $dateTimestamp1 = new DateTime($startdate . " " . date("H:i:s",strtotime($starttime)), new DateTimeZone('America/New_York'));
		$dateTimestamp2 = new DateTime($enddate . " "  .date("H:i:s" , strtotime($endtime)), new DateTimeZone('America/New_York'));
		$dateTimestamp3 = new DateTime("NOW", new DateTimeZone('America/New_York'));
		
	    if ($dateTimestamp1 >= $dateTimestamp2  || $dateTimestamp2 <= $dateTimestamp3 ) {
	    	$errors++;
	    	$wpsa_notifications->add("SPFormError4", __('Invalid Swift Ad form value: Dates must be in the format yyyy-mm-dd and the end date must be greater than the start date which must be today or later. ' ),array('status' => 'error','icon' => 'thumbs-down'));
	    }
	    if ($errors > 0) {
	    	set_transient("swiftad_save_errors_{$user_id}", $wpsa_notifications, 45);
	    	swiftad_pause_post($post_id);
	    	return;
	    }
	    
	    
		$geo		 			= stripcslashes($_POST['swiftad-geotargets']) ;
		$geo_php				= json_decode($geo);	
		$data["postid"]			= $post_id;
		$data["impressions"]	= $impressions;
		$data["startdate"] 		= $startdate;
		$data["enddate"] 		= $enddate;
		$data["starttime"] 		= $starttime;
		$data["endtime"] 		= $endtime;
		$data["fc"]				= $fc;
		$data["fc_impressions"] = $fc_impress;
		$data["fc_howmany"] 	= $fc_howmany;
		$data["fc_type"] 		= $fc_type;
		$data["fc_lifetime"] 	= $fc_lifetime;
		$data["geo"] 			= $geo_php;
		$data["post_status"] 	= $post_status;
		if ($swiftad_config['serve-asap'] == "on") $data["delivery"] = "asap";
 		$data["ad_target"] 		= strtok($_POST["swiftad_target"], "|");
 		$data["ad_size"] 		= strtok( "|");
 		$data["ad_type"]		= strtok( "|");
 		
		$file = dirname(dirname(__FILE__)) . '/swiftad.php';
 		$raw_adcode = "<script type=\"text/javascript\" > document.addEventListener(\"click\", function(event) {  var ccUrl = '%%CLICK_URL_UNESC%%' + '" . plugin_dir_url($file) . "images/pixel.png?ord=%%CACHEBUSTER%%';  new Image(1,1).src = ccUrl;  }, true); </script>" . $_POST["ad_code"];
 		
 		$adcode = stripslashes($raw_adcode);
 		$adcode = str_replace(array("\r", "\n"), '', $adcode);
 		$adcode = str_replace(array("'"), '"', $adcode);
 		 		
 		if (isset($data["ad_type"]) && $data["ad_type"] == "PopUp") {
 			$data["ad_code"] 	= urlencode("<script type=\"text/javascript\">window.parent.swiftad_popup_fill('$adcode');</script>");
 		} else {
 			$data["ad_code"] 	= urlencode($adcode);
 		}
 		
 		$data["swiftad"] = true;
 		
 		

		$post = array("timeout" => 200, "body" => array("request" => $data, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		
		// Update Ad Server
		$url ="http://api.swiftimpressions.com/poweron";
		$reply = wp_safe_remote_post($url, $post);
		$response = wp_remote_retrieve_body($reply);
		
		unset($data["swiftad"]);
		
		if ($res = json_decode($response)) {
			if (isset($res->error)) {
				if ($abtest) {  
					$return = $res;
				} else {
					if (isset($res->overbook) && $res->overbook == true) {
						$wpsa_notifications->add("Swift Ad AB Test DB Update Error", __( 'There was an error creating/updating your Swift Ad AB Test. This test would overbook your monthly limit. You can adjust your subscription at http://swiftimpressions.com or please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com. '. $res->error),array('status' => 'error','icon' => 'thumbs-down'));
					} else {
						$wpsa_notifications->add("Swift Ad AB Test DB Update Error", __( 'There was an error creating/updating your Swift Ad AB Test. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));	
					}

				}
				//Make post draft
				swiftad_pause_post($post_id);
			} else {
				// Update Swift Ad Table
				if ( isset($data['geo']) && !empty($data['geo']) ) { 
					$data['geo'] = serialize($data['geo']);
				} else {
					$data['geo'] = "";	
				}
				$data["order_id"] = $res->orderid;
				$data["lineitem_id"] = $res->lineitemid;
				$data["run"] = $res->run;
				$data["status"] = "live";
				if ($abtest) $data["status"] = "abtest";
				if(!($wpdb->replace( "{$wpdb->prefix}swiftad_displayad", $data))) {
					if ($abest) {  
						$return = array("error" => "DB Error");
					} else {
						$wpsa_notifications->add("Swift Ad DB Update Error", __( 'There was an error creating/updating your Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
						//Make post draft
						swiftad_pause_post($post_id);
					}
				} else {
					$wpsa_notifications->add("Swift Ad Created/Updated", __( 'Swift Ad Created/Updated'),array('status' => 'success','icon' => 'thumbs-up'));
					if ($abtest) $return = $data;
				}	
			}
		} else {
			if ($abest) {  
				$return = array("error" => "DB Error");
				//Make post draft
				swiftad_pause_post($post_id);
			} else {
				$wpsa_notifications->add("Swift Ad DB Update Error", __( 'There was an error creating/updating your Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				//Make post draft
				swiftad_pause_post($post_id);
			}
		}
		if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>PostOn Query =>\n\n" . var_export($post, true) . "\n\nPoston Response =>\n\n" . var_export($res, true) . "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
	}

	set_transient("swiftad_save_errors_{$user_id}", $wpsa_notifications, 45);
	 return $return;
}

/*
 * Create new ad slot
 *
 */
function create_slot($slot) {
	$swiftad_config	= get_option('swiftad_config');
	global $wpsa_notifications, $wpdb;
	$license = get_option('swiftpost_license');
	if ( isset($license['license_key'])) {
		
		$post = array("timeout" => 200, "body" => array("request" => $slot, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		
		// Update Ad Server

		$url ="http://api.swiftimpressions.com/adunitcreate";
		$reply = wp_safe_remote_post($url, $post);
		$response = wp_remote_retrieve_body($reply);

		if ($res = json_decode($response)) {
			if (isset($res->error)) {
				$wpsa_notifications->add("Swift Ad DB Update Error", __( 'There was an error creating/updating your Swift Ad Slot. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
			} else {
				$data = array();
				$safename = str_replace("|","&#124;",$slot['name']);
				$data['name'] = $safename;
				$data['description'] = $slot['description'];
				$data['status'] = 'active';
				$data['code'] = $res->adUnits->adUnitCode;
				$data['size'] = $slot['size'];
				$data['slotid'] = $res->adUnits->id;
				$data['created'] = date('Y-m-d H:i:s');		
				if (!($wpdb->replace( "{$wpdb->prefix}swiftad_inventory", $data))) {
					$wpsa_notifications->add("Swift Ad DB Update Error", __( 'There was an error creating/updating your Swift Ad Slot. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				} else {
					$wpsa_notifications->add("Swift Ad Slot Created/Updated", __( 'Swift Ad Slot Created/Updated'),array('status' => 'success','icon' => 'thumbs-up'));
				}
			}
		} 

	} else {
		$wpsa_notifications->add("Swift Ad DB Update Error", __( 'Your plugin has to be resgistered to create ad slots. Please visit swiftimpressions.com to register for free, if you have any questions call 208.991.4865 or email njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
	}
	if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>Ad Slot Create Result:\nrequest:\n" . var_export($slot, true)."\nresult:\n" . var_export($response, true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
}


/*
 * Apply License Key to Blog Installation
 *
 */
function swiftad_license_apply() {
	$swiftad_config	= get_option('swiftad_config');
	global $wpsa_notifications;
	$license = get_option('swiftpost_license');
	if ( isset($_POST['license_key'])) {
		$key = sanitize_text_field($_POST['license_key']);
		$current_user = wp_get_current_user();
		$data = array (
			'body' => array (
						'key' => $key,
						'server' => $_SERVER['SERVER_NAME'],
						'user' => $current_user->user_login ,
						'date' => date("Y-m-d H:i:s")
						),
			'timeout' => 20
		);
	
		$url ="http://api.swiftimpressions.com/register";
		$result = wp_safe_remote_post($url, $data);
		$result1 = wp_remote_retrieve_body($result);
	
		if ($reg = json_decode($result1)) {
			if (isset($reg->error)) {
				$wpsa_notifications->add("License Reg Error 3", __($reg->error),array('status' => 'error','icon' => 'thumbs-down'));
			} else {
				$license['license_key'] 	= $reg->license->license_key;
				$license['server_key'] 		= $reg->license->server_key;
				$license['reg_user'] 		= $reg->license->reg_user;
				$license['reg_date'] 		= $reg->license->reg_date;
				$license['parent_code'] 	= $reg->license->ParentAdCode;
				$license['status'] 			= "registered";
				//$license['error'] = "Plugin Registered";
				$wpsa_notifications->add("Plugin Registered", __( 'The plugin was successfully registered to this installation.'),array('status' => 'success','icon' => 'thumbs-up'));
				update_option('swiftpost_license', $license);
			}
		} else {
			//$license['error'] = "There was a problem registering your plugin, please check your key and contact support if you are still having problems";
			$wpsa_notifications->add("License Reg Error 1", __('There was a problem registering your plugin. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		}

	} else {
		$wpsa_notifications->add("License Reg Error 2", __('There was a problem registering your plugin. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
	}

	if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>License Register Result:\nrequest:\n" . var_export($reg, true)."\nresult:\n" . var_export($result, true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
	
}

/**
 * License Release
 *
 *
 */
function swiftad_license_release() {
	$swiftad_config	= get_option('swiftad_config');
	$license = get_option('swiftpost_license');
	global $wpsa_notifications;
	if ( isset($_POST['license_key']) && isset( $license['server_key'])) {
		$key = sanitize_text_field($_POST['license_key']);
		$data = array (
			'body' => array (
						'license_key' => $key,
						'server_key' => $license['server_key']
						)
		);
		
		$url ="http://api.swiftimpressions.com/release";
		$result = wp_remote_retrieve_body(wp_safe_remote_post($url, $data));
		if (isset($result['error'])) {
			//$license['error'] =  $result['error'];
			$wpsa_notifications->add("License Release Error1", __('There was a problem releasing your license key. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		} else {
			$license['license_key'] 	= "";
			$license['server_key'] 		= "";
			$license['reg_user'] 		= "";
			$license['reg_date'] 		= "";
			$license['status'] 			= "unregistered";
			//$license['error'] = "Your license key has been released";
			$wpsa_notifications->add("License Released", __('Your license key has been released.'),array('status' => 'success','icon' => 'thumbs-up'));
		}
	} else {
		$wpsa_notifications->add("License Released", __('There was a problem releasing your license key. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
	}
	update_option('swiftpost_license', $license);
	
		 
	if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>License Release Result:\n" . var_export($result, true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
}

/**
 * Apply Post Status cahnges from post manage page
 *
 *
 */
function swiftad_postmanage($action, $postid, $run) {
	$swiftad_config = get_option('swiftad_config');
	// add the notifications class
	global $wpsa_notifications;
	// get db class
	global $wpdb;
	$query = "SELECT * FROM ".$wpdb->prefix."swiftad_displayad WHERE `postid` = " . $postid;
	
	if ($data = $wpdb->get_row($query,ARRAY_A,0)) {
		$license = get_option('swiftpost_license');
		/* Pause Order, poweroff */

		$request = array("postid" => $postid, "action" => $action, "run" => $run, "data" => $data, "swiftad" => true);
		$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		
		
		// Update Ad Server
		$url ="http://api.swiftimpressions.com/poweronmanage";
		$reply = wp_safe_remote_post($url, $post);
		$response  = wp_remote_retrieve_body($reply);
		
		if($res = json_decode($response)) {
			if(isset($res->error)) {
				$wpsa_notifications->add("Swift Pause Error 1", __('Error turning off Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $res->error),array('status' => 'error','icon' => 'thumbs-down'));
				// report error $res->error
			} else if(isset($res->success)) {
				if ($action == 'pause') {
					$data['status'] = "paused";
				} else if ($action == 'resume') {
					$data['status'] = "live";
				} else if ($action == 'new run') {
					$data['status'] = "live";
					$data['run'] = $res->run;
					$data['lineitem_id'] = $res->lineitemid;
					$data['startdate'] = $res->startdate;
					$data['enddate'] = $res->enddate;
				}
				
				if(!($wpdb->replace( "{$wpdb->prefix}swiftad_displayad", $data))) {
					$wpsa_notifications->add("Swift Ad DB Update Error", __( 'There was a problem updating your post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $wpdb->print_error()),array('status' => 'error','icon' => 'thumbs-down'));
					// report error $wpdb->print_error()
				} else {
					$wpsa_notifications->add("Swift Ad Updated", __( 'Swift Ad Updated, status set to '.$action."d"),array('status' => 'success','icon' => 'thumbs-up'));
				}
				
			}
		} else {
			$wpsa_notifications->add("Swift Pause Error 2", __('There was a problem pausing your post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
			// report error  $res->error
		}
		if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>Request Query =>\n " . var_export($request, true) . "\nPostoff Response =>\n\n" . var_export($res, true) . "</pre>",array('status' => 'debug','icon' => 'hammer'));
	
	}

}


/**
 * If ad is trashed, cancel order
 *
 *
 */

function swift_ad_transition( $new_status, $old_status, $post) {
	
    $post_id = $post->ID;
    $user_id = get_current_user_id();
    // Check the user's permissions.
	if ( isset( $post->post_type ) && 'swiftad_post_type' ==  $post->post_type  ) {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

	} else {
			return;
	}
	
	// add the notifications class
	global $wpsa_notifications;
	// get db class
	global $wpdb;
	$query = "SELECT * FROM ".$wpdb->prefix."swiftad_displayad WHERE `postid` = " . $post_id;
	
	if ($data = $wpdb->get_row($query,ARRAY_A,0)) {
		
		error_log("\n\n". var_export($data, true) . "\n\n" );
		
		if ($data['status'] == "live" && $old_status == "publish" && $new_status == "trash") {
			swiftad_postmanage("pause", $post_id, $data['run']);
			set_transient("swiftad_save_errors_{$user_id}", $wpsa_notifications, 45);
			
		} else if ($data['status'] == "paused" && $new_status == "publish" && $old_status == "trash") {
			swiftad_postmanage('resume', $post_id, $data['run']);
			set_transient("swiftad_save_errors_{$user_id}", $wpsa_notifications, 45);
		}
		
	}
  
}

/**
 * A/B add test form
 *
 *
 */

function swiftad_abtest_form() {
	  global $wpdb, $swiftad_config, $wpsa_notifications;
	  $license = get_option('swiftpost_license');

     ?>	
     
    <form name="abtest-new" id="abtest-new" method="post" action="<?php echo admin_url("admin.php?page=swiftad-abtest"); ?>">   
    
	<input type="hidden" name="swiftad_pp_on" value="on" />
	<input type="hidden" name="swiftad-newline" value="on" />
	<?php wp_nonce_field( 'swiftad_save_meta_box_data', 'swiftad_meta_box_nonce' ); ?> 
	
    <div  class="swiftad-admin-box-fw">
	   	<div class="swift-admin-box-title-bar rs-status-red-wrap">
			<div class="swift-admin-box-title">New A/B Split Test</div>
			<div class="clear"></div>
		</div>
			<div class="swift-admin-box-inner ">
	    	<div>
		     	<label>Name this test: </label> <input name="swiftad_abtest_name" value="" id="swiftad_abtest_name" type="text">
		     </div>
		     <div>
		     	<label><!--Hypothesis--> </label> <input name="swiftad_abtest_hypothesis" value="" id="swiftad_abtest_hypothesis" type="hidden">
		     </div>
		    <div>
		     	<?php
		     	 $settings = array(
				    'teeny' => true,
				    'textarea_rows' => 10,
				    'tabindex' => 1,
				    'media_buttons' => true,
				    'drag_drop_upload' => true
			     );
			     echo "<h3>A Test Code Snippet:</h3>";
			     wp_editor( "", 'ad_code_a', $settings); 
			     echo "<h3>B Test Code Snippet:</h3>";
			     wp_editor( "", 'ad_code_b', $settings);
				echo "<br/><br/><i>Insert the macro '%%CLICK_URL_UNESC%%' in front of the url of links that you want to track, for instance '&lt;a href=&quot;http://example.com&quot;&gt;' would be  '&lt;a href=&quot;%%CLICK_URL_UNESC%%http://example.com&quot;&gt;' with the macro inserted</i><br />";
				
				$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}swiftad_inventory WHERE status='active' ", OBJECT );
				$hidden_slot = "";
				echo "<p><label>Target Slot: </label><select name=\"swiftad_target\" id=\"swiftad-target\" >\n";
				
				foreach ($results as $key => $slot) {
					echo "<option value=\"{$slot->slotid}|{$slot->size}|{$slot->name}\" >{$slot->name} ({$slot->size})</option>\n";
				}
				echo "</select><br /><i>This is where the ad test will display</i>\n<div id=\"hidden_slot\">$hidden_slot</div></p>\n";
			 ?>
		     </div>
		     <div id="test-block"></div>
	    
			<div class="swiftad-meta-section" id="swiftad-meta-general">
				<p><label>Start Date: </label> <input type="text" id="pp-start-date" class="datepicker" name="swiftad_pp_start_date" value=""  /></p>
				<p><label>Start time: </label> <input type="text" id="pp-start-time" class="timepicker" name="swiftad_pp_start_time" value="" /></p>
				<p><label>End Date: </label> <input type="text" id="pp-end-date" class="datepicker" name="swiftad_pp_end_date" value=""  /></p>
				<p><label>End Time: </label> <input type="text" id="pp-end-time" class="timepicker" name="swiftad_pp_end_time" value=""   /></p>
				<p><label>Total Impressions: </label> <input type="text" id="pp-quanity" class="" name="swiftad_pp_quanity" value=""  /></p>
				<p>This will be the total impressions booked for each test, so 2 times this amount will count against your monthly booking limit.</p>		
			</div>
		  
		
		
			<div class="swiftad-meta-section" id="swiftad-meta-pp-fc-section">
				<h4>Frequency</h4> 
				
				<p>
					<input type="checkbox" value="on" id="UserFrequency-input" name="user-frequency-input" />
					<label  id="chkPerUserFrequency-label">Set per user frequency cap</label>
				</p>
				<p>Impressions per user:</p>
				<p>
					<input type="text" class="swiftad-fc-shorttext" tabindex="0" maxlength="5" name="swiftad_fc_impress"   length="3"  /> per 
					<input type="text" class="swiftad-fc-shorttext" tabindex="0" maxlength="5" name="swiftad_fc_howmany"  length="3"  /> 
					<select name="swiftad_fc_type" class="">
						<option value="MINUTE"  >minutes</option>
						<option value="HOUR" >hours</option>
						<option value="DAY" >days</option>
						<option value="WEEK" >weeks</option>
						<option value="MONTH" >months</option>
					</select>
		   		</p>
				<p>and/or</p>
				
					<input type="text" class="swiftad-fc-shorttext" tabindex="0" maxlength="5" name="swiftad_fc_impress_lifetime"  length="3" /> max impressions per visitor
				
			</div>
		
			<div class="swiftad-meta-section" id="swiftad-meta-pp-gt-section">
			    <h4>Geo Targeting</h4>
				<p>
					<input type="checkbox" value="on" id="geo-data-input" name="geo-data-input" />
					<label  id="chkGeoData-label">Limit to geographical areas</label>
				</p>
				<div id="pp-targeting-searchbox">
				     Target Type:
				     <select name="swiftad_gt_type" id="swiftad_gt_type"  >
				     	<option>--Choose Type--</option>
				     	<option value="City">City</option>
			     		<option value="Country">Country</option>
			     		<option value="Postal_Code">Postal Code</option>
			     		<option value="State">State</option>
				     </select>
		            <br />		
					<input id="swiftad_gt_search" type="text"   /><a id="swiftad_gt_button" class="button btn" >Search</a>
				</div>
				<div id="swiftad_gt_results"></div>
				<p>Targets:</p>
				<div id="swiftad_gt_selections"></div>
			</div>
			
				<input type="hidden" name="swiftad-geotargets" id="swiftad-geotargets" value='' />
			<div style="clear:both;"><!--Clear--></div>
		</div>
		<input name="submit"  class="swiftad-btn-rainbow"  type="submit" value="Start Test">
	</div>
	</form>
	<?php
	
	
	
}

/**
 * A/B add test
 *
 *
 */


function swiftad_abtest_add() {
	$swiftad_config	= get_option('swiftad_config');
	$license = get_option('swiftpost_license');
	$user_id = get_current_user_id();
	$return = "";
	// add the notifications class
	global $wpsa_notifications;
	// get db class
	global $wpdb;
	
	$target = strtok($_POST["swiftad_target"], "|");
	$target_size = strtok( "|");
	$target_type = strtok( "|");
	$target_name = strtok( "|");	
	
	
	/* Create A Test */
	$a_post = array(
	     'post_title' => "A/B Test - " . $_POST['swiftad_abtest_name'] . " - A-Test",
	     'post_status' => 'publish',
	     'post_type' => 'swiftad_post_type'
	);
	
	$post_id_a = wp_insert_post($a_post);
	
	$post_a = get_post( $post_id_a, 'ARRAY_A' );
	
	update_post_meta($post_id_a, "ad_code",  $_POST["ad_code_a"]);
 	update_post_meta($post_id_a, "swiftad_target", $target);
 	update_post_meta($post_id_a, "swiftad_size",$target_size);
 	update_post_meta($post_id_a, "swiftad_target_type",$target_type);
 	update_post_meta($post_id_a, "swiftad_target_name",$target_name);
	
	$_POST['ad_code'] = $_POST['ad_code_a'];
	
	$test_a = swiftad_save_meta_box_data($post_id_a,$post_a, false, true);
	
	if ((is_object($test_a) && isset($test_a->error)) || !isset($test_a )) {
		if (isset($test_a->overbook) && $test_a->overbook == true) {
			$wpsa_notifications->add("Swift Ad AB Test DB Update Error", __( 'There was an error creating/updating your Swift Ad AB Test. This test would overbook your monthly limit. You can adjust your subscription at http://swiftimpressions.com or please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com. ' . $test_a->error),array('status' => 'error','icon' => 'thumbs-down'));
		} else {
			$wpsa_notifications->add("Swift Ad AB Test DB Update Error", __( 'There was an error creating/updating your Swift Ad AB Test. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		}
		wp_delete_post( $post_id_a, true );
		return;	
	}
	
	
	
	/* Create B Test */
	$b_post = array(
	     'post_title' => "A/B Test - " . $_POST['swiftad_abtest_name'] . " - B-Test",
	     'post_status' => 'publish',
	     'post_type' => 'swiftad_post_type'
	  );
	
	$post_id_b = wp_insert_post($b_post);
	$post_b = get_post( $post_id_b, 'ARRAY_A' );
	update_post_meta($post_id_b, "ad_code",  $_POST["ad_code_b"]);
 	update_post_meta($post_id_b, "swiftad_target", $target);
 	update_post_meta($post_id_b, "swiftad_size",$target_size);
 	update_post_meta($post_id_b, "swiftad_target_type",$target_type);
 	update_post_meta($post_id_b, "swiftad_target_name",$target_name);
	
	
	$_POST['ad_code'] = $_POST['ad_code_b'];
	$test_b = swiftad_save_meta_box_data($post_id_b,$post_b,false, true);
	
	if ((is_object($test_b) && isset($test_b->error)) || !isset($test_b )) {
		if (isset($test_b->overbook) && $test_b->overbook == true) {
			$wpsa_notifications->add("Swift Ad AB Test DB Update Error", __( 'There was an error creating/updating your Swift Ad AB Test. This test would overbook your monthly limit. You can adjust your subscription at http://swiftimpressions.com or please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com ' . $test_a->error),array('status' => 'error','icon' => 'thumbs-down'));
		} else {
			$wpsa_notifications->add("Swift Ad AB Test DB Update Error", __( 'There was an error creating/updating your Swift Ad AB Test. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		}
		wp_delete_post( $post_id_b, true );
		wp_delete_post( $post_id_a, true );
		return;	
	}
	
	/* DB Update Test Record */
	$data = array();
	$data['name'] 		= sanitize_text_field($_POST['swiftad_abtest_name']);
	$data['hypothesis'] = sanitize_text_field($_POST['swiftad_abtest_hypothesis']);
	$data['postid_a'] 	= $post_id_a;
	$data['postid_b'] 	= $post_id_b;
	$data['run_a'] 		= $test_a['run'];
	$data['run_b'] 		= $test_b['run'];
	$data['startdate'] 	= $test_a['startdate'];
	$data['starttime'] 	= $test_a['starttime'];
	$data['enddate'] 	= $test_a['enddate'];
	$data['endtime'] 	= $test_a['endtime'];
	$data['inventory'] 	= $target_name;
	$data['status']  	= 'running';
	$data['created'] 	= date('Y-m-d G:i:s');
	
	if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>AB Test Query =>\n\n" . var_export($data, true) ."</pre>",array('status' => 'debug','icon' => 'hammer'));

	if(!($wpdb->replace( "{$wpdb->prefix}swiftad_abtest", $data))) {
		$wpsa_notifications->add("Swift Ad AB Test DB Update Error", __( 'There was an error creating/updating your Swift Ad AB Test. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		return array("error" => "DB Error");
		
	} else {
		$wpsa_notifications->add("Swift Ad AB Test Created", __( 'Swift Ad AB Test Created'),array('status' => 'success','icon' => 'thumbs-up'));
		return array("sccess" => TRUE);
	}	
}

/**
 * Apply Post Status cahnges from post manage page
 *
 *
 */
function swiftad_abtest_manage($action, $testid) {
	$swiftad_config = get_option('swiftad_config');
	// add the notifications class
	global $wpsa_notifications;
	// get db class
	global $wpdb;
	$license = get_option('swiftpost_license');
	
	$test = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."swiftad_abtest WHERE `id` = " . $testid, ARRAY_A,0);
	$rows = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."swiftad_displayad WHERE `postid` IN (".$test['postid_a']." , ".$test['postid_b'].")",  ARRAY_A,0);
	foreach ($rows AS $row) {	
		/* Pause Orders, poweroff */
		$request = array("postid" => $row['postid'], "action" => $action, "run" => $row['run'], "data" => $row, "swiftad" => true);
		$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		
		// Update Ad Server
		$url ="http://api.swiftimpressions.com/poweronmanage";
		$reply = wp_safe_remote_post($url, $post);
		$response  = wp_remote_retrieve_body($reply);
		
		if($res = json_decode($response)) {
			if(isset($res->error)) {
				$wpsa_notifications->add("Swift A/B Test Error 1", __('Error turning off Swift Ad. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $res->error),array('status' => 'error','icon' => 'thumbs-down'));
				// report error $res->error
			} else if(isset($res->success)) {
	
			}
		} else {
			$wpsa_notifications->add("Swift A/B Test Error 2", __('There was a problem pausing your post. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
			// report error  $res->error
		}
		if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Ad Debug", "<pre>Request Query =>\n " . var_export($request, true) . "\nPostoff Response =>\n\n" . var_export($res, true) . "</pre>",array('status' => 'debug','icon' => 'hammer'));
	
	}
	
	
	if ($action == 'pause') {
		$test['status'] = "paused";
		$ad_status = "abtest";
	} else if ($action == 'resume') {
		$test['status'] = "running";
		$ad_status = "abtest";
	} 
	
	


	if(!($wpdb->replace( "{$wpdb->prefix}swiftad_abtest", $test))) {
		$wpsa_notifications->add("Swift Ad A/B Test DB Update Error", __( 'There was a problem updating the a/b test. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com' . $wpdb->print_error()),array('status' => 'error','icon' => 'thumbs-down'));
		
	} else {
		$wpdb->get_results("UPDATE `".$wpdb->prefix."swiftad_displayad` SET `status` = '$ad_status' WHERE `postid` IN (".$test['postid_a']." , ".$test['postid_b'].")",  ARRAY_A,0);
		$wpsa_notifications->add("Swift Ad A/B Test Updated", __( 'Swift Ad A/B Test Updated, status set to '.$action."d"),array('status' => 'success','icon' => 'thumbs-up'));
	}
}



