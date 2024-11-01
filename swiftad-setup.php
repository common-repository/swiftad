<?php
/*
 *  COPYRIGHT AND TRADEMARK NOTICE
 *  Copyright 2008-2015 Blog Nirvana. All Rights Reserved.
 *  SwiftImpresions is a trademark of BlogNirvana.
 *  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
 *  By using this code you agree to indemnify Blog Nirvana from any
 *  liability that might arise from it's use.
 */

/*
 * Activate Swift Posts
 *
 */
function swiftad_activate($network_wide) {
	if(is_multisite() && $network_wide) {
		global $wpdb;
		$current_blog = $wpdb->blogid;
		$activated = array();
		$blog_ids = $wpdb->get_col("SELECT `blog_id` FROM $wpdb->blogs;");
		foreach($blog_ids as $blog_id) {
			switch_to_blog($blog_id);
			swiftad_db_config_setup();
			$activated[] = $blog_id;
		}
		switch_to_blog($current_blog);
		return;
	}
	swiftad_db_config_setup();
}

/*
 * Setup Swift Posts on Activate
 *
 */

function swiftad_db_config_setup() {
	global $wpdb, $userdata;
	if(version_compare(PHP_VERSION, '5.3.0', '<') == -1) { 
		deactivate_plugins(plugin_basename('swiftad/swiftad.php'));
		wp_die('Swift Ad requires PHP 5.3 or higher. Your server reports version '.PHP_VERSION.'. Contact your hosting provider about upgrading your server!<br /><a href="'. get_option('siteurl').'/wp-admin/plugins.php">Return to dashboard</a>.'); 
		return; 
	} else {
		if(!current_user_can('activate_plugins')) {
			deactivate_plugins(plugin_basename('swiftad/swiftad.php'));
			wp_die('You do not have appropriate access to activate this plugin! Contact your administrator!<br /><a href="'. get_option('siteurl').'/wp-admin/plugins.php">Back to dashboard</a>.'); 
			return; 
		} else {
			// Set the capabilities for the administrator
			$role = get_role('administrator');		
			$role->add_cap("swiftad_pp_manage");
			
			/* Setup Options */
			swiftad_check_config();
	
			/* Install new database */
			swiftad_database_install();
			
			/* Set Task Schedule */
			wp_schedule_event(time(), 'twicedaily', 'swiftad_tasks_daily');

		}
	}
}

/*
 * Deactivate Swift Posts
 *
 */
function swiftad_deactivate($network_wide) {
    swiftad_network_propagate('swiftad_deactivate_setup', $network_wide);
}


function swiftad_deactivate_setup() {
	global $wpdb;
	// Clean up capabilities from ALL users
    $role = get_role('administrator');		
	$role->remove_cap("swiftad_pp_manage");
	
	/* Clear Tasks */
	wp_clear_scheduled_hook('swiftad_tasks_daily');
	
	/*Pause Orders*/
	
	$license = get_option('swiftpost_license');
	$request = array("action" => "pauseall", "swiftad" => true);
	
	$post = array("timeout" => 200, "body" => array("request" => $request, "license_key" => $license['license_key'], "server_key" => $license['server_key']));
	// Update Ad Server
	$url ="http://api.swiftimpressions.com/pauseallposts";
	$reply = wp_safe_remote_post($url, $post);
	$response  = wp_remote_retrieve_body($reply);

	/* Pause Ads */

}

/*
 * Deactivate Swift Posts
 *
 */
function swiftad_network_propagate($pfunction, $network_wide) {
    global $wpdb;

    if(is_multisite() && $network_wide) {
        $current_blog = $wpdb->blogid;
        // Get all blog ids
        $blogids = $wpdb->get_col("SELECT `blog_id` FROM $wpdb->blogs;");
        foreach ($blogids as $blog_id) {
            switch_to_blog($blog_id);
            call_user_func($pfunction, $network_wide);
        }
        switch_to_blog($current_blog);
        return;
    }
    call_user_func($pfunction, $network_wide);
}


/*
 * Swift Posts Network Uninstall
 *
 */
function swiftad_uninstall($network_wide) {
    swiftad_network_propagate('swiftad_uninstall_setup', $network_wide);
}

/*
 * Swift Impressions Uninstall
 *
 */
