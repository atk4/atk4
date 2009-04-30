/*
 * jQuery UI Table Expander
 *
 * Depends:
 *   ui.core.js
 */

$.widget("ui.atk4_expander", {
    _init: function() { 
        var self=this;
        this.element.addClass("ui-atk4-expander");
        this.element.bind("click.atk4_expander", function(event) { self.click(); });
        //this.element.click(function(){ alert($(this).expanded); $(this).expanded?$(this).collapse():$(this).expand(); });
    },
    click: function() {
        if(this.expanded) this.collapse(); else this.expand();

    },
    expanded: false,
    expander_id: null,
    expander_url: null,
    id: null,
    expand: function() {
        if(this.expanded)return false;

        // Make button look like it's bein pushed
        this.element.removeClass("ui-atk4-expander");
        this.element.addClass("ui-atk4-expander-active");

        // add additional row after ours
        el=this.element.closest('tr');

        this.expander_id=this.element.attr('id')+"_ex";

        el.after("<tr id='"+this.expander_id+"'><td colspan="+el.children().length+" class='ui-atk4-expander-bottom'><div>Loading...</div></td></tr>");

        // Kick of annimation before we send request
        var div=$('#'+this.expander_id+' td div');
        div.animate({height: "200px"},1500);
        div.load(this.element.attr('rel'),null,function(){
                div.stop();
                div.attr('style','display: block'); // clear overflow, height, etc
                });

        this.expanded=true;
    },
    collapse: function() {
        if(!this.expanded)return false;

        this.element.removeClass("ui-atk4-expander-active");
        this.element.addClass("ui-atk4-expander");

        var remove_this=this.expander_id;

        $('#'+this.expander_id+' td div').slideUp("fast",function(){
                $('#'+remove_this).remove();
                });




        this.expanded=false;
    }

});
