<?php
/*
Plugin Name: SwiftAd
Plugin URI: https://swiftimpressions.com
Author: Swiftimpressions
Author URI: https://swiftimpressions.com
Description: Swift Display Ads with geo targeting and per user frequency capping
Version: 0.5.4
License: GPLv2
*/

/* 
 *  COPYRIGHT AND TRADEMARK NOTICE
 *  Copyright (C) 2015 Swift Impressions. All Rights Reserved.
 *  Swift Impressions is a subsidiary of Blog Nirvana.
 *
 *  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
 *  By using this code you agree to indemnify Blog Nirvana and its subsidiaries from any
 *  liability that might arise from it's use. 
 *  
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License or (at your option) any later version.
 *  
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */


/*
 * swift Post constants
 */
  //None Yet
 
/*
 * Load Setup  
 */
include_once(plugin_dir_path( __FILE__ ) . 'swiftad-setup.php');

/*
 * Load config 
 */
load_plugin_textdomain('swiftad', false, basename(dirname(__FILE__)) . '/language');
$swiftad_config		= get_option('swiftad_config');
$swiftad_license	= get_option('swiftad_license');
$swiftad_version	= get_option("swiftad_version");

/*
 * Core Hooks/Actions
 *
 */
register_activation_hook(__FILE__, 'swiftad_activate');
register_deactivation_hook(__FILE__, 'swiftad_deactivate');
register_uninstall_hook(__FILE__, 'swiftad_uninstall');
add_action('swiftad_tasks_daily', 'swiftad_exec_daily');

/*
 * Daily cleanup tasks
 * 
 *  
 */ 
function swiftad_exec_daily() {
	global $wpdb;
	$auto_expire_ad   = $wpdb->query("UPDATE {$wpdb->prefix}swiftad_displayad SET `status` = \"expired\" WHERE `enddate` < CURDATE()");
	$auto_expire_test = $wpdb->query("UPDATE {$wpdb->prefix}swiftad_abtest SET `status` = \"completed\" WHERE `enddate` < CURDATE()");
}

if(!is_admin()) {
	include_once(plugin_dir_path( __FILE__ ) . 'swiftad-functions.php');
	if (isset($swiftad_config['shortcode_in_widgets']) && $swiftad_config['shortcode_in_widgets'] == 'on') add_filter('widget_text', 'do_shortcode');
	/* Swift Post Inject */
	add_shortcode('swiftad_adslot', 'swiftad_shortcode');
	add_action('wp_footer', 'swiftad_display_inject');
	add_action( 'wp_enqueue_scripts', 'swiftad_scripts');
	add_filter('widget_text', 'do_shortcode');
	if (isset($swiftad_config['autoinsert']) && $swiftad_config['autoinsert'] == "yes") add_action( 'loop_start' , 'swiftimpressoins_autoinsert' );
	/*
	 *  Slot Fill
	 */
	add_shortcode('swiftad', 'swiftad_shortcode');
}

// Register Custom Post Type
function swiftad_post_type() {

	$labels = array(
		'name'                  => _x( 'Swift Display Ads', 'Post Type General Name', 'swiftad' ),
		'singular_name'         => _x( 'Swift Display Ad', 'Post Type Singular Name', 'swiftad' ),
		'menu_name'             => __( 'Swift Ad', 'swiftad' ),
		'name_admin_bar'        => __( 'Swift Ad', 'swiftad' ),
		'archives'              => __( 'Swift Display Ad Archives', 'swiftad' ),
		'parent_item_colon'     => __( ':', 'swiftad' ),
		'all_items'             => __( 'Swift Display Ads', 'swiftad' ),
		'add_new_item'          => __( 'New Ad', 'swiftad' ),
		'add_new'               => __( 'New Ad', 'swiftad' ),
		'new_item'              => __( 'New Swift Display Ad', 'swiftad' ),
		'edit_item'             => __( 'Edit Swift Display Ad', 'swiftad' ),
		'update_item'           => __( 'Update Swift Display Ad', 'swiftad' ),
		'view_item'             => __( 'View Swift Display Ad', 'swiftad' ),
		'search_items'          => __( 'Search Swift Display Ad', 'swiftad' ),
		'not_found'             => __( 'Not found', 'swiftad' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'swiftad' ),
		'featured_image'        => __( 'Featured Image', 'swiftad' ),
		'set_featured_image'    => __( 'Set featured image', 'swiftad' ),
		'remove_featured_image' => __( 'Remove featured image', 'swiftad' ),
		'use_featured_image'    => __( 'Use as featured image', 'swiftad' ),
		'insert_into_item'      => __( 'Insert into item', 'swiftad' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'swiftad' ),
		'items_list'            => __( 'Items list', 'swiftad' ),
		'items_list_navigation' => __( 'Items list navigation', 'swiftad' ),
		'filter_items_list'     => __( 'Filter items list', 'swiftad' ),
	);
	$args = array(
		'label'                 => __( 'Swift Display Ad', 'swiftad' ),
		'description'           => __( 'Swift Display Ad', 'swiftad' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'revisions'),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => "swiftad",
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => false,
		'has_archive'           => true,		
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'rewrite'               => false,
		'capability_type'       => 'page',
		'map_meta_cap'			=> true,
		'menu_position'			=> 80
	);
	register_post_type( 'swiftad_post_type', $args );

}


add_filter( 'manage_swiftad_post_type_posts_columns', 'set_custom_edit_swiftad_post_type_columns' );
add_action( 'manage_swiftad_post_type_posts_custom_column' , 'custom_swiftad_post_type_column', 10, 2 );

function set_custom_edit_swiftad_post_type_columns($columns) {
	unset($columns['date']);
    $columns['ad_slot'] = __( 'Target Ad Slot', 'swiftad' );
    $columns['start_date'] = __( 'Ad Start', 'swiftad' );
    $columns['end_date'] = __( 'Ad End', 'swiftad' );
    return $columns;
}

