jQuery(document).ready(function(){
    // Only for adding group
    jQuery('.wpcf-fields-add-ajax-link').click(function(){
        jQuery.ajax({
            url: jQuery(this).attr('href'),
            beforeSend: function() {
                jQuery('#wpcf-ajax-response').addClass('wpcf-ajax-loading');
            },
            success: function(data) {
                jQuery('#wpcf-ajax-response').removeClass('wpcf-ajax-loading');
                jQuery('#wpcf-fields-sortable').append(data);
            }
        });
        return false;
    });
    // Sort and Drag
    jQuery('.ui-sortable').sortable({
        revert: true,
        handle: 'img.wpcf-fields-form-move-field',
        containment: 'parent'
    });
    jQuery('.wpcf-fields-radio-sortable').sortable({
        revert: true,
        handle: 'img.wpcf-fields-form-radio-move-field',
        containment: 'parent'
    });
    jQuery('.wpcf-fields-select-sortable').sortable({
        revert: true,
        handle: 'img.wpcf-fields-form-select-move-field',
        containment: 'parent'
    });
    
    jQuery(".wpcf-form-fieldset legend").live('click', function() {
        jQuery(this).parent().children(".collapsible").slideToggle("fast", function() {
            var toggle = '';
            if (jQuery(this).is(":visible")) {
                jQuery(this).parent().children("legend").removeClass("legend-collapsed").addClass("legend-expanded");
                toggle = 'open';
            } else {
                jQuery(this).parent().children("legend").removeClass("legend-expanded").addClass("legend-collapsed");
                toggle = 'close';
            }
            // Save collapsed state
            // Get fieldset id
            var collapsed = jQuery(this).parent().attr('id');
            // Get group id
            var group_id = false;
            if (jQuery('input:[name="group-id"]').length > 0) {
                group_id = jQuery('input:[name="group-id"]').val();
            } else {
                group_id = -1;
            }
            jQuery.ajax({
                url: ajaxurl,
                type: 'get',
                data: 'action=wpcf_ajax&wpcf_action=group_form_collapsed&id='+collapsed+'&toggle='+toggle+'&group_id='+group_id
            });
        });
    });
    jQuery('.wpcf-forms-set-legend').live('keyup', function(){
        jQuery(this).parents('fieldset').find('.wpcf-legend-update').html(jQuery(this).val());
    });
    jQuery('.form-error').parents('.collapsed').slideDown();
    jQuery('.wpcf-form input').live('focus', function(){
        jQuery(this).parents('.collapsed').slideDown();
    });
    
    // Delete AJAX added element
    jQuery('.wpcf-form-fields-delete').live('click', function(){
        if (jQuery(this).attr('href') == 'javascript:void(0);') {
            jQuery(this).parent().fadeOut(function(){
                jQuery(this).remove();
            });
        }
    });
    
    /*
     * Generic AJAX call (link). Parameters can be used.
     */
    jQuery('.wpcf-ajax-link').live('click', function(){
        var callback = wpcfGetParameterByName('wpcf_ajax_callback', jQuery(this).attr('href'));
        var update = wpcfGetParameterByName('wpcf_ajax_update', jQuery(this).attr('href'));
        var updateAdd = wpcfGetParameterByName('wpcf_ajax_update_add', jQuery(this).attr('href'));
        var warning = wpcfGetParameterByName('wpcf_warning', jQuery(this).attr('href'));
        var thisObject = jQuery(this);
        if (warning != false) {
            var answer = confirm(warning);
            if (answer == false) {
                return false;
            }
        }
        jQuery.ajax({
            url: jQuery(this).attr('href'),
            type: 'get',
            dataType: 'json',
            //            data: ,
            cache: false,
            beforeSend: function() {
                if (update != false) {
                    jQuery('#'+update).html('').show();
                }
            },
            success: function(data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        if (update != false) {
                            jQuery('#'+update).html(data.output).fadeOut(2000);
                        //                        if (data.output.length < 1) {
                        //                            jQuery('#'+update).fadeOut();
                        //                        }
                        }
                        if (updateAdd != false) {
                            if (data.output.length < 1) {
                                jQuery('#'+updateAdd).fadeOut();
                            }
                            //                        jQuery('.calcium-ajax-loader-small').remove();
                            jQuery('#'+updateAdd).append(data.output);
                        }
                    }
                    if (typeof data.execute != 'undefined') {
                        eval(data.execute);
                    }
                }
                if (callback != false) {
                    eval(callback+'(data, thisObject)');
                }
            }
        });
        return false;
    });
});

/**
 * Searches for parameter inside string ('arg', 'edit.php?arg=first&arg2=sec')
 */
function wpcfGetParameterByName(name, string){
    name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regexS = "[\\?&]"+name+"=([^&#]*)";
    var regex = new RegExp( regexS );
    var results = regex.exec(string);
    if (results == null) {
        return false;
    } else {
        return decodeURIComponent(results[1].replace(/\+/g, " "));
    }
}

/**
 * AJAX delete elements from group form callback.
 */
function wpcfFieldsFormDeleteElement(data, element) {
    element.parent().fadeOut(function(){
        element.parent().remove();
    });
}