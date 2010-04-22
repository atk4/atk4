/* Welcome to Agile Toolkit JS framework. This file implements checkboxes / selectable on Grid. */
$.widget("ui.atk4_checkboxes", {
	_init: function(options){
		var chb=this;
		var ivalue=$(this.options.dst_field).val();
		
		try{
			if($.parseJSON){
				ivalue=$.parseJSON(ivalue);
			}else{
				ivalue=eval('('+ivalue+')')
			}
		}catch(err){
			ivalue=Array();
		}
		$.each(ivalue,function(k,v){
			ivalue[k]=String(v);
		});
		
		console.log('initilaising with field: ',this.options.dst_field,', init value: ',ivalue);
		
		this.element.find('tbody').selectable({filter: 'tr',stop: function(){ chb.stop.apply(chb,[this]) }}).css({cursor:'crosshair'});
		this.element.find('input[type=checkbox]')
		.each(function(){
			var o=$(this);
			console.log('checking if ',o.val(),' is in array ',ivalue);
			if($.inArray(o.val(), ivalue)>-1){
				console.log('found');
				o.attr('checked',true);
				$(this).closest('tr').addClass('ui-selected');
			}
		})
		.change(function(){
			var tr=$(this).closest('tr');
			if($(this).attr('checked')){
				tr.addClass('ui-selected');
			}else{
				tr.removeClass('ui-selected');
			}
			chb.recalc();
		});
	},
	stop: function(c){
		$(c).children('.ui-selected').find('input').attr('checked',true);
		$(c).children().not('.ui-selected').find('input').removeAttr('checked',true);
		this.recalc();
	},
	recalc: function(){
		var r=[];
		this.element.find('input:checked').each(function(){
			r.push($(this).val());
		});
		console.log(this.options.dst_field);
		if(this.options.dst_field){
			$(this.options.dst_field).val($.univ.toJSON(r));
		}
	}
});
