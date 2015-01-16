/** * * Use this file only for scripts needed in full version.
 * Before moving from embedded JS - make sure it's needed only here.
 *
 * $HeadURL$
 * $LastChangedDate$
 * $LastChangedRevision$
 * $LastChangedBy$
 *
 */
jQuery(document).ready(function($){
    $('input[name=file]').on('change', function() {
        if($(this),$(this).val()) {
            $('input[name=import-file]').removeAttr('disabled');
        }
    });
    $('a.current').each( function() {
        var href = $(this).attr('href');
        if ('undefined' != typeof(href) && href.match(/page=wpcf\-edit(\-(type|usermeta))?/)) {
            $(this).attr('href', window.location.href);
        }
    });
    /**
     * settings toolset messages
     */
    $('#wpcf-toolset-messages-form input[type=checkbox]').on('change', function() {
        parent = $(this).closest('form');
        $('.spinner', parent).show();
        $('.updated', parent).hide();
        var data = {
            action: 'toolset_messages',
            value: $(this).attr('checked')

        };
        $.post(ajaxurl, data, function(response) {
            $('.spinner', parent).hide().after(response);
        });
    });
});
