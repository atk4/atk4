/* Welcome to Agile Toolkit JS framework. This file provides universal chain. */

// jQuery allows you to manipulate element by doing chaining. Similarly univ provides
// loads of simple functions to perform action chaining.
//

;
$||console.error("jQuery must be loaded");
$.univ||(function($){

$.univ = function(){
	var ignore=true;
	$.univ.ignore=false;
	return $.univ;
};

$.each({
	alert: function(a){
		alert(a);
	},
	displayAlert: function(a){
		alert(a);
	},
	redirectURL: function(page){
		if($.atk4.page){
			$.atk4.page(page);
		}else{
			document.location=page;
		}
	},
	log: function(arg){
		console.log('log: ',arg);
	},
	confirm: function(msg){
		if(!msg)msg='Are you sure?';
		if(!confirm(msg))this.ignore=true;
	},
	displayFormError: function(form,field,message){
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
	reload: function(id,url){
		$.get(url,function(text){
			$(id).html(text);
		});
	},


},function(name,fn){
	$.univ[name]=function(){
		if(!$.univ.ignore){
			fn.apply($.univ,arguments);
		}
		return $.univ;
	}
});


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
			return $.univ;
		}

});
})($);
