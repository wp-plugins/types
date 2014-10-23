/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/tags/Views-1.6.4-CRED-1.3.2-Types-1.6.4-Acces-1.2.3/toolset-forms/js/file.js $
 * $LastChangedDate: 2014-07-11 14:41:02 +0200 (Fri, 11 Jul 2014) $
 * $LastChangedRevision: 24880 $
 * $LastChangedBy: marcin $
 *
 */
var wptFile = (function($, w) {
    var $item, $parent, $preview;
    function init() {
        $('.js-wpt-field').on('click', 'a.js-wpt-file-upload', function() {
            if ( $(this).data('attched-thickbox') ) {
                return;
            }
            return wptFile.open(this, true);
        });
    }
    function initRow(row) {
        $('.js-wpt-field', row).on('click', 'a.js-wpt-file-upload', function() {
            $(this).data('attched-thickbox', true );
            return wptFile.open(this, true);
        });
    }
    function mediaInsert(url, type) {
        $(':input', $item).first().val(url);
        if (type == 'image') {
            $preview.html('<img src="' + url + '" />');
        } else {
            $preview.html('');
        }
        tb_remove();
    }
    function mediaInsertTrigger(guid, type) {
        window.parent.wptFile.mediaInsert(guid, type);
        window.parent.jQuery('#TB_closeWindowButton').trigger('click');
    }
    function open(el)
    {
        height = $('body').height()-20;
        if ( 800 < height ) {
            height = 800;
        }
        width = $('body').width()-20;
        if ( 670 < width ) {
            width = 670;
        }
        $item = $(el).parents('.js-wpt-field-item');
        $parent = $item.parents('.js-wpt-field');
        $preview = $('.js-wpt-file-preview', $item);
        type = 'file';
        if ( $(el).data('wpt-type') ) {
            type = $(el).data('wpt-type');
        }
        tb_show(wptFileData.title, wptFileData.adminurl + 'media-upload.php?' + wptFileData.for_post + 'type='+type+'&context=wpt-fields-media-insert&wpt[id]=' + $parent.data('wpt-id') + '&wpt[type]=' + $parent.data('wpt-type') + '&TB_iframe=true&width='+width+'&height='+height);
        return false;
    }
    return {
        init: init,
        initRow: initRow,
        open: open,
        mediaInsert: mediaInsert,
        mediaInsertTrigger: mediaInsertTrigger
    };
})(jQuery);

jQuery(document).ready(wptFile.init);

