/**
 *
 * Use this file only for scripts needed in full version.
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
        if ($(this).attr('href').match(/page=wpcf\-edit(\-(type|usermeta))?/)) {
            $(this).attr('href', window.location.href);
        }
    });
});
