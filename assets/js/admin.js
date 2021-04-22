var pdxsync_admin = {
    doSync: function(btn, conf){
        var button = jQuery(btn);
        button.attr('disabled', true);
        var btn_text = button.text();
        button.text(button.data('loading-text'));
		jQuery.post(ajaxurl, conf.data, function(response) {
            button.attr('disabled', false);
            button.text(btn_text);
            var res = JSON.parse(response);
            if(res.result){
                jQuery('#updatedtimeago').text(res.message);
            }
		});
    }
};