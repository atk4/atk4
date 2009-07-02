/*
 * jQuery UI Table Expander
 *
 * Depends:
 *   ui.core.js
 */

$.widget("ui.atk4_todo", {
    _init: function() {
        self=this;
        $('.todo_frame').dialog({
            autoOpen:false,
            width: 600,
            height: 400,
            buttons: {
                "Ok": function() {
                    $(this).dialog("close");
                    }
            }});
        this.element.bind("click.atk4_todo", function(event) { self.click(); });
    },
    click: function() {
        $('.todo_frame').dialog('open').load('todo.html?cut_object=todo');
        return false;
    }

});
