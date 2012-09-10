/* Welcome to Agile Toolkit JS framework. This file implements Form. */

// The following HTML structure should be used:
//
// <div id='formid'>     <!-- binding to this element -->
// <form action="url">
//   ..
//
//
//   <... class='atk-field'>
//     <input id='formid_fieldid' type=> <!-- could be any type really -->
//     field, etc
//   </...>
//   <error inserts here
//
//   ..repeats..
//
//   <div class="field-error-template" style="display: none"> .<span class="field-error-text">error template</span> </div>
// </form>	
//</div>

jQuery.widget("ui.atk4_form", {

	form: undefined,
	id: undefined,

	submitted: undefined,
	base_url: undefined,
	loading: false,
	plain_submit: false,

	template: {},

	_getChanged: function(){
		return this.form.hasClass('form_changed');
	},
	_setChanged: function(state){
        if(this.element.is('.ignore_changes')){
            this.form.removeClass('form_changed');
            return;
        }
		if(state)this.form.addClass('form_changed');else this.form.removeClass('form_changed');
	},

    _create: function(){
		var self=this;

		this.id=this.element.attr('id');
		this.form=this.element;

		// If we are not being bound to form directly, then find form inside ourselves
		if(!this.form.is('form')){
			this.form=this.form.find('form');
			this.element.bind('submit',function(ev){
				ev.preventDefault();
				self.submitForm();
			});
		}

		console.log('created with url=',this.form.attr('action'));

		this.form.append('<input name="ajax_submit" id="ajax_submit" value="1" type="hidden"/>');
		this.form.addClass('atk4_form');
		this.element.addClass('atk4_form_widget');

		this.form.find('input').bind('keypress',function(e){
			if($(this).is('.ui-autocomplete-input'))return true;
			if(e.keyCode==13){
				$(this).trigger('change');
				self.submitForm();
				return false;
				}
		});

		if($.browser.msie){
			this.form.find('input:radio,input:checkbox').click(function(){
				this.blur();	// this will call onchange event, like it should
				this.focus();
			});
		}

		this.form.find(':input').each(function(){
			if($(this).attr('type')=='checkbox')
				$(this).attr('data-initvalue',$(this).attr('checked'))
			else
				$(this).attr('data-initvalue',$(this).val())
			})
		.bind('change',function(ev){
				//if($(this).attr('type')=='checkbox')
            if($(this).attr('data-initvalue')==$(this).val()){
                ev.preventDefault();
                return;
            }else {
                $(this).attr('data-initvalue',$(this).val());
            }
            self._setChanged(true);
        });
		

		this.form.find('input[type=radio]').click(function(){
			self._setChanged(true);
		}).change(function(){
			self._setChanged(true);
		});

		// This class defines field error template
		// <div class="field-error-template"> .. <span class="field-error-text">..</span></div>

		// The following markup is using to show that form field contains errors.
		this.template['field_error']=this.element.find('.field-error-template').remove();
		if(!this.template['field_error'].length)console.log('Warning: form template does not have form-error-template class');

		this.form.submit(function(e){

			if(self.plain_submit){
				e.stopPropagation();
				self.plain_submit=false;
				return;// executes default action
			}

			e.stopPropagation();
			e.preventDefault();
			self.submitForm();
		});

		//this.element.find('.form_error').remove();

		/*
		-- obsolete code
		this.form.find('input[type=submit]').each(function(){
			var a=$('<a class="gbutton button_style1"></a>');
			var s=$(this);
			a.text(s.attr('value'));
			a.attr('tabIndex',s.attr('tabIndex')+1);
			a.click(function(){
				form.element.submit()});
			$(this).after(a);
			s.remove();
		});
		*/
		this.base_url=window.location.href.substr(0,window.location.href.indexOf('#'));
        if(!this.base_url)this.base_url=window.location.href;
		if(this.options.base_url)this.base_url=this.options.base_url;
    },
	submitPlain: function(){
		// Disable AJAX handling, perform submit to a specified target then restore functionality.
		// This function is used by file upload
		this.plain_submit=true;
		this.form.trigger('submit');
	},
	setFieldValue: function(field_name,value){
		var f=$('#'+this.id+'_'+field_name);


		if(!f.length){
			console.log('Unable to find field ',field_name,' and fill in ',value);
			return;
		}

		if(f.hasClass('field_reference')){
			var opt=f.find('option[value='+value+']');

			if(opt.length){
				// If it was found - then set value, should change dropdown
				f.val(value).change().trigger('change_ref');
				return;
			}

			var self=this;

			this.reloadField(field_name,null,function(){
				var f=$('#'+self.id+'_'+field_name);
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
	reloadField: function(field_name,url,fn,notrigger){
		var field_id=this.id+'_'+field_name;
		if(!url)url=this.base_url;
		console.log('Field reloading: ',field_name);

		url=$.atk4.addArgument(url,this.id+'_cut_field',field_id);
		var f=$("#"+field_id);

		if(!notrigger)f.trigger('reload_field');

		//if(f.hasClass('field_reference')){
			var f2=f.closest('.atk-form-field');
			//f.remove();
			f=f2;


		//}
		var c=this._getChanged();this._setChanged(false);
		f.atk4_load(url,fn);
		this._setChanged(c);
	},
	fieldError: function(field_name,error){

		if(this.options.error_handler){
			// Allows you to define custom error display handler
			return this.options.error_handler(field_name,error);
		}

		var field=
			typeof(field_name)=='string'?
				$('#'+this.id+'_'+field_name):
				field_name;

		if(!field.length){
			field=this.form.find('[name="'+field_name+'"]');
		}
		if(!field.length){
			alert('Field not found: '+field_name+', error is: '+error);
			return;
		}

		if(!error || error=='0')error="must be specified properly";


		field.focus();

		//field.closest('.form_field').find('.field_hint').hide();
		field.closest('form').find('.field_error').remove();

		// highlight field
		var field_highlight=field.closest('.atk-form-row').addClass('has-error').find('.atk-form-field');

		// Clear previous errors
		field_highlight.children('.atk-form-error').remove();

		if(!this.template['field_error'].length){
			// no template, use alert;
			alert(error);
			return;
		}
		var error_bl=this.template['field_error'].clone();
        
        // One of the below would find the text. This is faster appreach than
        // doing find('*').andSelf().filter('.field-error-text');
		error_bl.find('.field-error-text').
            add(error_bl.filter('.field-error-text')).text(error);

		error_bl.appendTo(field_highlight).fadeIn();

		this.form.addClass('form_has_error');

		/*
		h=$(//'<div class="clear"></div><span class="form_error"><i></i>'+error+'</span>');
		 '<dd class="atk-error"><div class="ui-state-error ui-corner-all"><span class="ui-icon"></span>'+
			 error+'</div></dd>').insertAfter(field_highlight);
		*/

		error_bl.click(function(){ $(this).closest('.has-error').removeClass('has-error'); error_bl.remove(); });

		var self=this;

		// make it remove error automatically when edited
		field.bind('change.errorhide',function(){
			var t=$(this);
			t.unbind('change.errorhide');

			self.form.removeClass('form_has_error');
			field_highlight.closest('.has-error').removeClass('has-error');
			error_bl.fadeOut(function(){ error_bl.remove(); });
		});
	},
	clearError: function(field){
		if(field){
			field=field.closest('.has-error');
		}else{
			field=this.form.find('.has-error');
		}
		field.find('.atk-form-error').remove();
		field.removeClass('has-error');

		//if(!this.form.find('.field_has_error').length)this.form.removeClass('form_has_error');
	},
	submitForm: function(btn){
		var params={}, form=this;
		if(form.loading){
			$.univ().loadingInProgress();
			return false;
		}

		// btn is clicked
		var richtext=this.element.find('.atk4_richtext');
		if(richtext.length)richtext.atk4_richtext('changeHTML');
		params=this.element.find(":input").serializeArray()
		if(btn){
            for (var el in params){
                if (params[el].name == 'ajax_submit'){
                    params[el].value=btn;
                    break;
                }
            }
        }

		var properties={
			type: "POST",
			url: this.form.attr('action')
		};

		form.loading=true;
		$.atk4.get(properties,params,function(res){
			var c=form._getChanged();form._setChanged(false);

			if(!$.atk4._checkSession(res))return;
			/*
			if(res.substr(0,5)=='ERROR'){
				$.univ().dialogOK('Error','There was error with your request. System maintainers have been notified.');
				return;
			}
			*/
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
			form.loading=false;
			form._setChanged(c);
		},function(){
			form.loading=false;
		});
	}
});