function custom_swiftad_post_type_column( $column, $post_id ) {
    global $wpdb;
    switch ( $column ) {
        case 'ad_slot' :
			if ($size = get_post_meta($post_id, "swiftad_size")) {
				$type = get_post_meta($post_id, "swiftad_target_type");
 				echo  (($name = get_post_meta($post_id, "swiftad_target_name")) ? $name[0] : "" ). " (" .$size[0] . " | " . $type[0].")";
			} else {
                echo "none";
            }
            break;
        case 'start_date' :
        	$start = $wpdb->get_row( "SELECT startdate, starttime FROM {$wpdb->prefix}swiftad_displayad WHERE postid = $post_id", ARRAY_A);
        	$date = new DateTime($start['startdate'] . " " .  date("H:i:s",strtotime($start['starttime'])));
        	echo $date->format( 'm/d/y g:iA');
        	break;
         case 'end_date' :
        	$end = $wpdb->get_row( "SELECT enddate, endtime FROM {$wpdb->prefix}swiftad_displayad WHERE postid = $post_id", ARRAY_A );
        	$date = new DateTime($end['enddate'] . " " .  date("H:i:s",strtotime($end['endtime'])));
        	echo $date->format( 'm/d/y g:iA');
        	break;
    }
}



function swiftad_admin_css() {
		global $post_type; 
		if ((isset($_GET['post_type']) && $_GET['post_type'] == 'swiftad_post_type') || ($post_type == 'swiftad_post_type')) 		
			echo "<link type='text/css' rel='stylesheet' href='" . plugins_url('/admin/css/swift_header.css', __FILE__) . "' />";
}

function swiftad_edit_php_header() {
	global $post_type; 
	if ((isset($_GET['post_type']) && $_GET['post_type'] == 'swiftad_post_type') || ($post_type == 'swiftad_post_type')) 		
		echo "<div id=\"swiftad-admin-header\" class=\"swiftad-admin-wrap\"><div class=\"swiftad-admin-logo-bar\" ><div class=\"swiftad-admin-logo\" ></div><div class=\"swiftad-admin-title-buttons\" ><a class=\"swiftad-btn-rainbow\" href=\"" . site_url("/wp-admin/admin.php?page=swiftad-settings") . "\">Settings</a><a class=\"swiftad-btn-rainbow\" href=\"https://swiftimpressions.com/useful-resources/\" target=\"_blank\" >Help</a></div></div></div>";		

}

/*
 * Dashboard
 */
if(is_admin()) {
	add_action('admin_menu', 'swiftad_dashboard', 2);
	add_action( 'init', 'swiftad_post_type', 0 );
	include_once( plugin_dir_path( __FILE__ ) . 'admin/swiftad_admin_functions.php');
	include_once(plugin_dir_path( __FILE__ ) . 'admin/wpsa-notifications.php');
	swiftad_check_config();
	add_action('save_post', 'swiftad_save_meta_box_data', 10, 3);
	add_action('save_post', 'save_swiftad_fileds');
	add_action('transition_post_status','swift_ad_transition',10,3);
	
	add_action('admin_enqueue_scripts', 'swiftad_admin_scripts' );
	add_action('wp_logout', 'swiftadEndSession');
	add_action('wp_login', 'swiftadStartSession');
	add_action('admin_notices', 'swiftad_admin_notices');
    /* Setup Debug */
    if ($swiftad_config["debug"] == "on") {
    	
    	
    }
	/*--- Internal redirects ------------------------------------*/
	if(isset($_POST['swiftad_register_license'])) add_action('init', 'swiftad_license_apply');
	if(isset($_POST['swiftad_release_license']))  add_action('init', 'swiftad_license_release');
	
	global $wpsa_notifications;
	
	add_action('admin_head', 'swiftad_admin_css');
	add_action('in_admin_header', 'swiftad_edit_php_header');

}

/*
 * Swift Impressions Dashboard Menus & pages
 */
function swiftad_dashboard() {

	add_menu_page('Swift Ad', 'Swift Ad', 'swiftad_pp_manage', 'swiftad', 'swiftad_info', '','25.7');
	add_submenu_page('swiftad', 'Swift Ad > '.__('Dashboard', 'swiftad'), __('Dashboard', 'swiftad'), 'swiftad_pp_manage', 'swiftad', 'swiftad_info');
	add_submenu_page('swiftad', 'Swift Ad > '.__('Reports', 'swiftad'), __('Reports', 'swiftad'), 'swiftad_pp_manage', 'swiftad-displayads', 'swiftad_displayads');	
	add_submenu_page('swiftad', 'Swift Ad > '.__('Inventory', 'swiftad'), __('Inventory', 'swiftad'), 'swiftad_pp_manage', 'swiftad-inventory', 'swiftad_inventory');
	add_submenu_page('swiftad', 'Swift Ad > '.__('Split Testing', 'swiftad'),  __('Split Testing', 'swiftad'), 'swiftad_pp_manage', 'swiftad-abtest', 'swiftad_abtest');
	add_submenu_page('swiftad', 'Swift Ad > '.__('Plugin Setup', 'swiftad'), __('Plugin Setup', 'swiftad'), 'swiftad_pp_manage', 'swiftad-settings', 'swiftad_settings');

}

/*
 * Swift Impressions Info Page
 */
