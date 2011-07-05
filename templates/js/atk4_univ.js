/* Welcome to Agile Toolkit JS framework. This file provides universal chain. */

// jQuery allows you to manipulate element by doing chaining. Similarly univ provides
// loads of simple functions to perform action chaining.
//

;
$||console.error("jQuery must be loaded");
(function($){


$.each({
	alert: function(a){
		alert(a);
	},
	setTimeout: function(code,delay){
		setTimeout(code,delay);
	},
	displayAlert: function(a){
		alert(a);
	},
	redirect: function(url,fn){
		if($.fn.atk4_load && $('#Content').hasClass('atk4_loader')){
			$.univ.page(url,fn);
		}else{
			document.location=url;
		}
	},
	redirectURL: function(page,fn){
		if($.fn.atk4_load && $('#Content').hasClass('atk4_loader')){
			$.univ.page(page,fn);
		}else{
			document.location=page;
		}
	},
	location: function(url){
		if(!url)document.location.reload(true);else
		document.location=url;
	},
	page: function(page,fn){
		$('#Content').atk4_load(page,fn);
	},
	log: function(arg1){
		if(console)console.log(arg1);
   	},
	consoleError: function(arg1){
		if(console){
            if(console.error)console.error(arg1);
            else console.log('Error: '+arg1);
        }
   	},
	confirm: function(msg){
		if(!msg)msg='Are you sure?';
		if(!confirm(msg))this.ignore=true;
	},
	displayFormError: function(form,field,message){
		console.log(form,field,message);
		if(!message){
			message=field;
			field=form;
		}
		if(form){
			var el=$(form);
			// TODO - pass on action to form widget

		}
		this.alert(field+": "+message);
	},
	setFormFocus: function(form,field){
		$('#'+form+' input[name='+form+'_'+field).focus();
	},
	closeExpander: function(){
		var e=this.jquery.closest('.lister_expander').parent().prev().find('.expander');
		if(!e.length)e=$('.expander');

        e.atk4_expander('collapse');
	},
	closeExpanderWidget: function(){
		this.closeExpander();
	},
	reloadExpandedRow: function(id){
		if(!id)id=this.jquery.closest('.lister_expander').parent().prev().attr('rel');
		this.closeExpander();
		var g=this.jquery.closest('.atk4_grid');
		g.atk4_grid('reloadRow',id);
	},
	removeExpandedRow: function(id){
		if(!id)id=this.jquery.closest('.lister_expander').parent().prev().attr('rel');
		this.closeExpander();
		var g=this.jquery.closest('.atk4_grid');
		g.atk4_grid('removeRow',id);
	},
	loadRegionUrlEx: function(id,url){
		$('#'+id).load(url);
	},
	memorizeExpander: function(){ },
	submitForm: function(form,button,spinner){
		var successHandler=function(response_text){
			if(response_text){
				try {
					eval(response_text);
				}catch(e){
					//while some browsers prevents popup we better use alert
					w=window.open(null,null,'height=400,width=700,location=no,menubar=no,scrollbars=yes,status=no,titlebar=no,toolbar=no');
					if(w){
						w.document.write('<h2>Error in AJAX response: '+e+'</h2>');
						w.document.write(response_text);
						w.document.write('<center><input type=button onclick="window.close()" value="Close"></center>');
					}else{
						showMessage("Error in AJAX response: "+e+"\n"+response_text);
					}
					try{
						eval(response_text.substring(response_text.indexOf('//ajax_script_start'),response_text.lastIndexOf('//ajax_script_end')));
					} catch(e) {
						if(w){
							w.document.write('<h2>Error in AJAX response: '+e+'</h2>');
							w.document.write(response_text);
							w.document.write('<center><input type=button onclick="window.close()" value="Close"></center>');
						}else{
							showMessage('Could not parse response. '+e);
						}
					}
				}
			} else {
				showMessage("Warning: Empty response from server");
			}
			if(spinner)spinner_off(spinner);
		};
		// adding hidden field with clicked button value
		if(button){
			btn_value=button.substring(button.lastIndexOf('_')+1);
			button=$('<input name="'+button+'" id="'+button+'" value="'+btn_value+'" type="hidden">');
			$('#'+form).append(button);
		}
		// adding a flag for ajax submit
		$(form).append($('<input name="ajax_submit" id="ajax_submit" value="1" type="hidden">'));
		$(form).ajaxSubmit({success: successHandler});
		// removing hidden field
		if(button)button.remove();
	},
	addArgument: function(url,args){
		return $.atk4.addArgument(url,args);
	},
	reloadArgs: function(url,key,value){
		var u=$.atk4.addArgument(url,key+'='+value);
		console.log(url);
		this.jquery.atk4_load(u);
	},
	reload: function(url,arg,fn){
		/*
		 * $obj->js()->reload();	 will now properly reload most of the objects.
		 * This function can be also called on a low level, however URL have to be
		 * specified.
		 * $('#obj').univ().reload('http://..');
		 *
		 * Difference between atk4_load and this function is that this function will
		 * correctly replace element and insert it into container when reloading. It
		 * is more suitable for reloading existing elements
		 */

		this.jquery.atk4_reload(url,arg,fn);
	},
	reloadParent: function(depth,args){
		if(!depth)depth=1;
		var atk=this.jquery;
		var patk=atk;
		while(depth-->0){
			atk=atk.closest('.atk4_loader');
			if(atk.length)patk=atk;
			if(!depth){
				if(atk.length)atk.atk4_loader('reload',args);else
					patk.atk4_loader('reload',args);
			}else{
				atk=atk.parent();
			}
		}
	},
	reloadContents: function(url,arg,fn){
		/*
		 * Identical to reload(), but instead of reloading element itself,
		 * it will reload only contents of the element.
		 */

		if(arg){
			$.each(arg,function(key,value){
				url=$.atk4.addArgument(url,key+'='+value);
			});
		}

		this.jquery.atk4_load(url,fn);
	},
	saveSelected: function(name,url){
		result=new Array();
		i=0;
		$('#'+name+' input[type=checkbox]').each(function(){
			result[i]=$(this).attr('value')+':'+($(this).attr('checked')==true?'Y':'N');
			i++;
		});
		$.get(url+'&selected='+result.join(','),null,function(res){
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
					showMessage("Error in AJAX response: "+e+"\n"+response_text);
				}
			}
		});
	},
	executeUrl: function(url,callback){
		$.get(url,callback);
	},
	ajaxFunc:	function(str_code){
		$.globalEval(str_code);
	},
	reloadRow:	function(id){
		// Reload row of active grid
		var grid=this.jquery.closest('.atk4_grid');
		grid.atk4_grid('reloadRow',id);
	},
	removeRow:	function(id){
		// Reload row of active grid
		var grid=this.jquery.closest('.atk4_grid');
		grid.atk4_grid('removeRow',id);
	},
	removeOverlay: function(){
		var grid=this.jquery.closest('.atk4_grid');
		if(!grid.length){
			console.log('removeOverlay cannot find grid');
		}
		grid.atk4_grid('removeOverlay');
	},
dialogPrepare: function(options){
/*
 * This function creates a new dialog and makes sure other dialog-related functions will
 * work perfectly with it
 */
	var dialog=$('<div class="dialog dialog_autosize" title="Untitled">Loading<div></div></div>').appendTo('body');
	if(options.noAutoSizeHack)dialog.removeClass('dialog_autosize');
	dialog.dialog(options);
	if(options.customClass){
        dialog.parent().addClass(options.customClass);
    }
	$.data(dialog.get(0),'opener',this.jquery);
	$.data(dialog.get(0),'options',options);

	return dialog;
},
getDialogData: function(key){
	var dlg=this.jquery.closest('.dialog').get(0);
	if(!dlg)return null;
	var r=$.data(dlg,key);
	if(!r){
  		return null;
	}
	return r;
},
getFrameOpener: function(){
	var d=this.getDialogData('opener');
	if(!d)return null;
	return $(this.getDialogData('opener'));
},
dialogBox: function(options){
	return this.dialogPrepare($.extend({
		bgiframe: true,
		modal: true,
		width: 1000,
		position: 'top',
		autoOpen:false,
		beforeclose: function(){
			if($(this).is('.atk4_loader')){
				if(!$(this).atk4_loader('remove'))return false;
			}
		},
		buttons: {
			'Ok': function(){
				var f=$(this).find('form');
				if(f.length)f.eq(0).submit(); else $(this).dialog('close');
			},
			'Cancel': function(){
				$(this).dialog('close');
			}
		},
		close: function(){
			$(this).dialog('destroy');
			$(this).remove();
		}
	},options));
},
dialogURL: function(title,url,options,callback){
	var dlg=this.dialogBox($.extend(options,{title: title,autoOpen: true}));
	dlg.atk4_load(url,callback);
	return dlg.dialog('open');
},
frameURL: function(title,url,options,callback){
	options=$.extend({
		buttons:{}
	},options);
	return this.dialogURL(title,url,options,callback);
},
dialogOK: function(title,text,fn,options){
	var dlg=this.dialogBox($.extend({
		title: title,
		width: 450,
		//height: 150,
		close: fn,
		open: function() {
			$(this).parents('.ui-dialog-buttonpane button:eq(0)').focus();
		},
		buttons: {
			'Ok': function(){
				$(this).dialog('close');
			}
		}
	},options));
	dlg.html(text);
	dlg.dialog('open');

},
dialogConfirm: function(title,text,fn,options){
	/*
	 * Displays confirmation dialogue.
	 */
	var dlg=this.dialogBox($.extend({title: title, width: 450, height: 200,
	buttons: {
		'Ok': function(){
			$(this).dialog('close');
			if(fn)fn();
		},
		'Cancel': function(){
			$(this).dialog('close');
		}
	}},options));

	dlg.html("<form></form>"+text);
	dlg.find('form').submit(function(ev){ ev.preventDefault(); console.log('ok clicked'); fn; dlg.dialog('close'); });
	dlg.dialog('open');
},
dialogError: function(text,options,fn){
	this.dialogConfirm('Error','<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'+text,null,
					   $.extend({buttons:{'Ok':function(){ $(this).dialog('close');if(fn)fn()}}},options));
},
dialogAttention: function(text,options,fn){
	this.dialogConfirm('Attention!','<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'+text,null,
					   $.extend({buttons:{'Ok':function(){ $(this).dialog('close');if(fn)fn()}}},options));
},
message: function(msg,icon){
	if($.ui.atk4_notify && $('#atk-growl-holder').length){
		$('#atk-growl-holder').atk4_notify().atk4_notify('message',msg,icon);
		return;
	}
},
successMessage: function(msg){
	var html="";

	if($.ui.atk4_notify && $('.atk-growl-holder').length){
		$('.atk-growl-holder').atk4_notify().atk4_notify('successMessage',msg);
		return;
	}

	if(!$('.atk-growl-holder').length)return alert(msg);
	html = '<p class="growl">'+msg
		+'&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" class="growl_close"></a></p>';

	var growl=$(html).prependTo('#atk-growl-holder');
	growl.find('.growl_close').click(function(){
			growl.fadeOut(500,function(){ growl.remove(); });
	});

	growl.slideDown(200)
	   	.animate({opacity: 1.0},4000) // few seconds to show message
   		.fadeOut(500,function() {
				growl.remove();
				});

	growl.mouseover(function(){
			$(this).stop(true);
			$(this).find(".growl_close").css("display","block");
			});
	growl.mouseout(function(){
			$(this).animate({opacity: 1.0},4000) // few seconds to show message
			.fadeOut(500,function() {
				growl.remove();
				});
			$(this).find(".growl_close").css("display","none");
			});

	//this.dialogAttention(text);
},
fillFormFromFrame: function(options){
	/*
	 * Use this function from inside frame to insert values into the form which originally opened it
	 */
	var j=this.jquery;
	var form=this.getFrameOpener();
	form=form.closest('form');
	var form_id=form.attr('id');

	$.each(options, function(key,value){
		form.atk4_form('setFieldValue',key,value);
	});
	this.jquery=j;

},
closeDialog: function(){
	var r=this.getFrameOpener();
	if(!r)return;
	this.jquery.closest('.dialog').dialog('close');
	this.jquery=r;
},
getjQuery: function(){
	return this.jquery;
},
ajaxec: function(url,data){
	// Combination of ajax and exec. Will pull provided url and execute returned javascript.
	region=this.jquery;
	$.atk4.get(url,data,function(ret){
		/*
		 // error handling goes away from here
		if(ret.substr(0,5)=='ERROR'){
			$.univ().dialogOK('Error','There was error with your request. System maintainers have been notified.');
			return;
		}
		*/
		if(!$.atk4._checkSession(ret))return;
		try{
			eval(ret)
		}catch(e){
			w=window.open(null,null,'height=400,width=700,location=no,menubar=no,scrollbars=yes,status=no,titlebar=no,toolbar=no');
			if(w){
				w.document.write('<h2>Error in AJAXec response: '+e+'</h2>');
				w.document.write(ret);
				w.document.write('<center><input type=button onclick="window.close()" value="Close"></center>');
			}else{
				showMessage("Error in AJAXec response: "+e+"\n"+response_text);
			}
		}

	});
},
newWindow: function(url,name,options){
	window.open(url,name,options);
},
loadingInProgress: function(){
	this.successMessage('Loading is in progress. Please wait');
},
ajaxifyLinks: function(){
	// Links of the current container will be opened in the closest loader
	this.jquery.find('td a').click(function(ev){
		ev.preventDefault();
		$(this).closest('.atk4_loader').atk4_loader('loadURL',$(this).attr('href'));
	});
},
autoChange: function(interval){
	// Normally onchange gets triggered only when field is submitted. However this function
	// will make field call on_change one second since last key is pressed. This makes event
	// triggering more user-friendly
	var f=this.jquery;
	var f0=f.get(0);
	if(typeof interval == 'undefined')interval=1000;

	f.attr('data-val',f.val());

	function onkeyup(){
		if(f.attr('data-val')==f.val())return;
		f.attr('data-val',f.val());
		var timer=$.data(f0,'timer');
		if(timer){
			clearTimeout(timer);
		}
		if(interval){
			timer=setTimeout(function(){
					$.data(f0,'timer',null);
					f.trigger('autochange');
					f.change();
					},interval);
			$.data(f0,'timer',timer);
		}else{
			f.trigger('autochange');
			f.change();
		}
	}
	//f.change(onchange);
	f.keyup(onkeyup);
	f.bind('autochange_manual',onkeyup);
},
numericField: function(){
	this.jquery.bind('keyup change',function () {
	var t= this.value.replace(/[^0-9\.-]/g,'');
		if(t != this.value)this.value=t;
	});
},
disableEnter: function(){
	this.jquery.bind('keydown keypress',function (e) {
		if(e.which==13){
			return false;
		}
	});
},
bindConditionalShow: function(conditions,tag){
	// Warning
	//   this function does not handle recursive cases,
	//   when element A hides element B which should also hide
	//   element C. You may end up with B hidden and C still showing.
	var f=this.jquery;
	var n=f.closest('form');
	if(!n.attr('id'))n=n.parent();
	n=n.attr('id');

	if(typeof tag == 'undefined')tag='div';

	var sel=function(name){
		var s=[]
		fid=n;
		$.each(name,function(){
			var dom=$(a='#'+fid+'_'+this)[0];
			if(dom){
				s.push(dom);
			}else{
				console.log("Field is not defined",a);
			}
		});
		s=$(s);
		if(tag){
			s=s.closest(tag);
		}
		return s;
	}

	var ch=function(){
		if(f.is('.atk-checkboxlist,.atk-radio')){
			var v=[];
			f.find('input:checked').each(function(){
				v.push(this.value);
			});
		}else{
			var v=f.val();
		}
		if(f.is(':checkbox'))v=f[0].checked?v:'';
		if(f.is('select')){
			v=f.find('option:selected').val();
		}

		// first, lets hide everything we can
		$.each(conditions,function(k,x){
			s=sel(this);
			if(s.length){
				s.hide();
			}
		});

		// Next, let's see if there is an exact match for that
		var exact_match=null;
		if(v instanceof Array){
			exact_match=[];
			$.each(v,function(k,val){
				if(typeof conditions[val] != 'undefined'){
					exact_match.push(sel(conditions[val]));
				}
			});
			if(!exact_match.length && typeof conditions['*'] != 'undefined'){
				exact_match=sel(conditions['*']);
			}
		}else{
			if(typeof conditions[v] != 'undefined'){
				exact_match=sel(conditions[v]);
			}else if(typeof conditions['*'] != 'undefined'){
				// catch-all value exists
				exact_match=sel(conditions['*']);
			}
		}

		if(exact_match && exact_match.length){
			if(exact_match instanceof Array){
				$.each(exact_match,function(k,val){
					val.show();
				});
			}else{
				exact_match.show();
			}
		}
	}
	if(f.hasClass('field_reference')){
		f.bind('change_ref',ch);
	}else if(f.hasClass('atk-checkboxlist')){
		f.find('input[type=checkbox]').bind('change',ch);
	}else{
		if (f.is(':checkbox') && (jQuery.browser.msie && parseInt(jQuery.browser.version) <= 7)) {
			f.click(ch)
		}else f.change(ch);
	}
	ch();
	//console.log(conditions);
},

bindFillInFields: function(fields){
	/*
	 * This is universal function for autocomplete / dropdown fields. Whenever original field changes,
	 *  we will use information in "rel" attribute of orignial field to fill other fields
	 *  with appropriate values
	 */
	var f=this.jquery;


	$.each(fields,function(key,val){
		$(val).change(function(){ $(this).addClass('manually_changed'); });
	});


	function onchange_fn(){
		var data=eval('('+f.attr('rel')+')');
		var myid=$(this).val();
		data=data[myid];

		function auto_fill(){
			$.each(fields,function(key,val){
				if(data && data[key]){
					$(val).val(data[key]).change().removeClass('manually_changed');
				}
			});
		};

		// Make sure none of those fields were changed manually.
		var need_to_warn = false;
		$.each(fields,function(key,val){
			if(data && data[key] && $(val).hasClass('manually_changed') && $(val).val()){
				need_to_warn=true;
			}
		});

		if(need_to_warn)
			$(this).univ().dialogConfirm('Warning','Some fields you have edited are about to be auto filled. Would you like to proceed?',auto_fill);
		else
			auto_fill();
	};

	f.bind('change_ref change',onchange_fn);
},
    




nullFunction:function(){}
},$.univ._import
);

