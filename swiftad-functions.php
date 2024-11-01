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
 * Inject SwiftAd into page footer
 *
 *
 */
function swiftad_display_inject() {
	
	global $wpdb, $swiftad_slotfills;
	$swiftad_config = get_option('swiftad_config');
	
	if (isset($_GET['swift_preview']) && isset( $swiftad_slotfills) ) {
		$postid = $_GET['swift_preview'];
		$postids = array(0 => $postid );
		$previews = "";
		//create each slot
		foreach ($swiftad_slotfills as $slotid) {
			$previews .= "\nfillPowerPost(" . $postid . ", " . $slotid  . ");\n";
		}
		
		$args['nopaging'] = true;
		$args['post_type'] = 'swiftas_post_type';
		$args['post_status'] = 'publish';
		$args['post__in'] = $postids;
		wp_reset_query();
		
		
		$query = new WP_Query($args);
		echo "<div id=\"swiftad-powerpost-block\" style=\"display: none;\">";
		while (  $query->have_posts() ) {
			$query->the_post();
			echo "<div id=\"swiftad-" . get_the_ID() . "\" style=\"display: none;\">";
			echo "<div id=\"swiftad-preview-header\" style=\"padding: 8px 20px;margin: 10px 20px;background: rgba(240,240,240, 1);border-radius: 5px;border: 1px solid #ccc;\"><img style=\"float: left;\" src=\"". pluginsurl('/images/small-logo.png', __FILE__) ."\"><div style=\"float: right;padding: 6px 0;color: #ccc;\"> Preview</div><div style=\"clear: both\"><!--clear--></div></div>\n";
			$custom = get_post_custom(get_the_ID());
			echo stripslashes($custom["ad_code"][0]);
			echo "\n</div>\n";
		}	
		echo "\n</div>\n";
		 
		echo "<script type='text/javascript'>jQuery(document).ready(function() {" . $previews . "});\n\nfunction fillPowerPost(postid,slotid) {jQuery(\"#div-swiftad-\" + slotid).replaceWith(jQuery(\"#swiftad-powerpost-block #swiftad-\"+postid).html());}</script>";	
		
			
		
	} else if (isset($swiftad_slotfills)) {
	
		$dfp1 = "";
		$dfp2 = "";
		$divs = "";

		/* Get shortcode slot fills & create each slot */
		
		
		foreach ($swiftad_slotfills as $code => $size) {

			$width = strtok($size, "x");
			$height = strtok("x");
			$dfp1 .= "googletag.defineSlot(\"/72045342/$code\", [$width, $height], \"div-swift-" . $code . "\").addService(googletag.pubads()).setCollapseEmptyDiv(true);";
			$dfp2 .= "googletag.cmd.push(function() { googletag.display(\"div-swift-" . $code . "\"); });";
		}
	
		echo "<script type='text/javascript'>var googletag = googletag || {};googletag.cmd = googletag.cmd || [];(function() {var gads = document.createElement('script');gads.async = true;gads.type = 'text/javascript';var useSSL = 'https:' == document.location.protocol;gads.src = (useSSL ? 'https:' : 'http:') +'//www.googletagservices.com/tag/js/gpt.js';var node = document.getElementsByTagName('script')[0];node.parentNode.insertBefore(gads, node);})(); \n\n googletag.cmd.push(function() {" . $dfp1 ."googletag.enableServices();});\n\njQuery(document).ready(function() {".$dfp2."});\n</script>";	

		unset($swiftad_slotfills);
	}	
}




/*
 * Handle swiftad shortcodes
 *
 */

function swiftad_shortcode($atts, $content = null) {
    global $swiftad_slotfills;

	if (empty($swiftad_slotfills)) $swiftad_slotfills = array();

	$output = "<!--Swift Ad-->\n";
	if(!empty($atts['slotid']) && !empty($atts['size']) ) {
		$output .= "\n<div id=\"div-swift-". $atts['slotid'] ."\"></div>\n";
		$swiftad_slotfills[$atts['slotid']] = strtok($atts['size'], "|");
	}
	return $output;
}


function swiftad_scripts() {
	wp_enqueue_script( "Swift Post", plugins_url( '/js/swiftad.js' , __FILE__),NULL, 1.01, true);
	wp_enqueue_style( 'swiftad-stylesheet', plugins_url( '/css/swiftad.css', __FILE__ ) );
}



