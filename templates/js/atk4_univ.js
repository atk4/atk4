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


$.univ._import=function(name,fn){
	$.univ[name]=function(){
		var ret;

		if(!$.univ.ignore){
			ret=fn.apply($.univ,arguments);
		}
		return ret?ret:$.univ;
	}
}

$.each({
	alert: function(a){
		alert(a);
	},
	displayAlert: function(a){
		alert(a);
	},
	redirect: function(url,fn){
		if($.atk4 && $.atk4.page){
			$.atk4.page(url,fn);
		}else{
			document.location=url;
		}
	},
	redirectURL: function(page,fn){
		if($.atk4.page){
			$.atk4.page(page,fn);
		}else{
			document.location=page;
		}
	},
	page: function(page,fn){
		$.atk4.page(page,fn);
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
	autoChange: function(){
	// Normally onchange gets triggered only when field is submitted. However this function
	// will make field call on_change one second since last key is pressed. This makes event
	// triggering more user-friendly
		var f=this.jquery;
		var f0=f.get(0);

		/*
		function onchange(){
			var timer=$.data(f0,'timer');
			if(timer)clearTimeout(timer);
			$.data(f0,'timer',null);
		}
		*/
		function onkeyup(){
			var timer=$.data(f0,'timer');
			if(timer){
				clearTimeout(timer);
			}
			timer=setTimeout(function(){
					$.data(f0,'timer',null);
					f.change();
					},500);
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
