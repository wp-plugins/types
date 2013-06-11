
var iclEditorWidth = 550;
var iclEditorWidthMin = 195;
var iclEditorHeight = 420;
var iclEditorHeightMin = 195;
var iclCodemirror = new Array();

jQuery(document).ready(function(){
    /*
     * Set active editor
     * Important when switching between editor instances.
     * 
     * Used on WP editor, Types WYSIWYG, Views Filter Meta HTML,
     * Views Layout Meta HTML, CRED form.
     */
    window.wpcfActiveEditor = 'content';
    jQuery('.wp-media-buttons a, .wpcf-wysiwyg .editor_addon_wrapper .item, #postdivrich .editor_addon_wrapper .item, #wpv_filter_meta_html_admin_edit .item, #wpv_layout_meta_html_admin_edit .item').on('click', function(){
        window.wpcfActiveEditor = jQuery(this).parents('.wpcf-wysiwyg, #postdivrich, #wpv_layout_meta_html_admin, #wpv_filter_meta_html_admin')
        .find('textarea#content, textarea.wpcf-wysiwyg, textarea#wpv_layout_meta_html_content, textarea#wpv_filter_meta_html_content').attr('id');
        
    /*
         *
         * TODO 1.3 Why we do not have saving cookie in common?
         */
    //        document.cookie = "wpcfActiveEditor="+window.wpcfActiveEditor+"; expires=Monday, 31-Dec-2020 23:59:59 GMT; path="+wpcf_cookiepath+"; domain="+wpcf_cookiedomain+";";
    });
    
    /*
     * Handle the "Add Field" boxes - some layout changes.
     */
    jQuery('.wpv_add_fields_button').on('click', function(e) {
        
        // Set dropdown
        var dropdown_list = jQuery('#add_field_popup .editor_addon_dropdown');
        
        if (dropdown_list.css('visibility') == 'hidden') {

            /*
             * Specific for 'Add Field'
             * Make changes before setting popup
             */
            jQuery('#add_field_popup .editor_addon_wrapper .vicon').css('display', 'none');
            jQuery('#add_field_popup').show();
            
            // Place it above button
            dropdown_list.css('margin', '-25px 0 0 -15px');
	    dropdown_list.css('right', '0');
            var pos = jQuery('.wpv_add_fields_button').position();
            dropdown_list.css('top', pos.top + jQuery('.wpv_add_fields_button').height() - iclEditorHeight + 'px');
            dropdown_list.css('left', pos.left + jQuery('.wpv_add_fields_button').width() + 'px');

            // Toggle
            icl_editor_popup(dropdown_list);

        } else {
            dropdown_list.css('visibility', 'hidden');
        }
    });
	
	
    /*
     *
     * This manages clicking on dropdown icon
     */
    jQuery('#post').on('click', '.editor_addon_wrapper img', function(e){
        
        // Set dropdown
        var drop_down = jQuery(this).parent().find('.editor_addon_dropdown');

        if (drop_down.css('visibility') == 'hidden') {

            // Hide top links if div too small
            wpv_hide_top_groups(jQuery(this).parent());
            
            // Popup
            icl_editor_popup(drop_down);
            
        } else {
            // Hide all
            jQuery('.editor_addon_dropdown').css('visibility', 'hidden').css('display', 'inline');
        }
        
        
        // Bind close on iFrame click (it's loaded now)
        /*
         *
         * TODO Check and document this
         * SRDJAN I do not understand this one...
         */
        jQuery('#content_ifr').contents().bind('click', function(e) {
            jQuery('.editor_addon_dropdown').css('visibility', 'hidden').css('display', 'inline');
        });
        
        
        // Bind Escape
        jQuery(document).bind('keyup', function(e) {
            if (e.keyCode == 27) {
                jQuery('.editor_addon_dropdown').css('visibility', 'hidden').css('display', 'inline');
                jQuery(this).unbind(e);
            }
        });
    

    });

    /*
     *
     *
     * Trigger close action
     */
    jQuery('.editor_addon_wrapper .item, .editor_addon_dropdown .close').on('click', function(e){
        jQuery('.editor_addon_dropdown').css('visibility', 'hidden').css('display', 'inline');
    });

    /*
     * 
     * Direct links
     */
    jQuery('.editor-addon-top-link').on('click', function(){
        var scrollTargetDiv = jQuery(this).parents('.editor_addon_dropdown');
        var target = jQuery(this).parents('li')
        .find('.'+jQuery(this).data('editor_addon_target')+'-target');
        var position = target.position();
        var scrollTo = position.top;
        
        // Do scroll.
        scrollTargetDiv.animate({
            scrollTop:Math.round(scrollTo)
        }, 'fast');

    });
});


/*
 *
 *
 *
 *
 *
 *
 *
 *
 *
 * FUNCTIONS
 */

