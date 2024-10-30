jQuery(document).ready(function() {
	function countChars(field,cntfield) {
		cntfield.value = field.value.length;
	}


	jQuery('.attachment_upload').click(function() {
		jQuery('.attachment').removeClass('active');
		jQuery(this).parent().find('.attachment:first').addClass('active');
		tb_show('', 'media-upload.php?post_id=0&TB_iframe=1');
		return false;
	});

	var _send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html) {
		 if (jQuery('.attachment.active').length > 0) {
           	imgurl = jQuery('img',html).attr('src');
			aurl = jQuery('a',"<div>" + html + "</div>").attr('href');

			if (imgurl) {
				jQuery('.attachment.active').val(imgurl);
			} else {
				jQuery('.attachment.active').val(aurl);
			}
 
			jQuery('.attachment').removeClass('active');
			tb_remove();
        } else {
            _send_to_editor(html);
        }
	}

	if (jQuery('#admin-section-tweets-wrap').length > 0) {
    jQuery.getJSON('http://api.twitter.com/1/statuses/user_timeline.json?callback=?&count=3&screen_name=cwantwm',
        function(data) {
            jQuery.each(data, function(i, tweet) {
                if(tweet.text !== undefined) {
                    jQuery('#admin-section-tweets-wrap').append("<li class='speech'>"+tweet.text+"</li>");
                }
            });
        }
    );
	}
		
	jQuery('.suggest').each(function () { jQuery(this).suggest(ajaxurl + '?action=suggest_action&type=' + jQuery(this).data('suggest'));  });
	
	

		
	jQuery('.range').change(function(event){
		jQuery(this).next('.rangeval').html(jQuery(this).val());
	});
	jQuery('.range').change();
	jQuery('.range').click(function(event){jQuery(this).focus();});
	
	jQuery('input.picker').each(function () {
		var saveid=jQuery(this);
		
		jQuery(this).next('div.picker').farbtastic(function (color) { saveid.val(color.toUpperCase()).prev('.swatch').css('background',color); });
	});
				
	jQuery('input.picker').focus(function () {jQuery(this).next('div.picker').show();})
	jQuery('input.picker').blur(function () {jQuery(this).next('div.picker').hide();})
	
});