function swiftad_uninstall_setup() {
	global $wpdb, $wp_roles;

	// Clean up roles and scheduled tasks
	swiftad_deactivate_setup();

	// Drop MySQL Tables
	$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}swiftad_displayad`");
	$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}swiftad_inventory`");
	$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}swiftad_abtest`");
	// Delete Options	
	delete_option('swiftad_config');
	delete_option('swiftad_db_version');
	delete_option('swiftad_version');
	if ( !function_exists('swiftpost_info') ) {
		delete_option('swiftpost_license');
	} 

}

/*
 * Swift Impressions check config has been created or exists
 *
 */
function swiftad_check_config() {
	$config = get_option('swiftad_config');
	$license = get_option('swiftpost_license');
	
	if($config === false || !is_array($config) || !isset($config['responsive']) ) update_option('swiftad_config', array( 'debug' => 'off', 'responsive' => 'off', 'shortcode_in_widgets'));
	if($license === false || !is_array($license) || !isset($license['server_key']) ) update_option('swiftpost_license', array('status' => 'unregistered', 'server_key' => null, 'license_key' => null, "reg_user" => "", "reg_date" => "", "parent_code" => 'no-code'));
}

/*
 * Swift Impressions install DB table
 *
 */
function swiftad_database_install() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	// Initial data
	$charset_collate = $engine = '';

	if(!empty($wpdb->charset)) {
		$charset_collate .= " DEFAULT CHARACTER SET {$wpdb->charset}";
	} 
	if($wpdb->has_cap('collation') AND !empty($wpdb->collate)) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}

	$found_engine = $wpdb->get_var("SELECT ENGINE FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '".DB_NAME."' AND `TABLE_NAME` = '{$wpdb->prefix}posts';");
	if(strtolower($found_engine) == 'innodb') {
		$engine = ' ENGINE=InnoDB';
	}


	dbDelta("CREATE TABLE {$wpdb->prefix}swiftad_displayad (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		postid bigint(20) unsigned NOT NULL,
		status varchar(15)  NOT NULL DEFAULT 'live',
		run int(11) NOT NULL DEFAULT 1,
		ad_code text  NOT NULL,
		ad_target varchar(16)  NOT NULL,
		ad_size varchar(8)  DEFAULT NULL,
		ad_type varchar(16) DEFAULT NULL,
		delivery varchar(32) DEFAULT NULL,
		impressions int(11) NOT NULL DEFAULT 0,
		order_id int(15) NOT NULL DEFAULT 0,
		lineitem_id int(15) NOT NULL DEFAULT 0,
		slotid varchar(64)  NOT NULL,
		startdate date NOT NULL,
		starttime varchar(12)  NOT NULL,
		enddate date NOT NULL,
		endtime varchar(12) NOT NULL,
		fc varchar(6)  NOT NULL,
		fc_impressions varchar(8)  NOT NULL,
		fc_howmany varchar(8)  NOT NULL,
		fc_type varchar(16)  NOT NULL,
		fc_lifetime varchar(32)  NOT NULL,
		gd varchar(6)  NOT NULL,
		geo text  NOT NULL,
		post_status varchar(20)  NOT NULL DEFAULT 'off',
		created datetime NOT NULL,
		timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY postid (postid),
		KEY startdate (startdate),
		KEY enddate (enddate),
		KEY status (status)
		) ".$charset_collate.$engine.";");



	dbDelta("CREATE TABLE {$wpdb->prefix}swiftad_inventory (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(64)  NOT NULL,
		description text  NOT NULL,
		status varchar(15)  NOT NULL DEFAULT 'active',
		code varchar(15)  NOT NULL,
		size varchar(32)  NOT NULL,
		slotid varchar(64)  NOT NULL,
		created datetime NOT NULL,
		timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY code (code),
		KEY status (status)
		) ".$charset_collate.$engine.";");


	dbDelta("CREATE TABLE {$wpdb->prefix}swiftad_abtest (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(64)  NOT NULL DEFAULT 'A/B Test',
		hypothesis text,
		postid_a bigint(20) unsigned NOT NULL,
		postid_b bigint(20) unsigned NOT NULL,
		run_a int(4) NOT NULL,
		run_b int(4) NOT NULL,
		inventory varchar(64)  NULL,
		startdate date NOT NULL,
		starttime varchar(12) NOT NULL,
		enddate date NOT NULL,
		endtime varchar(12) NOT NULL,
		status varchar(15)  NOT NULL,
		created datetime NOT NULL,
		timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY startdate (startdate),
		KEY enddate (enddate),
		KEY status (status)
		) ".$charset_collate.$engine.";");

	


}