/**
 *
 * Main popup function
 */
function icl_editor_popup(e) {

    // Toggle
    icl_editor_toggle(e);
            
    // Set popup
    icl_editor_resize_popup(e);
            
    // Bind window click to auto-hide
    icl_editor_bind_auto_close();
}

// TODO Document this
jQuery.expr.filters.icl_offscreen = function(el) {
    var t = jQuery(el).offset();
    return (
        (el.offsetLeft + el.offsetWidth) < 0 
        || (el.offsetTop + el.offsetHeight) < 0
        || (el.offsetLeft > window.innerWidth || el.offsetTop > window.innerHeight)
        );
};

/**
 * Toggles popups.
 * 
 * @todo We have multiple calls here.
 */
function icl_editor_toggle(element) {
    
    // Hide all except current
    jQuery('.editor_addon_dropdown').each(function(){
        if (element.attr('id') != jQuery(this).attr('id')) {
            jQuery(this).css('visibility', 'hidden')
            .css('display', 'inline');
        } else {
            if (jQuery(this).css('visibility') == 'visible') {
                jQuery(this).css('visibility', 'hidden');
            } else {
                jQuery(this).css('visibility', 'visible').css('display', 'inline');
            }
        }
    });
}

/**
 * Resizing Toolset editor dropdowns.
 * 
 * Mind there are multiple instances on same screen.
 * @see .editor_addon_dropdown
 */
function icl_editor_resize_popup(element) {

    /*
     * First hide elements that should not be taken into account
     * Important: this is where we show shortuts in popup
     */
    jQuery(element).find('.direct-links').hide();
    jQuery(element).find('.editor-addon-link-to-top').hide();
    
    // Initial state
    // If hidden will be 0
    var heightInitial = jQuery(element).height();

    /*
     * Resize
     * 
     * We'll take main editor width
     */
    var editorWidth = Math.round( jQuery('#post-body-content').width() + 20 );
    icl_editor_resize_popup_width(element, editorWidth);
    
    /*
     * Adjust size.
     */
    if (heightInitial > iclEditorHeight) {
        /*
         *
         * Important: this is where we show shortuts in popup
         */
        jQuery(element).find('.direct-links').show();
        jQuery(element).find('.editor-addon-link-to-top').show();
        icl_editor_resize_popup_height(element, iclEditorHeight);
    }
    if (heightInitial < iclEditorHeightMin) {
        icl_editor_resize_popup_height(element, iclEditorHeight);
    }
    
    /*
     * Set CSS
     */
    jQuery(element).css('overflow', 'auto');
    jQuery(element).css('padding', '0px');
}

/**
 * Sets element width.
 */
function icl_editor_resize_popup_width(element, width) {
    jQuery(element).width(width).css('width', width + 'px');
}

/**
 * Sets element height.
 */
function icl_editor_resize_popup_height(element, height) {
    jQuery(element).height(height).css('height', height + 'px');
}
	

var keyStr = "ABCDEFGHIJKLMNOP" +
"QRSTUVWXYZabcdef" +
"ghijklmnopqrstuv" +
"wxyz0123456789+/" +
"=";
                   
function editor_decode64(input) {
    var output = "";
    var chr1, chr2, chr3 = "";
    var enc1, enc2, enc3, enc4 = "";
    var i = 0;
    
    // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
    var base64test = /[^A-Za-z0-9\+\/\=]/g;
    if (base64test.exec(input)) {
        alert("There were invalid base64 characters in the input text.\n" +
            "Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\n" +
            "Expect errors in decoding.");
    }
    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
    
    do {
        enc1 = keyStr.indexOf(input.charAt(i++));
        enc2 = keyStr.indexOf(input.charAt(i++));
        enc3 = keyStr.indexOf(input.charAt(i++));
        enc4 = keyStr.indexOf(input.charAt(i++));
    
        chr1 = (enc1 << 2) | (enc2 >> 4);
        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
        chr3 = ((enc3 & 3) << 6) | enc4;
    
        output = output + String.fromCharCode(chr1);
    
        if (enc3 != 64) {
            output = output + String.fromCharCode(chr2);
        }
        if (enc4 != 64) {
            output = output + String.fromCharCode(chr3);
        }
    
        chr1 = chr2 = chr3 = "";
        enc1 = enc2 = enc3 = enc4 = "";
    
    } while (i < input.length);
    
    return unescape(editor_utf8_decode(output));
}

function editor_utf8_decode(utftext) {
    var string = "";
    var i = 0;
    var c = c1 = c2 = 0;

    while ( i < utftext.length ) {

        c = utftext.charCodeAt(i);

        if (c < 128) {
            string += String.fromCharCode(c);
            i++;
        }
        else if((c > 191) && (c < 224)) {
            c2 = utftext.charCodeAt(i+1);
            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
            i += 2;
        }
        else {
            c2 = utftext.charCodeAt(i+1);
            c3 = utftext.charCodeAt(i+2);
            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }

    }

    return string;
}

