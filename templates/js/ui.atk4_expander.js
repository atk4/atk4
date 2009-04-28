/*
 * jQuery UI Table Expander
 *
 * Depends:
 *   ui.core.js
 */


var atk4_expander = {
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
        el=this.element;
        while(!el.is("tr")){
            el=el.parent();
        }
        tmp=this.element.attr('rel').split(' ');
        this.expander_id=tmp[0]+"_"+tmp[1];
        this.id=tmp[1];

        el.after("<tr id='"+this.expander_id+"'><td colspan=4 class='ui-atk4-expander-bottom'><div>Loading name='"+this.expander_id+"', id="+this.id+"!</div></td></tr>");

        // Kick of annimation before we send request
        $('#'+this.expander_id+' td div').animate({height: "200px"},1500);

        this.expanded=true;
    },
    collapse: function() {
        if(!this.expanded)return false;

        this.element.removeClass("ui-atk4-expander-active");
        this.element.addClass("ui-atk4-expander");

        $('#'+this.expander_id).remove();


        this.expanded=false;
    }

};

