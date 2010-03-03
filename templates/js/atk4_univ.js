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
		document.location=url;
	},
	page: function(page,fn){
		$('#Content').atk4_load(page,fn);
	},
	log: function(arg1){
		if(log){
			log.info(arg1);
			var s=$('#header_widgets').children('a:last').children('span');
			var i=parseInt(s.text());
			if(i>0)i++;else i='1';
			s.text(i);
			var s=$('#header_widgets').children('a:last').children('img').stop().effect('bounce');
		}else{
			console.log(arg1);
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
        $('.expander').atk4_expander('collapse');
	},
	closeExpanderWidget: function(){
		this.closeExpander();
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
	var dialog=$('<div class="dialog" title="Untitled">Loading<div></div></div>').appendTo('body');
	dialog.dialog(options);
	$.data(dialog.get(0),'opener',this.jquery);
	$.data(dialog.get(0),'options',options);

	return dialog;
},
getDialogData: function(key){

	var dlg=this.jquery.closest('.dialog').get(0);

	if(!dlg)this.log('must be called from inside dialog',this.jquery);

	var r=$.data(dlg,key);
	if(!r){
  		this.log('key',key,' does not have data for ',this.jquery.closest('.dialog').get(0));
	}

	return r;
},
getFrameOpener: function(){
	return $(this.getDialogData('opener'));
},
dialogBox: function(options){
	return this.dialogPrepare($.extend({
		bgiframe: true,
		modal: true,
		width: 800,
//		height: 700,
		position: 'top',
		autoOpen:false,
		beforeclose: function(){
			if($(this).is('.atk4_loader')){
				if(!$(this).atk4_loader('remove'))return false;
			}
		},
		buttons: {
			'Cancel': function(){
				$(this).dialog('close');
			},
			'Ok': function(){
				var f=$(this).find('form');
				if(f.length)f.eq(0).submit(); else $(this).dialog('close');
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
	dlg.dialog('open');
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
		'Cancel': function(){
			$(this).dialog('close');
		},
		'Ok': function(){
			$(this).dialog('close');
			if(fn)fn();
		}
	}},options));

	dlg.html("<form></form>"+text);
	dlg.find('form').submit(function(ev){ ev.preventDefault(); console.log('ok clicked'); fn; dlg.dialog('close'); });
	dlg.dialog('open');
},
dialogError: function(text,options){
	this.dialogConfirm('Error','<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'+text,null,options);
},
dialogAttention: function(text,fn){
	this.dialogConfirm('Attention!','<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'+text,fn);
},
successMessage: function(msg){
	var html="";
	html = '<p class="growl">'+msg
		+'&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" class="growl_close"></a></p>';

	var growl=$(html).prependTo('#float-messages');
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
	this.jquery.closest('.dialog').dialog('close');
	this.jquery=r;
},
getjQuery: function(){
	return this.jquery;
},
ajaxec: function(url){
	// Combination of ajax and exec. Will pull provided url and execute returned javascript.
	$.get(url,function(ret){
		if(ret.substr(0,5)=='ERROR'){
			$.univ().dialogOK('Error','There was error with your request. System maintainers have been notified.');
			return;
		}	
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
loadingInProgress: function(){
	if(this.successMessage){
		this.successMessage('Loading is in progress. Please wait');
	}else{
		alert('Loading is in progress. Please wait');
	}
},
	autoChange: function(interval){
	// Normally onchange gets triggered only when field is submitted. However this function
	// will make field call on_change one second since last key is pressed. This makes event
	// triggering more user-friendly
		var f=this.jquery;
		var f0=f.get(0);

		f.attr('data-val',f.val());

		function onkeyup(){
			if(f.attr('data-val')==f.val())return;
			var timer=$.data(f0,'timer');
			if(timer){
				clearTimeout(timer);
			}
			timer=setTimeout(function(){
					$.data(f0,'timer',null);
					f.trigger('autochange');
					f.change();
					},interval?interval:500);
			$.data(f0,'timer',timer);
		}
		//f.change(onchange);
		f.keyup(onkeyup);
	}

},$.univ._import
);

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
