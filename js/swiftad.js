/*
 *  COPYRIGHT AND TRADEMARK NOTICE
 *  Copyright 2008-2015 Blog Nirvana. All Rights Reserved.
 *  SwiftImpresions is a trademark of BlogNirvana.

 *  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
 *  By using this code you agree to indemnify Blog Nirvana from any
 *  liability that might arise from it's use.
 */

/*
 * Javascript for Swift Impressions 
 *
 *
 */

function stripslashes(str) {
    str = str.replace(/\\'/g, '\'');
    str = str.replace(/\\"/g, '"');
    str = str.replace(/\\0/g, '\0');
    str = str.replace(/\\\\/g, '\\');
    return str;
}

function swiftad_popup_fill($code) {
	jQuery("body").append("<div class='modal-overlay js-modal-close'></div>");
    jQuery("body").append("<div id=\"swiftad-popup\" class=\"modal-box\">\n<div class=\"modal-body\">\n" + stripslashes($code) + "\n</div>\n<footer> <a href=\"#\" class=\"btn btn-small js-modal-close\">Close</a> </footer>\n</div>\n");
    
    jQuery(".modal-overlay").fadeTo(500, 0.7);
	jQuery("#swiftad-popup").fadeIn(500);
	jQuery(".js-modal-close, .modal-overlay").click(function() {
	    jQuery(".modal-box, .modal-overlay").fadeOut(500, function() {
	        jQuery(".modal-overlay").remove();
	    });
	});
	jQuery(window).resize(function() {
	    jQuery(".modal-box").css({
	        top: (jQuery(window).height() - jQuery(".modal-box").outerHeight()) / 2,
	        left: (jQuery(window).width() - jQuery(".modal-box").outerWidth()) / 2
	    });
	}); 
	jQuery(window).resize(); 
	
}

