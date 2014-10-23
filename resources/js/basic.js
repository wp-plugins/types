/**
 *
 * Use this file only for scripts needed in full version.
 * Before moving from embedded JS - make sure it's needed only here.
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/cck/tags/1.6.3/resources/js/basic.js $
 * $LastChangedDate: 2014-07-04 12:19:40 +0200 (Fri, 04 Jul 2014) $
 * $LastChangedRevision: 24633 $
 * $LastChangedBy: marcin $
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
