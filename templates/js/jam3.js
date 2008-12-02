/*
 * AModules addons and plugins to jQuery
 */
var ajaxIsIE = false;
var buttonClicked = null;
var am_loading='amodules3/img/loading.gif';
var am_notloading='amodules3/img/not_loading.gif';
var last_session_check = 0;


/**
 * Shows the message provided. Replaces standard alert() toshow more handsome output
 */
function showMessage($message){
	alert($message);
}
/**
 * Checks if session is expired and forces redirect to login if so
 * Check is performed by a key string in the results of the aarq request to
 * the specified URL
 * In order for function not to find a key in self parameters, key is passed with
 * spaces replaced by = (equals sign). Make sure your key has spaces :)
 * ### Zak ### 
 * Changed this to make ajax request to check sesson only if last check was done before mroe than 4 seconds,
 * This way server load will be reduced significantly if a lot of JSes needs to be executed.
 */
function checkSession(url){ 
	var curtime = new Date;
	if (last_session_check + 4000 < curtime.getTime()) {
		last_session_check = curtime.getTime();
		$.get(url, {check_session: 1}, function(xml){
			if($("expired",xml).text()=="1"){
				showMessage("Your session has expired. Please login again");
				window.location="";
			}
		});
	} else {
		return true;
	}
}
function spinner_on(spinner, timeout, text){
	/* This function starts a spinning wheel */
	spinner_id=spinner;
	spinner=$('#'+spinner_id);
	
	if(!spinner)return;
	// spinner is an image
	if(spinner.attr('src'))spinner.attr('src',$('#gif_loading img').attr('src'));
	// spinner is a place where image should be inserted
	else spinner.html('<img src="'+$('#gif_loading img').attr('src')+'" alt="loading">');
	// adding text if set
	if(text)$(text).insertAfter('#'+spinner_id+' img');
	// switching off on timeout
	if(timeout == null)timeout=3000;
	if(timeout>0)setTimeout("spinner_off('"+spinner_id+"')",timeout);
	return spinner;
}
function spinner_off(spinner){
	/* This function stops a spinning wheel */
	spinner=$('#'+spinner);
	if(!spinner)return;
	// spinner is an image
	if(spinner.attr('src'))spinner.attr('src',$('#gif_not_loading img').attr('src'));
	// spinner is a place where image should be inserted
	else spinner.html('<img src="'+$('#gif_not_loading img').attr('src')+'" alt="loading">');
}
function submitForm(form,spinner){
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
	$('#'+form).ajaxSubmit({success: successHandler});
}
function expander_flip(name,id,button,expander_url){
	// adding new row
	new_row_id=name+'_expandedcontent_'+id;
	expanding_row_id=name+'_'+id;
	if($('#'+new_row_id).attr('id')){
		// hiding expander
		$('#'+new_row_id+' div').slideUp('slow',function(){
			// removing row
			$('#'+expanding_row_id).next('.expander_tr').remove();
		});
	}else{
		new_row=$('<tr class="expander_tr"></tr>').insertAfter('#'+expanding_row_id);
		new_row.html('<td colspan="'+$('#'+expanding_row_id+' td').length+'" id="'+new_row_id+'" name="'+new_row_id+'" class="expander_td"><div id="image_div"></div></td>');
		new_cell=$('#'+new_row_id);
		// spinner
		s=spinner_on(new_row_id+' #image_div',0,'<b>Loading. Stand by...</b>');
		new_cell.html(new_cell.html()+'<div style="display: none"></div>');
		// loading content into hidden div
		$.get(expander_url,function(result){
			$('#'+new_row_id+' div').html(result);
			// now handsomely showing the content and hiding spinner
			$('#'+new_row_id+' #image_div').remove();
			//$('#'+new_row_id+' b').remove();
			$('#'+new_row_id+' div').slideDown('slow');
		});
	}
}
function reloadGridRow(url,name,id,callback){
	/*$.get(url,null,function(json){
		// result contains JSON array
		// see Grid::formatRowContent_jquery()
		var row=json;
		tr='#'+name+'_'+id+' td';
		$(tr).each(function(i){
			this.html(json[i].data.actual);
			if(json[i].style!=null){
				if(json[i].style.class)this.attr('class',json[i].style.class);
				//this.attr('style'
			}
		});
	},'json');*/
}
function setFormFocus(form,field){
	var frm = document.getElementById(form);
	frm[form+"_"+field].focus();
}
