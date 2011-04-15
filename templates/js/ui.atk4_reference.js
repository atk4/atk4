/* Welcome to Agile Toolkit JS framework. This file implements Hint. */

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

$.widget("ui.atk4_reference", {
    options: {
    },
	shown: true,
	autocomplete: null,
    _init: function(options){
        $.extend(this.options,this.default_options,options);
		this.name=this.element.attr('id');
		this.element.css({cursor: 'pointer'});
		//var def=this.element.prepend('<option'
			//+(this.element.find('option[selected]').length?'':' selected')+'> .. </option');
	},
	showAddDialog: function(){

	},
	initAutocomplete: function(ac_options){
		// Add new field after ourselves for auto-complete
		//var t=$('<span class="input_autocomplete wo_button"><span><i></i><input class="input_style1" id="'+this.name+'_autocomplete"/></span></span>');
		this.element.combobox();



		var dropdown=this.element.prev();

		this.autocomplete=$('#'+this.name+'_autocomplete');

        this.autocomplete.autocomplete();
        return;


		this.autocomplete.attr('style',this.element.attr('style'));
		this.autocomplete.attr('tabindex',this.element.attr('tabindex'));
		this.element.attr('tabindex',null);


		if(this.element.val()){
			var d=this.element.find('option[value='+this.element.val()+']').text();
			if((i=d.indexOf('<br/>'))>=0)d=d.substr(0,i);
			this.autocomplete.val(d);
		}

		if(!this.options.leave_dd){
			this.element.hide();
		}

		var ref=this;
		var types=this.element.attr('nomtypes');
		if(types)types=eval('('+types+')');

		//t.find('i').click(function(){ ref.autocomplete.trigger('focus_ref'); });

		this.autocomplete.autocomplete($.extend({
			minChars: 0,
			mustMatch: true,
			data:this.getData(),
			matchContains: true,
			//data:[[1,'january'],[2,'february'],[3,'march']],
			formatItem: function(data,i,total){
				var d=data[1],i;
				if(types)d=d+'<br/><small><i>'+types[data[0]]+'</i></small>';
				return d;
			},
			formatResult: function(data){
				return data[1];
			},
			result: function(ev,data){
				/*
				console.log('removing');
				ref.element.next('a.autocomplete_add').remove();
				ref.element.val(data[1]);
				*/

				ref.fixBrokenAutocomplete(this);
				/*
				var fields = $(this).parents('form:eq(0),body').find('button,input,textarea,select');
				var index = fields.index( this );
				if ( index > -1 && ( index + 1 ) < fields.length ) {
					fields.eq( index + 1 ).focus();
				}
				*/

			}

		},ac_options));

		this.element.change(function(){
			ref.autocomplete.val($(this).children(':selected').text());
		});
		this.autocomplete.blur(function(){
			ref.fixBrokenAutocomplete(this);
		});
		this.autocomplete.keyup(function(){
			ref.fixBrokenAutocomplete(this);
		});

		this.autocomplete.focus(function(){
			ref.element.trigger('focus_ref');
		});

		this.autocomplete.mouseover(function(){
			ref.element.mouseover()
		});
		this.autocomplete.mouseout(function(){
			ref.element.mouseout()
		});

		if(ref.element.attr('disabled')){
			this.autocomplete.attr('disabled',true);
		}

/*
		this.autocomplete.click(function(e){
			e.preventDefault();
			alert('clicked me');
			//ref.autocomplete.click();
		});
*/

	},
	isNewEntry: function(el){
		var val=$(el).val();
		var ref=this;

		// Not adding empty entries
		if(!val && ref.element.val()!=''){
			ref.element.val('');
			if(ref.element.attr('data-initvalue')!=ref.element.val()){
				ref.element.attr('data-initvalue',ref.element.val());
				ref.element.trigger('change_ref');
			}
			return false;
		}

		// Matches selected entry
		if(this.element.find(':selected').text() == val)return false;

		var matches_other=false;
		this.element.children().each(function(){
			if($(this).text()==val){
				ref.element.val($(this).val());
				if(ref.element.attr('data-initvalue')!=ref.element.val()){
					ref.element.attr('data-initvalue',ref.element.val());
					ref.element.trigger('change_ref');
				}
				matches_other=true;
			}
		});

		// Matches other entry
		if(matches_other)return false;

		if(ref.element.val()!=''){
			ref.element.val('');
		}
		return true;
	},
	fixBrokenAutocomplete: function(el){
		var ref=this;

		if(this.isNewEntry(el)){
		}else{
		}
	},
	setPlusUrl: function(url,options,title){
		this.options.plus_url=url;
		var ref=this;
		this.autocomplete.parent().after('<a href="'+this.options.plus_url+'" id="'+this.name+'_addlink" class="ref_model_add autocomplete_add gbutton button_style2">+</a>');
		this.autocomplete.parent().parent().removeClass('wo_button');

		$('#'+this.name+'_addlink').click(function(ev){
			ev.preventDefault();
			$(this).univ().dialogURL('Adding new '+(title?title:'..'),ref.options.plus_url
				+"&val_txt="+escape(ref.autocomplete.val())
				+"&val_id="+escape(ref.element.val())
				,options,function(){
				region.find('.fill_current').val(ref.element.val()).change();
			});
		});
	},
	getData: function(){
		var data=[];
		this.element.children('option').each(function(key,val){
			if($(val).val()){
				data.push([$(val).val(),$.trim($(val).text())]);
			}
		});
		return data;
	}
});