function insert_b64_shortcode_to_editor(b64_shortcode, text_area) {
    var shortcode = editor_decode64(b64_shortcode);
    if(shortcode.indexOf('[types') == 0 && shortcode.indexOf('[/types') === false) {
        shortcode += '[/types]';
    }

    window.wpcfActiveEditor = text_area;
    icl_editor.insert(shortcode);
}

/**
 * Filtering elements from search boxes with JS
 */
function wpv_on_search_filter(el) {
    // get search text
    var searchText = jQuery(el).val();
	
    // get parent on DOM to find items and hide/show Search
    var parent = el.parentNode.parentNode;
    var searchItems = jQuery(parent).find('.group .item');
	
    jQuery(parent).find('.search_clear').css('display', (searchText == '') ? 'none' : 'inline');
	
    // iterate items and search
    jQuery(searchItems).each(function() {
        if(searchText == '' || jQuery(this).text().search(new RegExp(searchText, 'i')) > -1) {
            // alert(jQuery(this).text());
            jQuery(this).css('display', 'inline');
        } 
        else {
            jQuery(this).css('display', 'none');
        }
    });
	
    // iterate group titles and check if they have items (otherwise hide them)
	
    wpv_hide_top_groups(parent);
}

/**
 * @TODO Document this
 */
function wpv_hide_top_groups(parent) {
    var groupTitles = jQuery(parent).find('.group-title');
    jQuery(groupTitles).each(function() {
        var parentOfGroup = jQuery(this).parent();
        // by default we assume that there are no children to show
        var visibleGroup = false;
        jQuery(parentOfGroup).find('.item').each(function() {
            if(jQuery(this).css('display') == 'inline') {
                visibleGroup = true;
                return false;
            }
        });
		
        if(!visibleGroup) {
            jQuery(this).css('display', 'none');
        } else {
            jQuery(this).css('display', 'block');
        }
    });
}

// clear search input
function wpv_search_clear(el) {
    var parent = el.parentNode.parentNode;
    var searchbox = jQuery(parent).find('.search_field');
    searchbox.val('');
    wpv_on_search_filter(searchbox[0]);
}

/**
 * Bind window click to auto-hide
 * 
 * This should be generic close.
 * It's used in few places
 */
function icl_editor_bind_auto_close() {
    /*
     * jQuery executes 'bind' immediatelly on click
     */
    jQuery('body').bind('click',function(e){
        
        var dropdownAddField = jQuery('#add_field_popup .editor_addon_dropdown');
          
        // Exception for 'Add field' button
        if (jQuery(e.target).hasClass('wpv_add_fields_button')) {
        // Do nothing other dropdowns will take care
        } else if (jQuery(e.target).parents('.editor_addon_wrapper').length < 1) {
    
            // Hide all
            jQuery('.editor_addon_dropdown').css('visibility', 'hidden')
            .css('display', 'inline');
                
            // Unbind Add field dropdown
            dropdownAddField.removeClass('icl_editor_click_binded');
                
            jQuery(this).unbind(e);
        }
    });
}

/**
 * 
 * Inserts content into active editor.
 */