function swiftad_info() {
	global $wpdb;
	$swiftad_config	= get_option('swiftad_config');
	$swiftpost_license	= get_option('swiftpost_license');
	
	if ((!isset($swiftpost_license['server_key']) || (isset($swiftpost_license['server_key']) && $swiftpost_license['server_key'] == "")) && isset($_POST['swiftad-accept-terms']) ) swiftad_license_activate();
	?>
	<div class="swiftad-admin-wrap">
	  
	  <div class="swiftad-admin-logo-bar" >
	  	 <div class="swiftad-admin-logo" ></div>
		<div class="swiftad-admin-title-buttons" >
		 <a class="swiftad-btn-rainbow" href="<?php echo site_url("/wp-admin/admin.php?page=swiftad-settings"); ?>">Settings</a>
		 <a class="swiftad-btn-rainbow" href="https://swiftimpressions.com/useful-resources/" target="_blank" >Help</a>
		</div>
	  </div>
		
		<?php 
		
		if ( !swiftad_license_check()) do_action('wpfn_notifications');  
		$status = $file = $view = $ad_edit_id = '';
		$license = get_option('swiftpost_license');
		
		if (!isset($license['level'])) {
			$license['level'] = "free";
			update_option('swiftpost_license', $license);
		}
		$plan_count = array('free' => 10000, '1333' => 100000, '1334' => 300000, '1335' => "unlimited");
	    $plan_level = array('free' => "Free Plan", '1333' => "Independent Operators", '1334' => "High Volume Niche", '1335' => "Industry Leader/Promoter");
		
	
		
		?>
		<div class="swiftad-admin-box">
			<div class="swiftad-admin-box-title-bar rs-status-red-wrap">
				<div class="swiftad-admin-box-title">General Information</div>
				
				
				<div class="clear"></div>
			</div>
			
			<div class="swiftad-admin-box-inner ">
				<h2 class="swift-box-title">Upgrading License</h2>
				<ol>
					<li>Go to <a href="http://SwiftImpressions.com" target="_blank">Swiftimpressions.com</a> and register for an account.</li>
					<li>Once registered and logged in, go to the Account page where you can find your license key and the updgade links.</li>
					<li>Choose to upgrade and then select one of the levels to purchase that subscription.</li>
					<li>Return to this setup page, release your current license if you've activated and enter your new license key in the activation box.</li>
					

				</li></ol>
				<h2 class="swift-box-title">Setup</h2>
				<ul>
				<li>Setup steps can be found on the on the Plugin Setup page of this plugin, (<a href="<?php echo site_url( "/wp-admin/admin.php?page=swiftad-settings"); ?>">click here</a>)
				</ul>
				<h2 class="swiftad-box-title">Customer Support Information</h2>
				
				<p>Please call us at (208) 473-7119 any time, day or night. We are more likely to answer between the hours of 9am and 5pm MST… Just an FYI.</p>

				<p>You can also email us at njones@swiftimpression.com if you are a millennial or don't like talking on the phone. We usually get back to you in under 30 minutes.</span>
				
				<p>Here are some helpful walkthroughs:</p>
				<ul>
				<li><a href="https://swiftimpressions.com/swift-ad-walkthrough/"><b>Swift Ad Installation Guide</b></a></li>
				<li><a href="https://swiftimpressions.com/swift-ad-widget/"><b>Swift Ad Widget Explainer</b></a></li>
				<li><a href="https://swiftimpressions.com/creating-a-swift-ad/"><b>Basics of Creating a Swift Ad</b></a></li>
				</ul>				
			</div>
		</div>
		
		
		<div class="swiftad-admin-box">
			<div class="swiftad-admin-box-title-bar rs-status-red-wrap">
				<div class="swiftad-admin-box-title">Plugin Activation</div>
				
				<?php if (isset($license['server_key']) && $license['server_key'] != ""): ?>
					<div class="swiftad-admin-green btn">Plugin Activated</div>
				<?php else: ?>
					<div class="swiftad-admin-red btn">Not Activated</div>
				<?php endif; ?>
				<div class="clear"></div>
			</div>
			
			<div class="swiftad-admin-box-inner ">
				<?php if (isset($license['server_key']) && $license['server_key'] != ""): ?>
					<table>
						<tr><td><label>License Key:</label> </td><td><?php echo $license['license_key']; ?> </td></tr>
						<tr><td><label>Subscription: </label> </td><td><?php echo $plan_level[$license['level']] ; ?> </td></tr>
						<tr><td><label>Monthly Impressions:</label> </td><td><?php echo $plan_count[$license['level']] . " impressions can be booked to start each month."; ?> </td></tr>
						<tr><td><label>Registered by:</label> </td><td><?php echo $license['reg_user']; ?> </td></tr>
						<tr><td><label>Registered on: </label></td><td><?php echo $license['reg_date']; ?> </td></tr>
						<tr><td valign="top"><label>To release your license:</label></td><td> Copy and paste the license key above into the box below and submit. Once released ads will quit serving and you can register you license on another blog.</td></tr>
					</table>
					<form name="unregister_license" id="post" method="post">
					    <input name="license_key" type=text />
			   			<input type=submit  class="swiftad-btn-rainbow" name="swiftad_release_license" value="Release" />
					    </form>
					  
					<?php else: ?>
					<p>
				    <h4>Press activate below to get 10K free impressions per month:</h4>
				    <a href="#" onclick="javascript: swiftadactivatefree(); return false;" class="swiftad-btn-rainbow">Activate</a>
				    </p>
				     <p></p><br />
				    <h4>Or, enter a valid premium License Key from <a href="//swiftimpressions.com" >SwiftImpressions.com</a> to register the plugin</h4>
				    <form name="register_license" id="post" method="post">
				    
				    <input name="license_key" type=text />
				    <input type=submit class="swiftad-btn-rainbow" name="swiftad_register_license" value="Register" />
				    
				    </form>
				   
				    <i>*When registering your plugin, we will store your username, site url and the site email associated with your license on our licensing server in order to confirm your identity.</i>				<?php endif; ?>
			</div>
		</div>
				
		<?php
			/* Check Bookings */
			date_default_timezone_set ( "America/New_York" );
			$request = array("impressions" => 0, "startdate" => date("Y-m-d H:i:s"));
			$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
			// Update Ad Server
			$url ="http://api.swiftimpressions.com/licensecount";
			$reply = wp_safe_remote_post($url, $post);
			$response  = wp_remote_retrieve_body($reply);
			
			if($res = json_decode($response)) {
			
				$current_count = $res->monthlycount->count;
				if ($res->licenselevel == 1335) {
					$available = "unlimited";
					$mark = "<div class=\"swiftad-admin-green btn\">&#10003;</div>";
				} else {
					$available = $plan_count[$res->licenselevel]-$current_count;
					if ($available > 0) {
						$mark = "<div class=\"swiftad-admin-green btn\">&#10003;</div>";
					} else {
						$mark = "<div class=\"swiftad-admin-red btn\">&#x274C;</div>";
					}
				}
		?>
		
		<div class="swiftad-admin-box">
			<div class="swiftad-admin-box-title-bar rs-status-red-wrap">
				<div class="swiftad-admin-box-title">Current Bookings</div>
				<?php echo $mark ?>
				<div class="clear"></div>
			</div>
			
			<div class="swiftad-admin-box-inner ">
				
				<p>
				<table>
					<tr><td valign="top"><label>For this month:</label> </td><td><?php echo $res->monthlycount->month . ", " . $res->monthlycount->year; ?> </td></tr>
					<tr><td valign="top"><label>Subscription: </label> </td><td><?php echo $plan_level[$license['level']] ; ?> </td></tr>
					<tr><td valign="top"><label>Booked this month:</label> </td><td><?php echo $current_count;  ?> Impressions</td></tr>
					<tr><td valign="top"><label>Available to Book:</label> </td><td><?php echo $available; ?> Impressions</td></tr>
				</table>
				</p>
			</div>
		</div>	
		<?php } ?>
		

		<br class="clear" />
     

</div>
<?php
}


/*
 * Swift Ad Stats Page
 */