$.extend($.univ,{

// Function with custom return value

	toJSON: function (value, whitelist) {
		var m = {
			'\b': '\\b',
			'\t': '\\t',
			'\n': '\\n',
			'\f': '\\f',
			'\r': '\\r',
			'"' : '\\"',
			'\\': '\\\\'
		};

        var a,          // The array holding the partial texts.
            i,          // The loop counter.
            k,          // The member key.
            l,          // Length.
            r = /["\\\x00-\x1f\x7f-\x9f]/g,
            v;          // The member value.
        switch (typeof value) {
        case 'string':
            return r.test(value) ?
                '"' + value.replace(r, function (a) {
                    var c = m[a];
                    if (c) {
                        return c;
                    }
                    c = a.charCodeAt();
                    return '\\u00' + Math.floor(c / 16).toString(16) + (c % 16).toString(16);
                }) + '"' :
                '"' + value + '"';
        case 'number':
            return isFinite(value) ? String(value) : 'null';
        case 'boolean':
        case 'null':
            return String(value);
        case 'object':
            if (!value) {
                return 'null';
            }
            if (typeof value.toJSON === 'function') {
                return this.toJSON(value.toJSON());
            }
            a = [];
            if (typeof value.length === 'number' &&
                    !(value.propertyIsEnumerable('length'))) {
                l = value.length;
                for (i = 0; i < l; i += 1) {
                    a.push(this.toJSON(value[i], whitelist) || 'null');
                }
                return '[' + a.join(',') + ']';
            }
            if (whitelist) {
                l = whitelist.length;
                for (i = 0; i < l; i += 1) {
                    k = whitelist[i];
                    if (typeof k === 'string') {
                        v = this.toJSON(value[k], whitelist);
                        if (v) {
                            a.push(this.toJSON(k) + ':' + v);
                        }
                    }
                }
            } else {
                for (k in value) {
                    if (typeof k === 'string') {
                        v = this.toJSON(value[k], whitelist);
                        if (v) {
                            a.push(this.toJSON(k) + ':' + v);
                        }
                    }
                }
            }
            return '{' + a.join(',') + '}';
        }
    }
});



////// Define deprecated functions ////////////
$.each([
	'openExpander'
],function(name,val){
	$.univ[val]=function(){
		console.error('Function is deprecated:',val);
		return $.univ;
	}
});

$.fn.extend({
	univ: function(){
		var u=new $.univ;
		u.jquery=this;
		return u;
	}
});
})($);
