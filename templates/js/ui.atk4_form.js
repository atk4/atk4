/* Welcome to Agile Toolkit JS framework. This file implements Form. */

// The following HTML structure should be used:
//
// <form action="url">     <!-- binding to this element -->
//   ..
//     <input type=> <!-- could be any type really -->
//     <input type=> <!-- could be any type really -->
//     <input type=> <!-- could be any type really -->
//
//	 <img class="form_loading">
//		

$.widget("ui.atk4_form", {

	loading: undefined,
	submitted: undefined,
	base_url: undefined,
    _init: function(options){
        $.extend(this.options,options);
        var form=this;

		this.element.addClass('atk4_form');

		this.loading=this.element.find('img.form_loading');

		$('input').live('keypress',function(e){
			if($(this).is('.ui-autocomplete-input'))return true;
			if(e.keyCode==13){
				$(this).closest('form').submit();
				return false;
			}
		});

		this.loading.click(function(){
			form.indicateLoading();
		});

		this.overlay_prototype=this.element.find('.form_iedit');

		this.element.submit(function(e){
			e.preventDefault();
			form.submitForm();
		});

		this.element.find('.form_error').remove();

		this.element.find('input[type=submit]').each(function(){
			var a=$('<a class="gbutton button_style1"></a>');
			var s=$(this);
			a.text(s.attr('value'));
			a.attr('tabIndex',s.attr('tabIndex')+1);
			a.click(function(){ 
				form.element.submit()});
			$(this).after(a);
			s.remove();
		});
		this.base_url=window.location.href.substr(0,window.location.href.indexOf('#'));
		if(this.options.base_url)this.base_url=this.options.base_url;
    },
	indicateLoading: function(){
		var img=this.loading.attr('src').replace('not_loading','loading');
		this.loading.attr('src',img);
	},
	indicateNotLoading: function(){
		var img=this.loading.attr('src').replace('loading','not_loading');
		this.loading.attr('src',img);
	},
	setFieldValue: function(field_name,value){
		var f=$('#'+this.element.attr('id')+'_'+field_name);

		if(!f.length){
			console.log('Unable to find field ',field_name,' and fill in ',value);
			return;
		}

		if(f.hasClass('field_reference')){
			console.log('Field ',field_name,' is a reference, see if we can find value');

			var opt=f.find('option[value='+value+']');
			console.log('Found: ',opt.length);

			if(opt.length){
				// If it was found - then set value, should change dropdown
				f.val(value).change().trigger('change_ref');
				return;
			}

			var form=this;

			this.reloadField(field_name,function(){
				var f=$('#'+form.element.attr('id')+'_'+field_name);
				var opt=f.find('option[value='+value+']');
				if(opt.length){
					f.val(value).change().trigger('change_ref');
				}else{
					console.log('even after field reload, couldnt select ',value,' for ',field_name);
				}
			});
			return;
		}

		f.val(value).change();
	},
	reloadField: function(field_name,fn){
		var field_id=this.element.attr('id')+'_'+field_name;
		var url=this.base_url;

		url=$.atk4.addArgument(url,"cut_object="+field_id);

		var f=$("#"+field_id);
		if(f.hasClass('field_reference')){
			var f2=f.prev();
			f.remove();
			f=f2;


		}
		f.atk4_reload(url,null,fn);
	},
	fieldError: function(field_name,error){
		var field=
			typeof(field_name)=='string'?
				$('#'+this.element.attr('id')+'_'+field_name):
				field_name;

		if(!field.length){
			field=this.element.find('[name="'+field_name+'"]');
		}
		if(!field.length){
			alert('Field not found: ',field_name,', error is',error);
			return;
		}

		if(!error)error="must be specified properly";
		field.closest('.form_field').find('.field_hint').hide();
		field.closest('form').find('.field_error').remove();

		var h;

		if($('body').attr('rel')=='front'){
			h=$('<span class="form_error field_error"><i></i>'+error+'</span>').
				appendTo(field.closest('dd'));
		}else if ((jQuery.browser.msie && parseInt(jQuery.browser.version) <= 7)) {
			field.closest('dl,td').addClass('form_has_error');
			h=$('<span class="form_error2 field_error"><i></i>'+error+'</span>')
				.insertAfter(field);
		}else{
			// highlight field
			field.closest('dl,td').addClass('form_has_error');
			h=$(//'<div class="clear"></div><span class="form_error"><i></i>'+error+'</span>');
'<div class="field_hint form_error">'+
'	<div class="field_hint_wrap">'+
'		<p><i></i>'+error+'</p>'+
'		<div class="f_h_cn f_h_tl"></div>'+
'		<div class="f_h_cn f_h_tr"></div>'+
'	</div>'+
'	<div class="f_h_cn f_h_bl"></div>'+
'	<div class="f_h_cn f_h_br"></div>'+
'</div>').insertAfter(field);
		}

		h.find('.field_hint_wrap').click(function(){ $(this).parent().remove(); });


		// make it remove error automatically when edited
		field.bind('focus.errorhide',function(){
			var t=$(this);
			t.unbind('focus.errorhide');
			t.closest('.form_field').find('.field_hint').hide();
			t.closest('dl,td').removeClass('form_has_error');
			t.closest('dl,td').find('.form_error').remove();

		});
	},
	clearError: function(field){
		if(!field){
			field=this.element.find('.form_has_error');
		}else{
			field=field.closest('.form_has_error');
		}

		field.closest('.form_field').find('.field_hint').hide();
		field.closest('dl,td').find('.form_error').remove();
		field.removeClass('form_has_error');
	},
	submitForm: function(btn){
		var params={}, form=this;
		this.element.find("input[checked], input[type='text'], input[type='hidden'], input[type='password'], input[type='submit'], option[selected], textarea")
		.each(function() {
			if(this.disabled || this.parentNode.disabled)if(!$(this).hasClass('submit_disabled'))return;

			params[ this.name || this.id || this.parentNode.name || this.parentNode.id ] = this.value;
		});

		params['ajax_submit']=1;

		// btn is clicked
		if(btn)params[btn]=1;

		$.post(this.element.attr('action'),params,function(res){
			if(res.substr(0,5)=='ERROR'){
				$.univ().dialogOK('Error','There was error with your request. System maintainers have been notified.');
				return;
			}	
			try {
				eval(res);
			}catch(e){
				//while some browsers prevents popup we better use alert
				w=window.open(null,null,'height=400,width=700,location=no,menubar=no,scrollbars=yes,status=no,titlebar=no,toolbar=no');
				if(w){
					w.document.write('<h2>Error in AJAX response: '+e+'</h2>');
					w.document.write(res);
					w.document.write('<center><input type=button onclick="window.close()" value="Close"></center>');
				}else{
					alert("Error in AJAX response: "+e+"\n"+res);
				}
			}
		});
	}
});