var icl_editor = (function(window, $){

    function isTinyMce($textarea)
    {
        var editor, ed=$textarea.attr('id');
        if (ed && ed.charAt(0)=='#') ed=ed.substring(1);
        
        // if tinyMCE
        if (
            window.tinyMCE && ed &&
            null != (editor=window.tinyMCE.get(ed)) && 
            false == editor.isHidden()
                )
            return editor;
        return false;
    };
    
    function isCodeMirror($textarea)
    {
        var textareaNext = $textarea[0].nextSibling;
        // if CodeMirror
        if (
            textareaNext && $textarea.is('textarea')&&
            //$(textareaNext).is('textarea')&&
            textareaNext.CodeMirror &&
            $textarea[0]==textareaNext.CodeMirror.getTextArea()
                )
            return textareaNext.CodeMirror;
        return false;
    };
    
    function getContent($area)
    {
        if (!$area) $area=$('#content');
        //var tinymce=aux.isTinyMce($area);
        var codemirror=isCodeMirror($area);
        if (codemirror)
            return codemirror.getValue();
        return $area.val();
    };
    
    function InsertAtCursor(myField, myValue1, myValue2)
    {
        var $myField=myField;
        var tinymce=isTinyMce($myField);
        var codemirror=isCodeMirror($myField);
        // if tinyMCE
        if (tinymce)
        {
            //            alert('tinymce');
            tinymce.focus();
            if (typeof(myValue2)!='undefined' && myValue2) // wrap
                tinymce.execCommand("mceReplaceContent",false, myValue1 + tinymce.selection.getContent({
                    format : 'raw'
                }) + myValue2);
            else
                tinymce.execCommand("mceInsertContent",false, myValue1);
        }
        // else if CodeMirror
        else if (codemirror)
        {
            //            alert('codemirror');
            codemirror.focus();

            if (!codemirror.somethingSelected())
            {
                // set at current cursor
                var current_cursor=codemirror.getCursor(true);
                codemirror.setSelection(current_cursor, current_cursor);
            }
            if (typeof(myValue2)!='undefined' && myValue2) { // wrap
                codemirror.replaceSelection(myValue1 + codemirror.getSelection() + myValue2, 'end');
            } else {
                codemirror.replaceSelection(myValue1, 'end');
            }
        }
        // else other text fields
        else
        {
            //            alert('other');
            myField=$myField[0]; //$(myField)[0];
            myField.focus();
            if (document.selection)
            {
                sel = document.selection.createRange();
                if (typeof(myValue2)!='undefined' && myValue2) // wrap
                    sel.text = myValue1 + sel.text + myValue2;
                else
                    sel.text = myValue1;
            }
            else if ((myField.selectionStart != null) && (myField.selectionStart != undefined)/* == 0 || myField.selectionStart == '0'*/)
            {
                var startPos = parseInt(myField.selectionStart);
                var endPos = parseInt(myField.selectionEnd);
                if (typeof(myValue2)!='undefined' && myValue2) // wrap
                {
                    var sel = myField.value.substring(startPos, endPos);
                    myField.value = myField.value.substring(0, startPos) + myValue1 + sel + myValue2 +
                    myField.value.substring(endPos, myField.value.length);
                }
                else
                    myField.value = myField.value.substring(0, startPos) + myValue1 +
                    myField.value.substring(endPos, myField.value.length);
            }
            else
            {
                if (typeof(myValue2)!='undefined' && myValue2) // wrap
                    myField.value += myValue1 + myValue2;
                else
                    myField.value += myValue1;
            }
        }
    //        $myField.trigger('paste');
    };
    
    function insertContent(content)
    {
        //        alert(window.wpcfActiveEditor);
        InsertAtCursor($('#'+window.wpcfActiveEditor), content);
    }
    
    /**
     * Toggles Codemirror on textarea (#ID, toggle).
     * 
     * We could record mouse position on Codemirror to restore it.
     */
    function toggleCodeMirror(textarea, on)
    {
        // if codemirror activated, enable syntax highlight
        if (window.CodeMirror)
        {
            if (!on && window.iclCodemirror[textarea])
            {
                window.iclCodemirror[textarea].toTextArea();
                window.iclCodemirror[textarea] = false;
                jQuery('#'+textarea).focus();
                return !on;
            }
            else if (on && !window.iclCodemirror[textarea])
            {
//                CodeMirror.defineMode("myshortcodes", codemirror_shortcodes_overlay);
                    
                var $_metabox=$('#'+textarea).closest('.postbox'),
                _metabox_closed=false,
                _metabox_display=false;
                        
                if ($_metabox.hasClass('closed') || 'none'==$_metabox.css('display'))
                {
                    _metabox_closed=true;
                    $_metabox.removeClass('closed');
                }
                if ('none'==$_metabox.css('display'))
                {
                    _metabox_display='none';
                    $_metabox.css('display','block');
                }
                window.iclCodemirror[textarea] = CodeMirror.fromTextArea(document.getElementById(textarea), {
                    mode: 'myshortcodes',//"text/html",
                    tabMode: "indent",
                    lineWrapping: true,
                    lineNumbers : true,
                    autofocus: true
                });
                
                // TODO is resizing needed?
                // needed for scrolling
//                var height=Math.min(5000, Math.max(50, 200));
//                $('#'+textarea).css('resize', 'none').height( height + 'px' );
//                window.iclCodemirror[textarea].setSize( $('#'+textarea).width(), height );
                    
                if ('none'==_metabox_display)
                {
                    $_metabox.css('display','none');
                }
                if (_metabox_closed)
                {
                    $_metabox.addClass('closed');
                }
                
                jQuery('#'+textarea).focus();

                return window.iclCodemirror[textarea];
            }
        }
        return false;
    };
    
    return {
        isTinyMce : isTinyMce,
        isCodeMirror : isCodeMirror,
        getContent : getContent,
        InsertAtCursor : InsertAtCursor,
        toggleCodeMirror : toggleCodeMirror,
        insert : function(text) {
            insertContent(text);
        },
        codemirror : function(textarea, on) {
            return toggleCodeMirror(textarea, on);
        },
        codemirrorGet : function(textarea) {
            return window.iclCodemirror[textarea];
        }
    };

})(window, jQuery, undefined);