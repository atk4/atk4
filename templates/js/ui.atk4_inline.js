/*
 * jQuery UI Table Expander
 *
 * Depends:
 *   ui.core.js
 */

$.widget("ui.atk4_inline", {
    _init: function() {
        var self=this;
        this.element.parent().bind("click.atk4_inline", function(event) { self.click(); });
    },
    click: function() {
        if(this.expanded) this.collapse(); else this.expand();

    },
    expanded: false,
    inline_id: null,
    inline_url: null,
    this_tr: null,
    
    
    id: null,
    expand: function() {
        if(this.expanded)return false;

        // Make button look like it's bein pushed
        $('.inline').atk4_inline('collapse');

        // add additional row after ours
        this.this_tr=this.element.closest('tr');

        this.inline_id=this.element.attr('id')+"_ex";

        // Substitute current row with a properly formatted "inline editing template"
        this.this_tr.hide();
        this.this_tr.after("<tr id='"+this.inline_id+"' class='lister_editrow'><td colspan="+this.this_tr.children().length+"><div class='editrow_rel'><div class='editrow_top'><div></div></div><div class='editrow_left'></div><div class='editrow_right'></div><div class='loading'>Loading...</div></div></td></tr>"
                );

        var l=$('.loading');
        l.animate({height: "6em"},1500);

        // Define local variables (because we will need them in functions)
        var inline_id_ref=$('#'+this.inline_id);

        // Replace contents of newly added row with AJAX
        $.ajax({
            type: "GET",
            url: this.element.attr('rel'),
            success: function(res){
                // Firefox ignores <script>, so we need to extract it and evaluate it
                // individually. But we need to load HTML first
                l.stop();
                html=res.replace(/<script(.|\s)*?\/script>/g, "");
                inline_id_ref.html(html);

                // We are going to evaluate each piece, but there is some mess with newlines
                res=res
                  .replace(new RegExp("\\n","g"),' ')
                  .replace(new RegExp("\\r","g"),' ')
                  .replace(/<script[^>]*>(.*?)<\/script>/ig, function(a,b){ 
                    eval(b);
                    })
                ;

            }
        });
        this.expanded=true;
    },
    collapse: function() {
        // We are closing ourselves

        if(!this.expanded)return false;

        var remove_this=this.inline_id;

        this.this_tr.show();
        $('#'+remove_this).remove();
        this.expanded=false;
    }

});
