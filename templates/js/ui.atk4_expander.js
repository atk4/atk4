/*
 * jQuery UI Table Expander
 *
 * Depends:
 *   ui.core.js
 */

$.widget("ui.atk4_expander", {
    _create: function() {
        var self=this;
        this.element.bind("click.atk4_expander", function(event) { self.click(); });
    },
    click: function() {
        if(this.expanded) this.collapse(); else this.expand();

    },
	transition: false,
    expanded: false,
    expander_id: null,
    expander_url: null,
    this_tr: null,
    this_div: null,
    
    
    id: null,
    expand: function() {
        if(this.expanded || this.transition)return false;

        // Collapse if any others are expanded
        this.element.closest('table').find('.expander').atk4_expander('collapseFast');

        // Make button look like it's bein pushed
        this.element.addClass('expander');

        // add additional row after ours
        this.this_tr=this.element.closest('tr');
        this.this_tr.addClass("lister_expander_parent");

        this.expander_id=this.element.attr('id')+"_ex";

        this.this_tr.after("<tr id='"+this.expander_id+"'><td class='lister_expander ui-corner-bottom' colspan="+this.this_tr.children().length+"><div class='lister_expander_inner' ><div class='lister_expander_inner2' style='height: 0px' id='"+this.expander_id+"_cell'>Loading....</div></div></td></tr>"
                );

        // Kick of annimation before we send request
        this.div=$('#'+this.expander_id+'_cell');
        var div=this.div;
        // expander loands contents of <tr><td><div>
		this.transition=true;
		var self=this;
        div.atk4_load(this.element.attr('rel'),function(){
                div.stop();
                div.attr('style','display: block'); // clear overflow, height, etc
				self.transition=false;
        });
        this.expanded=true;
    },
    collapse: function() {
        if(!this.expanded || this.transition)return false;

        this.element.removeClass('expander');
		var ttr=this.this_tr;


        var remove_this=this.expander_id;

        // expander contracts div
		this.transition=true;
		var self=this;
        this.div.slideUp("fast",function(){
				ttr.removeClass("lister_expander_parent");
                $('#'+remove_this).remove();
				self.transition=false;
        });

		this.div.empty();
		this.div.triggerHandler('remove');




        this.expanded=false;
    },
    collapseFast: function() {
        if(!this.expanded || this.transition)return false;

        console.log(this.element[0]);
        this.element.filter('input').each(function(junk,cb){
            cb.checked=false;
            $(cb).change();
        });

        this.element.removeClass('expander');
		var ttr=this.this_tr;

        var remove_this=this.expander_id;

        // expander contracts div
		this.transition=true;
		var self=this;
		ttr.removeClass("lister_expander_parent");
        $('#'+remove_this).remove();
		self.transition=false;

		this.div.empty();
		this.div.triggerHandler('remove');

        this.expanded=false;
    }

});