function swiftad_displayads() {
	global $wpdb, $swiftad_config, $wpsa_notifications;
	$license = get_option('swiftpost_license');
	$active_tab = (isset($_GET['tab'])) ? esc_attr($_GET['tab']) : 'active';

	
	if(isset($_POST['postid']) && isset($_POST['action']) ) {
		
		// Check if our nonce is set.
		if ( ! isset( $_POST['swiftad_post_page_nonce'] ) || ! wp_verify_nonce( $_POST['swiftad_post_page_nonce'], 'swiftad_post_page' ) ) {
			
		} else {
			$result = swiftad_postmanage( $_POST['action'], $_POST['postid'],  $_POST['run']); 			
			
		}
	}
	?>


	<div class="swiftad-admin-wrap">
		  <div class="swiftad-admin-logo-bar" >
		  	 <div class="swiftad-admin-logo" ></div>
			<div class="swiftad-admin-title-buttons" >
			
			 <a class="swiftad-btn-rainbow" href="<?php echo site_url("/wp-admin/admin.php?page=swiftad-settings"); ?>">Settings</a>
			 <a class="swiftad-btn-rainbow" href="https://swiftimpressions.com/useful-resources/" target="_blank" >Help</a>
			</div>
		  </div>
			
		  <h2 class="nav-tab-wrapper">  
	          <a href="?page=swiftad-displayads&tab=active" class="nav-tab <?php echo $active_tab == 'active' ? 'nav-tab-active' : ''; ?>">Active Ads</a>  
	          <a href="?page=swiftad-displayads&tab=paused" class="nav-tab <?php echo $active_tab == 'paused' ? 'nav-tab-active' : ''; ?>">Paused ads</a>  
	          <a href="?page=swiftad-displayads&tab=expired" class="nav-tab <?php echo $active_tab == 'expired' ? 'nav-tab-active' : ''; ?>">Expired Ads</a>
	      </h2>
		
		<div id="swiftad-admin-block" class="swiftad-admin-box-tabs">
			
		    <div class="swiftad-admin-box-inner ">
	
	
	<?php
	
	if($active_tab == 'active') {
		$filter = "WHERE status = \"live\"";
		$action = "pause";
		$icon = "dashicons-controls-pause";
	} else if ($active_tab == 'paused' ) {
		$filter = "WHERE status = \"paused\"";
		$action = "resume";
		$icon = "dashicons-controls-play";
	} else if ($active_tab == 'expired' ) {
		$filter = "WHERE status = \"expired\"";
		$action = "new run";
		$icon = "dashicons-format-gallery";
	}

	
	
	$offset = 10;
	$paged = isset($_GET['paged']) ? $_GET['paged'] : "0";
	
	$postids = $wpdb->get_col("SELECT postid FROM {$wpdb->prefix}swiftad_displayad " .$filter ." ORDER BY postid DESC LIMIT $offset OFFSET $paged");
	
	if ( $postids ) {
		
		$data = array(
			"postids" => $postids,
			"swiftad" => true
			);
		$post = array("timeout" => 200, "body" => array("request" => $data, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
	
		// Get Post Stats
		$url ="http://api.swiftimpressions.com/poststats";
		$reply 	   = wp_safe_remote_post($url, $post);
		$response  = json_decode(wp_remote_retrieve_body($reply));
		
		if ($swiftad_config['debug'] == 'on') $wpsa_notifications->add("Swift Post Debug", "<pre>Stats Query =>\n\n\ " . var_export($post, true) .  "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
		do_action('wpsa_notifications');

		$args['post_type'] = 'swiftad_post_type';
		$args['post__in'] = $postids;
		$args['posts_per_page'] = $offset;
		$args['orderby'] = "ID";
		$args['ignore_sticky_posts'] = true;
		$get_sposts  = new WP_Query($args);
		
		

		if ( $get_sposts->have_posts() ) {
			
			echo "<form name=\"post-manage\" id=\"post-manage\" method=\"post\">\n<input type=\"hidden\" name=\"postid\" id=\"post-postid\" value=\"\" />\n<input type=\"hidden\" name=\"action\" id=\"post-action\" value=\"$action\" />\n<input type=\"hidden\" name=\"run\" id=\"post-run\" value=\"\" />";
			wp_nonce_field( 'swiftad_post_page', 'swiftad_post_page_nonce' );
			
			echo "<div id= \"swift-admin-post-wrqp\" class=\"swift-rainbow-box\">";
			
			while ( $get_sposts->have_posts() ) {
				$get_sposts->the_post();
				$id = get_the_ID();
				
				$runs = "";
				
				foreach ($response->$id as $run => $stats) {
					if (!isset($stats->stats) ) $stats->stats = new stdClass();
					if (!isset($stats->stats->impressionsDelivered)) $stats->stats->impressionsDelivered = 0;
					if (!isset($stats->stats->clicksDelivered)) $stats->stats->clicksDelivered = 0;
					
					$runs .= "<tr><td>Run ". $run." </td>\n";
					$runs .=  "<td>" . $stats->start->date->month . " / " . $stats->start->date->day . " / " . $stats->start->date->year . " - "; 
					$runs .=  $stats->end->date->month . " / " . $stats->end->date->day . " / " . $stats->end->date->year . "</td>\n";
					$runs .=  "<td>" . $stats->stats->impressionsDelivered . "</td>\n";
					$runs .=  "<td>" . $stats->stats->clicksDelivered . "</td>\n";
					$runs .=  "<td>". ($stats->stats->impressionsDelivered > 0 ? round(($stats->stats->clicksDelivered/$stats->stats->impressionsDelivered)*100, 2) : "0"). "</td></tr>\n";
				}
				
				
				echo "<div class=\"swift-item-wrap\"><div class=\"swift-post-report-titlebar\"><div class=\"swift-post-report-title\">" . the_title( '<span>', '</span>' , FALSE) . " </div>";
				echo "<div class=\"swift-post-report-buttons\">";
				echo "<a href=\"" .site_url( "/wp-admin/post.php?post=" . $id . "&amp;action=edit" ) . "\" title=\"Edit this item\"class=\"swift-btn-rainbow swift-tooltip swift-icon-button\" ><span class=\"dashicons dashicons-edit\"></span></a>";
				echo "<a href=\"" .site_url( '?swift_preview='. $id ) . "\" target=\"_blank\" class=\"swift-btn-rainbow swift-tooltip swift-icon-button\" title=\"Preview Ad\"><span class=\"dashicons dashicons-welcome-view-site\"></span></a>\n";
				echo "<button class=\"swift-tooltip swift-icon-button swift-btn-rainbow ad-manage\" title=\"$action ad\" type=\"button\" value=\"$id\" run=\"$run\" id=\"submit-$id\"><span class=\"dashicons $icon\"></span></button>";
				echo "</div></div>\n";
				echo "<div class=\"swift-admin-table-wrap\">\n";
				echo "<table class=\"table swift-stat-table\" cellspacing=\"0\"><tr><th>Run #</th><th>Dates</th><th>Impressions</th><th>Clicks</th><th>CTR (%)</th></tr>";
				
				echo $runs;
				echo "</table></div></div>\n";
			}
			echo "</form>";

		} else {
			echo "<!--no ads-->";
		}
		
		
		$page_url = "?page=swiftad-displayads&tab=$active_tab&paged=";
		
		$prev = ($paged == 0 ? "" : "<a class=\"swift-btn-rainbow\" href=\"$page_url".($paged-$offset)."\">".__( '&laquo; Back', 'swiftad' )."</a>");
		$next = (count($postids) == $offset ? "<a class=\"swift-btn-rainbow\"  href=\"$page_url".($paged+$offset)."\">".__( 'Next &raquo;', 'swiftad' )."</a>" : "");
		
		?>
		<div class="page-nav">
		<div class="nav-previous alignleft"><?php echo $prev; ?></div>
		<div class="nav-next alignright"><?php echo $next; ?></div>
		</div>
		<?php
		
		echo "</div>\n";

	} else {
		echo "<h4><i>There are no Swift Ads currently $active_tab</i></h4>";
	}
	echo "</div></div>\n";

}
/*
 * Swift Ad Inventory Page
 */
function swiftad_inventory() {
	global $wpdb,$wpsa_notifications;
	$swiftad_config = get_option('swiftad_config');
	$swiftad_license = get_option('swiftpost_license');	
	
	$url=strtok($_SERVER["REQUEST_URI"],'?');
	?>
	<div class="swiftad-admin-wrap">
	  <div class="swiftad-admin-logo-bar" >
	  	 <div class="swiftad-admin-logo" ></div>
		<div class="swiftad-admin-title-buttons" >
		 <a class="swift-btn-rainbow" href="<?php echo site_url("/wp-admin/admin.php?page=swiftad-settings"); ?>">Settings</a>
		 <a class="swift-btn-rainbow" href="https://swiftimpressions.com/useful-resources/" target="_blank" >Help</a>
		</div>
	  </div>
	  <?php
   
	if ( isset($_POST['action']) ) {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'swiftad_inventory' ) ) {
		    // This nonce is not valid.
		    $wpsa_notifications->add("Swift Security Error", __('There was a security error. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		    return;
		    
		} else {
		   if ($_POST['action'] == 'insert') {
		   		$newslot = array();
		   		$newslot['name'] = $_POST['slot_name'];
		   		$newslot['description'] = $_POST['slot_description'];
		   		$newslot['size'] = $_POST['slot_size'];
		   		create_slot($newslot);
		   } else if ($_POST['action'] == 'edit') {
		   	  $where = array( 'id' => $_POST['updateslot']);
		   	  $format = array('%s', '%s');
		   	  $whereformat = array('%d');
		   	  $data['name'] = $_POST['slot_name'];
		   	  $data['description'] =$_POST['slot_description'];
		   	  if(!( $wpdb->update( "{$wpdb->prefix}swiftad_inventory", $data, $where, $format, $whereformat))) {
					$wpsa_notifications->add("Swift Post DB Update Error", __( 'There was an error updating your Swift Ad inventory slot. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
				}
		   } 
		}	
	}
	
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'trash' && wp_verify_nonce( $_REQUEST['_wpnonce'], 'swiftad_inventory' ) ) {
   	  $where = array( 'id' => $_REQUEST['adslot']);
   	  $format = array('%s');
   	  $whereformat = array('%d');
   	  $data['status'] = 'trashed';
   	  if(!( $wpdb->update( "{$wpdb->prefix}swiftad_inventory", $data, $where, $format, $whereformat))) {
			$wpsa_notifications->add("Swift Post DB Update Error", __( 'There was an error removing your Swift Ad inventory slot. Please try again. If this problem persists please contact support by phone at 208.991.4865 or by email at njones@swiftimpression.com'),array('status' => 'error','icon' => 'thumbs-down'));
		}
   }
	
	
	swiftad_admin_notices();
	
	
	$nonce = wp_create_nonce('swiftad_inventory');
	
	if(isset($_REQUEST['form']) && ($_REQUEST['form'] == 'new' || $_REQUEST['form'] =='edit' ) ) {

		if ($_REQUEST['form'] =='edit') {
			$action = "edit";
			$submitaction = "Update";
			$slot = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}swiftad_inventory WHERE id = {$_REQUEST['adslot']}" );
			$slot_size = $slot->size;
			$slot_name = $slot->name;
			$slot_description = $slot->description;
			$updateinput = "<input type=\"hidden\" name=\"updateslot\" value=\"{$_REQUEST['adslot']}\">";
			$sizeon = ' disabled="disabled" ';
			
		} else {
			$submitaction = "Add New";
			$action = "insert";
			$updateinput = "";
			$slot_size = "";
			$slot_description = "";
			$slot_name = "";
			$sizeon = ' ';
		}

?>
	<div class="swiftad-admin-box">
			<div class="swiftad-admin-box-title-bar rs-status-red-wrap">
				<div class="swiftad-admin-box-title"><?php echo $submitaction; ?> Ad Slot </div>
				
				
				<div class="clear"></div>
			</div>
			
			<div class="swiftad-admin-box-inner ">
        
	        <form id="swiftad-inventory-list" method="post" name="swiftad-inventory-list">
	            <input name="action" type="hidden" id="swiftad-inventory-form" value="<?php echo $action; ?>">
	            <input id="_wpnonce" name="_wpnonce" type="hidden" value= "<?php echo $nonce; ?>"> 
	            <input name="_wp_http_referer" type="hidden" value="<?php echo $url; ?>">
	            <?php echo $updateinput; ?>
	            <p><label>Name: </label> <input type="text" name="slot_name" value="<?php echo $slot_name; ?>"></p>
	            <p><label>Description: </label> <input type="text" name="slot_description" value="<?php echo $slot_description; ?>"></p>
	            <p><label>Size/Type: </label> 
	            <select type="text" name="slot_size" <?php echo $sizeon; ?> >
	            	<option value="970x90|Standard" <?php if( $slot_size == "970x90|Standard" ) echo " selected"; ?>>970x90 (Large Leaderboard)</option>
					<option value="728x9|Standard" <?php if( $slot_size == "728x90|Standard" ) echo " selected"; ?>>728x90 (Leaderboard)</option>
					<option value="468x60|Standard" <?php if( $slot_size == "468x60|Standard" ) echo " selected"; ?>>468x60 (Full Banner)</option>
					<option value="336x280|Standard" <?php if( $slot_size == "336x280|Standard" ) echo " selected"; ?>>336x280 (Large Rectangle)</option>
					<option value="320x50|Standard" <?php if( $slot_size == "320x50|Standard" ) echo " selected"; ?>>320x50 (Mobile Leaderboard)</option>
					<option value="300x600|Standard" <?php if( $slot_size == "300x600|Standard" ) echo " selected"; ?>>300x600 (Half Page Ad)</option>
					<option value="300x250|Standard" <?php if( $slot_size == "300x250|Standard" ) echo " selected"; ?>>300x250 (Medium Rectangle)</option>
					<option value="300x100|Standard" <?php if( $slot_size == "300x100|Standard" ) echo " selected"; ?>>300x100 (3:1 Rectangle)</option>
					<option value="250x250|Standard" <?php if( $slot_size == "250x250|Standard" ) echo " selected"; ?>>250x250 (Square)</option>
					<option value="240x400|Standard" <?php if( $slot_size == "240x400|Standard" ) echo " selected"; ?>>240x400 (Vertical Rectangle)</option>
					<option value="234x60|Standard" <?php if( $slot_size == "234x60|Standard" ) echo " selected"; ?>>234x60 (Half Banner)</option>
					<option value="200x200|Standard" <?php if( $slot_size == "200x200|Standard" ) echo " selected"; ?>>200x200 (Small Square)</option>
					<option value="180x150|Standard" <?php if( $slot_size == "180x150|Standard" ) echo " selected"; ?>>180x150 (Rectangle)</option>
					<option value="160x600|Standard" <?php if( $slot_size == "160x600|Standard" ) echo " selected"; ?>>160x600 (Wide Skyscraper)</option>
					<option value="125x125|Standard" <?php if( $slot_size == "125x12|Standard" ) echo " selected"; ?>>125x125 (Button)</option>
					<option value="120x600|Standard" <?php if( $slot_size == "120x600|Standard" ) echo " selected"; ?>>120x600 (Skyscraper)</option>
					<option value="120x240|Standard" <?php if( $slot_size == "120x240|Standard" ) echo " selected"; ?>>120x240 (Vertical Banner)</option>
					<option value="120x90|Standard" <?php if( $slot_size == "120x90|Standard" ) echo " selected"; ?>>120x90 (Button 1)</option>
					<option value="120x60|Standard" <?php if( $slot_size == "120x60|Standard" ) echo " selected"; ?>>120x60 (Button 2)</option>
					<option value="88x31|Standard" <?php if( $slot_size == "88x31|Standard" ) echo " selected"; ?>>88x31 (Micro Bar)</option>
					<option value="1x1|PopUp" <?php if( $slot_size == "1x1|PopUp" ) echo " selected"; ?>>PopUp (Custom)</option>
	            </select>
	            </p>
	             <p><input class="swift-btn-rainbow"  id="adslot-edit" type="submit" value="<?php echo $submitaction; ?>">
	       </div>
	 </div>

<?php		
		
	} else {

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}swiftad_inventory WHERE status='active' ", OBJECT );
?>	
	

        <span style="font-size: 1.6em;margin-bottom: 15px;display: inline-block;">Ad Slot Inventory</span> <a href="<?php echo $url . "?page=swiftad-inventory&form=new&_wpnonce=".$nonce ?>" class="swift-btn-rainbow">Add New</a>
        
        <form id="swiftad-inventory-list" method="post" name="swiftad-inventory-list">
            
            <input id="_wpnonce" name="_wpnonce" type="hidden" value= "<?php echo $nonce; ?>"> 
            <input name="_wp_http_referer" type="hidden" value="<?php echo $url; ?>">

            <table class="wp-list-table widefat fixed striped pages">
                <thead>
                    <tr>
                       
                        <th class="manage-column column-title column-primary"  id="title" scope="col"> <span>Title</span></th>
                        <th class="manage-column" scope="col"><span>Description</span></th>
                        <th class="manage-column" scope="col"><span>Shortcode</span></th>
                        <th class="manage-column" scope="col"><span>Size/type</span></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                
<?php
foreach ( $results as $adslot ) 
{
	$slotsize = $adslot->size;
	if (strpos($slotsize, "-")) $slotsize = "1x1";

?>
                    <tr class="iedit author-self level-0 post-2 type-page status-publish hentry"  id="post-2">
                        
                        <td class="title column-title has-row-actions column-primary page-title" data-colname="Title">
                            <strong><?php echo $adslot->name; ?></strong>

                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo $url."?page=swiftad-inventory&adslot=".$adslot->id."&form=edit&_wpnonce=".$nonce; ?>" title="Edit this item">Edit</a> |</span>
                                <span class="trash"><a class="submitdelete" href= "<?php echo $url."?page=swiftad-inventory&adslot=".$adslot->id."&action=trash&_wpnonce=".$nonce; ?>" title="Move this item to the Trash">Delete</a></span> 
                            </div>
                        </td>
                        <td class="  " data-colname="description">
                            <?php echo  $adslot->description; ?>
                        </td>
                        <td class="  " data-colname="slot-shortcode">
                            <?php echo "[swiftad_adslot size=\"" . $slotsize  . "\" slotid=\"" . $adslot->code . "\"]"; ?>
                        </td>
                       
                        <td class="date column-date" data-colname="slot-size">
	                        <span><?php echo $adslot->size; ?></span>
	                   </td>
                    </tr>
                    
<?php
}

?>                
                </tbody>
                <tfoot>
                    <tr>
                        <th class="manage-column column-title column-primary"  id="title" scope="col"><span>Title</span></th>
                        <th class="manage-column" scope="col"><span>Shortcode</span></th>
                        <th class="manage-column" scope="col"><span>Size/type</span></th>
                    </tr>
                </tfoot>
            </table>
       
        </form>
        <div id="ajax-response"></div><br class="clear">
   
    
    <?php
	
	}
	
	echo "</div>";
	
}

/*
 * Swift Ad A/B Testing
 *
 */
function swiftad_abtest() {
	global $wpdb, $swiftad_config, $wpsa_notifications;
	$license = get_option('swiftpost_license');	

	$active_tab = (isset($_GET['tab'])) ? esc_attr($_GET['tab']) : 'active';
    $url = admin_url("admin.php?page=swiftad-abtest");

	?>	
	<div class="swiftad-admin-wrap">
	  <div class="swiftad-admin-logo-bar" >
	  	 <div class="swiftad-admin-logo" ></div>
		<div class="swiftad-admin-title-buttons" >
		 <a type="button" class="swiftad-btn-rainbow" href="<?php echo $url;?>&action=new_test">New Test</a>
		 <a class="swiftad-btn-rainbow" href="<?php echo site_url("/wp-admin/admin.php?page=swiftad-settings"); ?>">Settings</a>
		 <a class="swiftad-btn-rainbow" href="https://swiftimpressions.com/useful-resources/" target="_blank" >Help</a>
		</div>
	  </div>	<?php



	if (isset($_GET['action']) && $_GET['action'] == "new_test") {
		swiftad_abtest_form();
		return;
	} else if (isset($_POST['submit']) && $_POST['submit'] == "Start Test") {
		$result = swiftad_abtest_add();
		
	} else if(isset($_POST['abtestid']) && isset($_POST['action']) ) {
		
		// Check if our nonce is set.
		if ( ! isset( $_POST['swiftad_post_page_nonce'] ) || ! wp_verify_nonce( $_POST['swiftad_post_page_nonce'], 'swiftad_post_page' ) ) {
			
		} else {
			$result = swiftad_abtest_manage( $_POST['action'], $_POST['abtestid']); 			
		}
	}
	
	do_action('wpsa_notifications');
		
	?>
	
	
		<h2 class="nav-tab-wrapper">  
	          <a href="<?php echo $url; ?>&tab=active" class="nav-tab <?php echo $active_tab == 'active' ? 'nav-tab-active' : ''; ?>">Active tests</a>  
	          <a href="<?php echo $url; ?>&tab=paused" class="nav-tab <?php echo $active_tab == 'paused' ? 'nav-tab-active' : ''; ?>">Paused tests</a>  
	          <a href="<?php echo $url; ?>&tab=expired" class="nav-tab <?php echo $active_tab == 'expired' ? 'nav-tab-active' : ''; ?>">Expired tests</a>
	      </h2>
		
		<div class="swiftad-admin-box-tabs">
			
		    <div class="swiftad-admin-box-inner ">
	
		
		
		<?php
		if($active_tab == 'active') {
			$filter = "WHERE status = \"running\"";
			$action = "pause";
			$icon = "dashicons-controls-pause";
		} else if ($active_tab == 'paused' ) {
			$filter = "WHERE status = \"paused\"";
			$action = "resume";
			$icon = "dashicons-controls-play";
		} else if ($active_tab == 'expired' ) {
			$filter = "WHERE status = \"expired\"";
			$action = "new test";
		}
		
		$offset = 5;
		$paged = isset($_GET['paged']) ? $_GET['paged'] : "0";
		
		$tests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}swiftad_abtest " .$filter ." ORDER BY startdate DESC", ARRAY_A);
		
		$postids = array();
		foreach ($tests as $row) {
			$postids[] = $row['postid_a'];
			$postids[] = $row['postid_b'];
		}
	
		
		if (!empty($postids) ) {
			
			$data = array(
				"postids" => $postids,
				"swiftad" => true
				);
			$post = array("timeout" => 200, "body" => array("request" => $data, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
		
			// Get Post Stats
			$api_url	="http://api.swiftimpressions.com/poststats";
			$reply 	   = wp_safe_remote_post($api_url, $post);
			$response  = json_decode(wp_remote_retrieve_body($reply));
			
			if ($swiftad_config['debug'] == 'on') {
				$wpsa_notifications->add("Swift Ad A/B Test Debug", "<pre>\nPOST =>\n  " . var_export($_POST, true) .  " \n\nA/B TEST Query =>\n\n\ " . var_export($data, true) .  "\n\nreply->\n\n" . var_export($reply,true)."</pre>",array('status' => 'debug','icon' => 'hammer'));
			    do_action('wpsa_notifications');
			}
			
			echo "<div id=\"swiftpost-admin-block\" >";
		    
		    if (!isset($response->error) && !isset($response->code)) {
				
				echo "<form name=\"abtest\" id=\"abtest-form\" method=\"post\" action=\"$url\">\n<input type=\"hidden\" name=\"abtestid\" id=\"abtestid\" value=\"\" />\n<input type=\"hidden\" name=\"action\" id=\"post-action\" value=\"$action\" />\n";
				wp_nonce_field( 'swiftad_post_page', 'swiftad_post_page_nonce' );
				foreach ( $tests as $row) {
					if (!($stats_a = $response->$row['postid_a']->$row['run_a']->stats)) {
					    $stats_a = new stdClass();
						$stats_a->impressionsDelivered = 0;
						$stats_a->clicksDelivered = 0;
					}
					if (!($stats_b = $response->$row['postid_b']->$row['run_b']->stats)) {
					    $stats_b = new stdClass();
						$stats_b->impressionsDelivered = 0;
						$stats_b->clicksDelivered = 0;
					}
					
					$ctr_a = ($stats_a->impressionsDelivered == 0) ? 0 : round(  ($stats_a->clicksDelivered/$stats_a->impressionsDelivered), 4) * 100;
					$ctr_b = ($stats_b->impressionsDelivered == 0) ? 0 : round(  ($stats_b->clicksDelivered/$stats_b->impressionsDelivered), 4) * 100;
					$a_result = "";
					$b_result = "";
					$id = $row['id'];
					if ($ctr_a > $ctr_b) {
						$a_result = "<span class=\"dashicons dashicons-yes\" style=\"color: green;\"></span> ". round((($ctr_a - $ctr_b)/ $ctr_b) * 100, 2) . "% increase";
					} else if ($ctr_b > $ctr_a) {
						$b_result = "<span class=\"dashicons dashicons-yes\" style=\"color: green;\"></span> ". round((($ctr_b - $ctr_a)/ $ctr_a) * 100, 2) . "% increase";
					}
					?>
					
					<div class="ab-test-item">
					<div class="swiftad-abtest-info">
					<h3><?php echo $row['name']; ?></h3>
					<div><h3>Runs: <i><?php echo $row['startdate'] . " " . $row['starttime']; ?> </i> through <i><?php echo $row['enddate'] . " " . $row['endtime']; ?> </i></h3></div>
					<div><h3>Inventory Slot: <i><?php echo $row['inventory']; ?> </i></h3></div>
					</div>
					<div class="swiftad-abtest-buttons">
				 		<button type="button" value="<?php echo $id; ?>" id="submit-<?php echo $id; ?>"><span class="dashicons <?php echo $icon; ?>"></span></button>
					</div>
					
					<table class="table swift-stat-table" cellspacing="0"><tbody><tr><th>Test - Title</th><th>Impressions</th><th>Clicks</th><th>CTR</th><th></th></tr>
					<tr><td><?php echo get_the_title($row['postid_a']); ?></td><td><?php echo $stats_a->impressionsDelivered ?><td><?php echo $stats_a->clicksDelivered; ?></td><td><?php echo $ctr_a; ?></td><td><?php echo $a_result; ?></td></tr>
					<tr><td><?php echo get_the_title($row['postid_b']); ?> </td><td><?php echo $stats_b->impressionsDelivered ?><td><?php echo $stats_b->clicksDelivered; ?></td><td><?php echo $ctr_b; ?></td><td><?php echo $a_result; ?></td></tr>
					</tbody></table>
					
					</div>
					
					
					<?php
					
				}					
	
			}
			echo "</form>";
			
			$page_url = "?page=swiftpost_abtestt&tab=$active_tab&paged=";
			
			$prev = ($paged == 0 ? "" : "<a href=\"$page_url".($paged-$offset)."\">".__( '&laquo; Back', 'swiftpost' )."</a>");
			$next = (count($postids) == $offset ? "<a href=\"$page_url".($paged+$offset)."\">".__( 'Next &raquo;', 'swiftpost' )."</a>" : "");
			echo "<div class=\"page-nav nav-previous alignleft\">$prev</div>";
			echo "<div class=\"page-nav nav-next alignright\">$next</div>";
			echo "</div>\n";
	
		} else {
				echo "<!--no tests-->";
				echo "<h4><i>There are no Swift Ads A/B Tests currently $active_tab</i></h4>";
		}

	
	echo "</div></div>\n";
} 

/*
 * Swift Ad Settings Page
 *
 */
function swiftad_settings() {
	global $wpdb, $wp_roles;
	$swiftad_config = get_option('swiftad_config');
	$swiftad_license = get_option('swiftad_license');
    
    if (isset($_POST['swiftad_settings_apply'])) {
    	global $wpsa_notifications;
    	if ( isset( $_POST['swiftad_settings_page_nonce'] ) && wp_verify_nonce( $_POST['swiftad_settings_page_nonce'], 'swiftad_settings_page' )) {
   		
    		if (isset($_POST['swiftad-debug'])) {
    			$swiftad_config['debug'] = "on";
    		} else {
    			$swiftad_config['debug'] = "off";
    		}
    		
    		if (isset($_POST['swiftad-responsive'])) {
    			$swiftad_config['responsive'] = "on";
    		} else {
    			$swiftad_config['responsive'] = "off";
    		}
    		
    		if (isset($_POST['shortcode_in_widgets'])) {
    			$swiftad_config['shortcode_in_widgets'] = "on";
    		} else {
    			$swiftad_config['shortcode_in_widgets'] = "off";
    		}
    		if (isset($_POST['serve-asap'])) {
    			$swiftad_config['serve-asap'] = "on";
    		} else {
    			$swiftad_config['serve-asap'] = "off";
    		}
    		
    		
			update_option('swiftad_config', $swiftad_config);
			
			$wpsa_notifications->add("Settings Applied", __('Swift Ad Settings Updated' ),array('status' => 'success','icon' => 'thumbs-up'));
		} else {
			$wpsa_notifications->add("Settings Form Error", __('Invalid Swift Ad Settings' ),array('status' => 'error','icon' => 'thumbs-down'));
			
		}
    	do_action('wpsa_notifications');
    }
	

	?>

<div class="swiftad-admin-wrap">
	  <div class="swiftad-admin-logo-bar" >
	  	 <div class="swiftad-admin-logo" ></div>
		<div class="swiftad-admin-title-buttons" >
		 <a class="swiftad-btn-rainbow" href="<?php echo site_url("/wp-admin/admin.php?page=swiftad-settings"); ?>">Settings</a>
		 <a class="swiftad-btn-rainbow" href="https://swiftimpressions.com/useful-resources/" target="_blank" >Help</a>
		</div>
	  </div>
		<?php if(isset($status) && $status > 0) swiftad_status($status, array('error' => $error)); ?>
	

	  	<form name="settings" id="post" method="post" action="admin.php?page=swiftad-settings">
	  		<?php wp_nonce_field( 'swiftad_settings_page', 'swiftad_settings_page_nonce' ); ?>
			
	<div class="swiftad-admin-box-fw">
			<div class="swiftad-admin-box-title-bar rs-status-red-wrap">
				<div class="swiftad-admin-box-title">Update Settings</div>

				<div class="clear"></div>
			</div>
			
			<div class="swift-admin-box-inner ">
				<h2 class="swiftad-box-title">Responsive Ads</h2>
			
				<p><label><input name="swiftad-responsive" <?php echo ($swiftad_config['responsive'] == "on" ? "checked" : ""); ?> type="checkbox" size=120> Enable responsive ad slots, images will resize</label></p>
				
				<h2 class="swiftad-box-title">Enable Shortcodes in Text Widget</h2>
				<p><label ><input name="shortcode_in_widgets" <?php echo ($swiftad_config['shortcode_in_widgets'] == "on" ? "checked" : ""); ?> type="checkbox" size=120> When checked you can put shortcodes for ad slots directly in text widgets</label></p>
				
				<h2 class="swiftad-box-title">Server Ads as soon as possible</h2>
				<p><label><input name="serve-asap" <?php echo ($swiftad_config['serve-asap'] == "on" ? "checked" : ""); ?> type="checkbox" size=120> <label for"serve-asap"> When checked ads will server as soon as possible, otherwise serves will be spread out over the time period meaning sometimes the spot will be blank.</label></p>
				
				<h2 class="swiftad-box-title">Enable Debugging</h2>
				<p><label><input name="swiftad-debug" <?php echo ($swiftad_config['debug'] == "on" ? "checked" : ""); ?> type="checkbox" size=120> Turn on debugging </label></p>
	
		    <p class="submit">
		      	<input type="submit" name="swiftad_settings_apply" class="swift-btn-rainbow"  value="<?php _e('Update Settings', 'swiftad'); ?>" />
		    </p>
		</form>
	     </div>
     </div>
<?php 
}
?>
