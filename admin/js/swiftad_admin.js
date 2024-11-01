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


jQuery(document).ready(function() {

    jQuery('.datepicker').datepicker({
        dateFormat : 'yy-mm-dd'
    });
    
    jQuery('#swiftad-newline').change(function() {
    	 if(jQuery(this).is(":checked")) {
    	 	jQuery('#pp-start-time').prop('readonly', false);
    	 	jQuery('#pp-start-date').prop('readonly', false);
    	 	jQuery('#pp-start-time').timepicker({ useSelect: true , step: 15});
    	 	jQuery('#pp-start-date').datepicker();
    	 } else {
    	 	jQuery('#pp-start-time').prop('readonly', true);
    	 	jQuery('#pp-start-date').prop('readonly', true);
    	 }
    });
    
    
    jQuery('.timepicker:not([disabled], [readonly])').timepicker({ useSelect: true , step: 15});
    
    swiftadinitiateGeoTarget();
    
    jQuery('#swiftad_gt_button').click(function() {
    	jQuery("#swiftad_gt_results").html('<img src="/wp-includes/images/spinner.gif" />');
		jQuery.ajax({
			url: "http://api.swiftimpressions.com/geoquery_get",
			jsonp: "swiftadprocessGeoResults",
			dataType: "jsonp",
			data: {
		        type: jQuery("#swiftad_gt_type").val(),
		        name: jQuery("#swiftad_gt_search").val(),
		        swiftad: true

		    }
		});    	
    });
    
     jQuery('#swiftad-admin-block #post-manage button').click(function(e) {
    	   	jQuery("#post-manage #post-postid").val(jQuery(this).attr("value"));
    	   	jQuery("#post-manage #post-run").val(jQuery(this).attr("run"));
    	   	jQuery("#post-manage").submit();
    });
    jQuery('#abtest-form button').click(function(e) {
    	   	jQuery("#abtest-form #abtestid").val(jQuery(this).attr("value"));
    	   	jQuery("#abtest-form").submit();
    });

});

function swiftadprocessGeoResults(results) {
	jQuery("#swiftad_gt_results").html("<!--Results-->");
	if (typeof results.error !== 'undefined') {
		jQuery("#swiftad_gt_results").html(results.error);
	} else {
		jQuery.each(results, function( index, geoObject ) {
			var data_o = {};
		   data_o[index] = geoObject;
		   jQuery("#swiftad_gt_results").append(geoObject.name +  " - " + geoObject.Parent +  " - " +geoObject.countrycode + " - " + geoObject.type + " <a data_t='" + geoObject.type + "' data_c='" + geoObject.countrycode + "' data_n='" + geoObject.name + "' data_i='" + index + "'data_o='" + JSON.stringify(data_o) + "' class='geo-results-target' style='cursor: pointer;' onclick='swiftadaddGeoTarget(this);return false;'>add</a><br />");
		});
	}
}

function swiftadaddGeoTarget(link) {
	
	jQuery("#swiftad_gt_selections").append("<a data_i='" + jQuery(link).attr('data_i') + "'  onclick='swiftadremoveGeoTarget(this);return false;' class='swiftad-geo-target-link'>" + jQuery(link).attr('data_n') + " - " + jQuery(link).attr('data_c')  + " - " + jQuery(link).attr('data_t') + " <span class='xremove'>x</span></a>");
	
	if (jQuery("#swiftad-geotargets").length) {
		var old_val = jQuery.parseJSON(jQuery("#swiftad-geotargets").val());
		var new_val = jQuery.parseJSON(jQuery(link).attr('data_o'));
		old_val = jQuery.extend(old_val, new_val);
		jQuery("#swiftad-geotargets").val(JSON.stringify(old_val));
		
	} else {
		var form = jQuery(link).closest('form');
	    input = jQuery("<input>").attr("type", "hidden")
                             .attr("name", "swiftad-geotargets")
                             .attr("id", "swiftad-geotargets")
                             .val(jQuery(link).attr('data_o'));
    	jQuery(form).append(jQuery(input));
	}
}

function swiftadinitiateGeoTarget() {
	
	var dirty = jQuery("#swiftad-geotargets").val();
	
	if (dirty !== null && dirty !== undefined && dirty.length) {
		dirty.replace('\\','');
		var targets = jQuery.parseJSON(dirty);
	
		jQuery.each(targets, function( index, value ) {
			jQuery("#swiftad_gt_selections").append("<a data_i='" + index + "'  onclick='removeGeoTarget(this);return false;' class='swiftad-geo-target-link'>" + value.name + " - " + value.Parent  + " - " + value.type + " - " +   value.countrycode + " <span class='xremove'>x</span></a>");
		});
	}
	
}

function swiftadremoveGeoTarget(link) {
	var old_val = jQuery.parseJSON(jQuery("#swiftad-geotargets").val());
	var index = jQuery(link).attr('data_i');
	delete old_val[index];
	if(jQuery.isEmptyObject(old_val)) {
		jQuery("#swiftad-geotargets").val("");
	} else {
		jQuery("#swiftad-geotargets").val(JSON.stringify(old_val));
	}
	jQuery(link).remove();
}

function swiftadactivatefree() {
	//display licnense with click through

	if (!jQuery("#swiftad-popup").length) {
      jQuery("body").append("<div id=\"swiftad-form-overlay\" class=\"swiftad-form-overlay js-form-close\"></div>");
      jQuery("body").append("<div id=\"swiftad-popup\" class=\"swiftad-form-box\"><div class=\"swiftad-form-body\"></div><div class=\"swiftad-form-footer\"><form id=\"activate-agree-terms\" method=post name=\"activate-agree-terms\" ><input type=\"checkbox\" name=\"swiftad-accept-terms\" value=\"acepted terms checked\"> I have read, understand, and agree to the terms<br><button type=\"submit\" name=\"agree\" class=\"swiftad-btn-rainbow\" style=\"color: #fff;\" value=\"I agree\">I agree</button></form> </div></div>\n");
	}
	jQuery("#swiftad-popup > .swiftad-form-body").load(swiftad_locals.license_url);
	
    jQuery(".swiftad-form-overlay").fadeTo(500, 0.4);
	jQuery("#swiftad-popup").fadeIn(500);
	jQuery(".js-form-close, .swiftad-form-overlay").click(function() {
	    jQuery("#swiftad-popup, .swiftad-form-overlay").fadeOut(500, function() {
	        jQuery("#swiftad-form-overlay").remove();
	        jQuery("#swiftad-popup").remove();
	    });
	});
	jQuery(window).resize(function() {
		if (jQuery("#swiftad-popup").length) {
			var topmargin = (jQuery(window).height() - jQuery("#swiftad-popup").outerHeight()) / 2;
		    jQuery("#swiftad-popup").css({
		        top: topmargin,
		        left: (jQuery(window).width() - jQuery("#swiftad-popup").outerWidth()) / 2
		    });
		}
	});
	jQuery(window).resize(); 
}







