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
	highlightRow: function(id){
		var el=this.element.find('tbody').eq(0).children('tr[data-id='+id+']');
		el.effect('highlight',3000);
	},
	removeRow: function(id){
		var el=this.element.find('tbody').eq(0).children('tr[data-id='+id+']');
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

		// maybe it was new? Then use it
		grid.element.find('tr[rel=new]').attr('rel',id);


		var args={};
		args[this.name+'_reload_row']=id;
		args[0]=this.base_url;
		grid.element.find('tr[rel='+id+']').atk4_reload(args,[],function(){
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
