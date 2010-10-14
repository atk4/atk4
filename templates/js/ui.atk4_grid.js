/* Welcome to Agile Toolkit JS framework. This file implements Grid. */

// The following HTML structure should be used:
//
// <table>     <!-- binding to this element -->
//   <thead>
//     <th>..
//    </thead>
//   <tr rel="23">	<!-- id of this row, important! -->
//      <td class="grid_cell">
//      <td class="grid_cell">
//

$.widget("ui.atk4_grid", {
	width: 0,
	name: undefined,
	overlay_prototype: undefined,
	row_prototype: undefined,
	base_url: undefined,
	loading: false,

	_create: function(){
		// If there are sortable things, sort them
		var self=this;


		var len=window.location.href.indexOf('#');
		if(len<0)len=window.location.href.length;
		this.base_url=window.location.href.substr(0,len);
		if(this.options.base_url)this.base_url=this.options.base_url;
		this.element.addClass('atk4_grid');
		this.name=this.element.attr('id');


		/*
		var thead=this.element.find('thead');
		thead.find('a').click(function(ev){
			ev.preventDefault();
			self.element.atk4_load({0:$(this).attr('href'),cut_object:self.element.attr('id')});


		});
		*/

	},
	init17: function(){



		this.initTableContent();

		//this.element.find('tbody').sortable();


		this.checkIfEmptyTable(false);
    },

    initTableContent: function(){
        var grid=this;

		if(this.options.removeEditCell){
			var table=this.element.find('.grid_content').find('table').eq(0);
			table.children('thead').find('th:last').remove();
			table.children('tbody').children('tr').not('.overlay_prototype').each(function(){
				$(this).children('td:last').remove();
			});

		}

		this.overlay_prototype=this.element.find('.overlay_prototype');
		this.row_prototype=this.element.find('.row_prototype');
		this.width=this.element.find('thead').find('th').length;
		//
		// if tabs are defined - make them clickable
		if(this.options.tabs){
			this.element.find('tbody').css({cursor:'pointer'});
			this.element.find('td.grid_cell').click(function(){

					grid.element.find('tr[rel=new]').remove();
					grid.click($(this));
					});
		}

		this.element.find('tr.overlay_prototype').prev().children().css({'border-bottom':'none'});
	},
	overlay: undefined,
	current_overlay: undefined,
	current_id: undefined,
    // Main menu clicking function
	openById: function(id,tab_to_open){
		return this.click(
				this.element.find('tr[rel='+id+']'),
				null,
				tab_to_open);
	},
    click: function(el,tabs,tab_to_open){
		// If tabs are not specified options.tabs are used.
		var tr=el.closest('tr');
		var id=tr.attr('rel');
		var grid=this;

		if(!el.length)return;

		// if there is inline already - lets see if we can close it
		if(this.current_overlay){
			// verify something here

			if(grid.element.find('.atk_loader').length && !grid.element.find('.atk4_loader').atk4_loader('remove'))return;
			this.current_overlay.next().show();
			this.current_overlay.remove();
		}

		/*
		if(id && this.overlay[id]){

			// OPPORTUNITY:
			//  we can use google gears here to check for cached results


			log.log('showing exsiting');
			this.current_overlay=this.overlay[id].show();
			return;
		}
		*/

		var overlay=this.overlay_prototype.clone();
		this.current_id=id;

		//this.element.find('th,.grid_panel').animate({opacity: "0.7"});
		//this.element.find('.grid_body').children().not('tr.form_iedit').css({opacity: "0.7"});
		//this.element.find('.grid_content').css({'borderColor':'#eae7e0'});

		overlay.find('td').eq(0).attr('colSpan',this.width);
		overlay.find('.overlay_close').click(function(){
			if(grid.element.find('.atk4_loader').atk4_loader('remove'))grid.removeOverlay();
		});
		overlay.find('.overlay_save').click(function(){
			overlay.find('form').eq(0).submit();
		});

		this.current_overlay=overlay;

		var current_tab=overlay.find('.ibox_tabs').children().eq(0);
		var last_tab=undefined;
		var grid=this;

		if(!tabs)tabs=this.options.tabs;

		tr.before(overlay);
		for(var tab in tabs){
			if(!current_tab.length)current_tab=last_tab.clone().insertAfter(last_tab);
			current_tab.attr('rel',tab);
			current_tab.find('span').html(tabs[tab]);
			current_tab.click(function(){
				grid.clickTab(this,grid);
			});
			if(((!tab_to_open) && (!last_tab)) || (tab_to_open==tabs[tab])){
				// this is right tab - lets load content
				if(!last_tab){
					// No need to open first tab
					this.loadTab(tab,true);
				}else{
					tab_to_open=current_tab;
				}
			}
			last_tab=current_tab;
			current_tab=current_tab.next();
		}
		if(tab_to_open && tab_to_open.click)tab_to_open.click();
		// If there are any additional tabs left - we delete them
		while(current_tab.length){
			last_tab=current_tab;
			current_tab=current_tab.next();
			last_tab.remove();
		}


		overlay.show().find('td').slideDown();
		tr.hide();

		this.checkIfEmptyTable(true);

		/*
		if(id){
			this.overlay[id]=overlay;
		}
		*/

    },
	removeOverlay: function(){
		if(this.current_overlay){
			this.current_overlay.next().show();
			this.current_overlay.remove();
		}
		this.element.find('tr[rel=new]').remove();
		//this.element.find('th,.grid_panel').css({opacity: "1"});
		//this.element.find('tr').not('tr.form_iedit').css({opacity: "1"});
		//this.element.find('.grid_content').css({'border-color':'#B7AE96'});
		this.checkIfEmptyTable(true);
	},
	newEntry: function(tabs){
		var grid=this;
		if(grid.loading){
			$.univ().loadingInProgress();
			return;
		}
		if(grid.element.find('.atk4_loader').length && !grid.element.find('.atk4_loader').atk4_loader('remove'))return;
		this.element.find('tr[rel=new]').remove();
		this.removeOverlay();
		var el=$('<tr rel="new"/>');
		for(var i=0;i<this.width;i++){
			el.append('<td class="grid_cell" nowrap="">&nbsp;</td>');
		}
		this.element.find('tbody.grid_body').prepend(el);

		var grid=this;

        el.find('td.grid_cell').click(function(){
            grid.click($(this));
        });
		console.log('About to click.......');
		this.click(el.find('td.grid_cell').eq(0),tabs);
	},
	clickTab: function(tab,grid){
		var tab=$(tab);

		this.loadTab(tab.attr('rel'),false,function(){
			if(!tab.hasClass('tabsel')){

				tab.parent().find('.tabsel').removeClass('tabsel');
				tab.addClass('tabsel');
			}
		});

	},
	loadTab: function(page,scroll,fx){
		var content=this.current_overlay.find('.ibox_content').eq(0);
		var ov=this.current_overlay;
		var self=this;

		self.loading=true;
		page=$.atk4.addArgument(page,'cut_region=Content');
		console.log('loading tab data now..');
		content.atk4_load(page+'&id='+this.current_id,function(){
			self.loading=false;
			if(scroll)$.scrollTo(ov,200);
			if(fx)fx();
		});
	},
	highlightRow: function(id){
		this.removeOverlay();
		this.element.find('tr[rel='+id+']').children().effect('highlight',null,3000);
	},
	removeRow: function(id){
		this.removeOverlay();
		var el=this.element.find('tr[rel='+id+']');
		//el.children().effect('highlight',{color: 'red'},1000);
		var grid=this;
		// causes problem!
		el.children().animate({'color':'white'}, function(){
			el.remove();
			grid.checkIfEmptyTable(true);
		});
	},

	// Further is a series of functions which call ourselves to find out more information
	//
	// We train our grid in the ways of self-awarness. It will be able to reload
	// itself either entirely or partial rows

	requestData: function(options,fn){
		// sends request to same URL but with options

		// TODO: if quicksearch is activated add filter here

		// TODO: if pagination is activated, add parameter here

		// amodules wants this:
		options['expanded']=this.name;

		var url=this.base_url;
		if(options){
			for(var key in options){
				if(url.indexOf('?')==-1)url+='?';else url+='&';
				url+=key+'='+options[key];
			}
		}
		$.get(url,fn);
	},
	reloadRow: function(id,fn){
		var grid=this;
		console.log("reload with ",id,fn);


		// maybe it was new? Then use it
		grid.element.find('tr[rel=new]').attr('rel',id);


		this.removeOverlay();

		var args={};
		args[this.name+'_reload_row']=id;
		args[0]=this.base_url;
		grid.element.find('tr[rel='+id+']').atk4_load(args,function(){
			if(fn)fn;else grid.highlightRow(id);

		});
		/*
		replaceWith(content);
		this.requestData(args,function(content){
			grid.element.find('tr[rel='+id+']').replaceWith(content);
			var el=grid.element.find('tr[rel='+id+']');
			//el.children('td:last').remove();
			//el.find('td.grid_cell').click(function(){
			   	//grid.click($(this));
			//});
			if(fn)fn();else grid.highlightRow(id);
		});
		*/
	},
	reload: function(){
		var url=this.base_url;
		url=$.atk4.addArgument(url,'cut_object='+this.name);
		this.element.atk4_grid('destroy');
		this.element.atk4_reload(url);
	},
	reloadData: function(q){
		var url=this.base_url;
		var t=this;
		url=$.atk4.addArgument(url,'cut_object='+this.name);
		url=$.atk4.addArgument(url,'cut_region=grid_content');
		$.each(q,function(k,v){
			url=$.atk4.addArgument(url,escape(k)+'='+escape(v));
		});
		t.element.find('.grid_content').atk4_reload(url,null,function(){
			t.initTableContent();
		});
	},
	checkIfEmptyTable: function(ok_to_reload){
		this.element.find('.lister_notfound_nojs').remove();
		if(x=this.element.find('tbody').children('tr').not(':hidden').length){
			this.element.find('.lister_notfound').hide();
		}else{
			if(ok_to_reload){
				var p=this.element.find('.grid_paginator');
				var l=p.children().length;
				if(l){
					console.log('Current page have no more rows, so we are refreshing page');
					//this.reload();
				}

			}
			this.element.find('.lister_notfound').show();
		}
	}
});
