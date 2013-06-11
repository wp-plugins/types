/* 
 * Editor.
 */

jQuery(document).ready(function($){
    
    ko.bindingHandlers.typesEditor = {
        init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
        // This will be called when the binding is first applied to an element
        // Set up any initial state, event handlers, etc. here
        },
        update: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
        // This will be called once when the binding is first applied to an element,
        // and again whenever the associated observable changes value.
        // Update the DOM element based on the supplied values here.
        }
    };
    
    //    var typesEditorModal = {
    //        mode: ko.observable('current'),
    //        cb_mode: ko.observable('db'),
    //        cbs_mode: ko.observable('display_all'),
    //        date_mode: ko.observable('text'),
    //        file_mode: ko.observable(),
    //        image_size: ko.observable(typesEditor.tb_options.size)
    //    //        isRelated: ko.observable(false)
    //    };

    typesEditor.thickbox();
    ko.applyBindings(typesEditorModal);
    
});

var typesEditorModal = {
    mode: ko.observable('current'),
    cb_mode: ko.observable('db'),
    cbs_mode: ko.observable('display_all'),
    date_mode: ko.observable('text'),
    file_mode: ko.observable(),
    image_size: ko.observable(typesEditorFieldOptions.size),
    radio_mode: ko.observable('db'),
    url_target: ko.observable('_self'),
    raw: ko.observable(),
    rawDisableAll: function(data, event) {
        if (this.raw()) {
            typesEditor.form().find('div.js-raw-disable :enabled')
            .not('#types-modal-raw,#__types_nonce')
            .addClass('types-raw-disabled')
            .attr('disabled', 'disabled');
            
            typesEditor.menu().find('a.js-raw-disable')
            .addClass('types-raw-disabled').unbind('click');
        } else {
            typesEditor.form().find('.types-raw-disabled').removeAttr('disabled')
            .removeClass('types-raw-disabled');
            typesEditor.resetMenu();
        }
        return true;
    },
    supports: function(feature) {
        return jQuery.inArray(feature, typesEditorOptions.supports) != -1;
    },
    context: function() {
        return typesEditorOptions.context;
    }
};

//(function($){
    
var typesEditor = (function(window, $){
    
    var modal = $('#types-editor-modal');
    var modalMenu = modal.find('.media-menu');
    var modalMenuItems;
    var modalContent = modal.find('.media-frame-content');
    var modalContentTabs;
    var modalInsertButton = modal.find('.media-button-insert');
    var modalForm;
    var tabIndex = 0;
    
    function thickbox()
    {
        modalForm = $('#types-editor-modal-form');
        
        modalMenuItems = modal.find('.media-menu a');
        modalContentTabs = modal.find('.media-frame-content .tab');
        
        // Bind menu tabbing
        bindTabbing();
        
        modalMenu.find('a:first-child').addClass('active');
        modalContent.find('.tab:eq(0)').show();
        modal.find('.media-modal-close, .media-modal-backdrop').click(function(){
            modalMenuItems.removeClass('active');
            modal.hide();
            return false;
        });
        
        // Alter look
        window.parent.jQuery('#TB_title').hide();
        window.parent.jQuery('#TB_window').css('height', 'auto');
        $('html, body').css('height', 'auto');
        modal.find('.media-modal').css({
            'left': 0, 
            'right': 0, 
            'top': 0, 
            'bottom': 0
        });
        
        // Bind close
        modal.find('.media-modal-close, .media-modal-backdrop').click(function(){
            window.parent.jQuery('#TB_closeWindowButton').trigger('click');
            return false;
        });
        
        // Bind submit
        modalInsertButton.click(function(){
            $('#types-editor-modal-form').trigger('submit');
            return false;
        });
    }
    
    function bindTabbing()
    {
        modalMenuItems.click(function(){
            modalMenuItems.removeClass('active');
            $(this).addClass('active');
            tabIndex = modalMenuItems.index($(this));
            modalContentTabs.hide();
            modalContent.find('.tab:eq('+tabIndex+')').show();
            return false;
        });
    }
    
    function resetMenu()
    {
        bindTabbing();
    }
        
    return {
        thickbox: thickbox,
        resetMenu: resetMenu,
        form: function() {
            return modalForm;
        },
        menu: function() {
            return modalMenu;
        }
    };
})(window, jQuery, undefined);
    
    
    
//}(jQuery));