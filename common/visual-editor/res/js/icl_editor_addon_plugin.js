Array.prototype.isKey = function(){
  for(i in this){
    if(i === arguments[0])
      return true;
  };
  return false;
};


function icl_editor_add_menu(c, m, icl_editor_menu) {
    var sub_menus = new Array();
    for (var index = 0; index < icl_editor_menu.length; index++) {
        
        // Set callback function
        var fn = icl_editor_menu[index][1];

        if (icl_editor_menu[index][2] != "") {
            // a sub menu
            
            
            if (sub_menus.isKey(icl_editor_menu[index][2])) {
                sub = sub_menus[icl_editor_menu[index][2]];
            } else {
                // Create a sub menu/s
                parts = icl_editor_menu[index][2].split('-!-');
                sub = m;
                name = '';
                for (var part = 0; part < parts.length; part++) {
                    if (name == '') {
                        name = parts[part];
                    } else {
                        name += '-!-' + parts[part];
                    }
                    if (sub_menus.isKey(name)) {
                        sub = sub_menus[name];
                    } else {
                        sub = sub.addMenu({title : parts[part]});
                        sub_menus[name] = sub;
                    }
                }
            }

          sub.add({title : icl_editor_menu[index][0],
                onclick : eval(fn)});
            
        } else {
          m.add({title : icl_editor_menu[index][0],
                onclick : eval(fn)});
        }
    }

    return c;
}

jQuery.fn.extend({
insertAtCaret: function(myValue){
  return this.each(function(i) {
    if (document.selection) {
      this.focus();
      sel = document.selection.createRange();
      sel.text = myValue;
      this.focus();
    }
    else if (this.selectionStart || this.selectionStart == '0') {
      var startPos = this.selectionStart;
      var endPos = this.selectionEnd;
      var scrollTop = this.scrollTop;
      this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
      this.focus();
      this.selectionStart = startPos + myValue.length;
      this.selectionEnd = startPos + myValue.length;
      this.scrollTop = scrollTop;
    } else {
      this.value += myValue;
      this.focus();
    }
  })
}
});
