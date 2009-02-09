/*
 * AModules addons and plugins to jQuery
 */
var ajaxIsIE = false;
var buttonClicked = null;
var last_session_check = 0;

############################################
############  Generic functions ############
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
			if($("expired",xml).textContent=="1"){
				showMessage("Your session has expired. Please login again");
				window.location="";
			}
		},'xml');
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
############################################
############## Form functions ##############
function submitForm(form,button,spinner){
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
	$('#'+form).ajaxSubmit({success: successHandler});
	// removing hidden field
	button.remove();
}
function setFormFocus(form,field){
	$('#'+form+' input[name='+form+'_'+field).focus();
}
/**
 * Sets the value of the field to a value returned by the specified URL
 */
function loadFieldValue(field,url){
	
}
############################################
############## Grid functions ##############
/*
 * This would just return exander's state on a button click. If another expander is
 * open, it would not do anything, just return it's button's name.
 */
function expander_is_open(name,id,button){
	//inlines should be hidden anyway
	//if(inline_is_active(name,id))return "inline_active";

	button_id=name+"_"+button+"_"+id;
	for(var index in $('#'+button_id).parent().children()){
		col=$('#'+button_id).parent().children().get(index);
		if($(col).hasClass('expanded_this')){
			// if column name equals to button id - our column has expander open
			if($(col).attr('id')==button_id)return 'expanded_this';
			else return $(col).data('button');
		}
	}
	return 'none';
}
/**
 * Open/close expander
 * name - lister name
 * id - record ID (from DB)
 * button - field name to expand
 * expander_url - URL to be loaded into expander
 */
function expander_flip(name,id,button,expander_url,callback){
	// adding new row
	new_row_id=name+'_expandedcontent_'+id;
	expanding_row_id=name+'_'+id;
	button_id=name+"_"+button+"_"+id;
	expander_status = expander_is_open(name,id,button);
	if(expander_status=='expanded_this'){ // expander is OPEN
		// hiding expander
		$('#'+new_row_id+' div').slideUp('slow',function(){
			// removing row
			//$('#'+expanding_row_id).next('.expander_tr').remove();
			$('#'+new_row_id).remove();
			// if callback set - calling it
			if(callback)callback();
		});
		// removing tab-like style of a cell
		button_off(button_id);
	}
	else if(expander_status=='none'){ // expander is CLOSED
		// closing all open inlines
		close_inlines(name);
		new_row=$('<tr class="expander_tr"></tr>').insertAfter('#'+expanding_row_id);
		new_row.html('<td colspan="'+$('#'+expanding_row_id+' td').length+'" id="'+new_row_id+'" name="'+new_row_id+'" class="expander_td"><div id="image_div"></div></td>');
		new_cell=$('#'+new_row_id);
		// spinner
		s=spinner_on(new_row_id+' #image_div',0,'<b>Loading. Stand by...</b>');
		new_cell.html(new_cell.html()+'<div style="display: none"></div>');
		// activating button
		button_on(button_id);
		// storing field name, will be required to determine open expander
		$('#'+button_id).data('button',button);
		// loading content into hidden div
		$.get(expander_url,function(result){
			$('#'+new_row_id+' div').html(result);
			// now handsomely showing the content and hiding spinner
			$('#'+new_row_id+' #image_div').remove();
			//$('#'+new_row_id+' b').remove();
			$('#'+new_row_id+' div').slideDown('slow');
		});
	}
	else{
		// other expander is open, its button name is in expander status. closing and open ours
		expander_flip(name,id,expander_status,null,function(){
			expander_flip(name,id,button,expander_url);
		});
	}
}
function reloadGridRow(url,name,id,callback){
	// globalizing tr
	$(document).data('tr_name','#'+name+'_'+id+' td');
	$.getJSON(url,null,function(json){
		// result contains JSON array
		// see Grid::formatRowContent_jquery()
		$.each(json,function(i,item){
			// getting collection of TDs
			td=$($(document).data('tr_name'));
			// indices  differ: for td is zero-based
			$(td[i-1]).html(item.data.actual);
			// styles
			if(item.params!=null){
				for(var name in item.params){
					if(name=='style')$(td[i-1]).css(item.params.style);
					else if(name=='onclick'){
						// TODO: this code works one time. After second execution expander stops to work. Do nothing for a while
						//if(item.params[name]==''||item.params[name]==null)$(td[i-1]).attr(name,'').unbind('click');
						//else $(td[i-1]).attr(name,item.params[name]).click(eval('function(event){'+item.params[name]+'}'));
					}else{
						// removing empty parameters
						if(item.params[name]==''||item.params[name]==null)$(td[i-1]).removeAttr(name);
						else $(td[i-1]).attr(name,item.params[name]);
					}
				}
			}
		});
	});
}
/*
 * Highlight expander button as it was pressed
 */
function button_on(id){
	button=$('#'+id);
	// if other button was active - deactivating it
	button.parent().children().each(function(i){
		if($(this).hasClass('expanded_this')){
			$(this).removeClass('expanded_this');
			// restoring class from data
			$(this).addClass($(this).data('class'));
		}
	});
	// storing old class in data for further restoring
	button.data('class',$(this).attr('class'));
	// setting tab-like view
	button.addClass('expanded_this');
}
/*
 * highlight expander button as it was released
 */
function button_off(id){
	button=$('#'+id);
	button.removeClass('expanded_this');
	// restoring class from data
	button.addClass($(this).data('class'));
}
/**
 * Event handler for inlines
 * e - event
 * inline_id - ID of the inline edit field
 * activate_next - if set, must contain row ID (tr id="this")
 */
function inline_process_key(e, inline_id, activate_next){
	if(isKeyPressed(e, kEsc)){
		//submitting and hiding current inline
		inline_hide(inline_id, 'cancel');
		return false;
	}
	if(isKeyPressed(e, kReturn)){
		//submitting and hiding current inline
		inline_hide(inline_id, 'update');
		return false;
	}

	if(activate_next&&isKeyPressed(e, kTab)){
		row=$('#'+activate_next).get(0);
		if(e.shiftKey||e.modifiers==4){
			//shift is pressed, locating previous row
			while(row.previousSibling){
				row=row.previousSibling;
				if(row.id&&row.id!=activate_next)break;
			}
		}else{
			//no shift, locating next row
			while(row.nextSibling){
				row=row.nextSibling;
				if(row.id)break;
			}
		}
		if(row.id){ 
			data=$('#'+inline_id).data('inline_data');
			//submitting and hiding current inline
			inline_hide(inline_id, 'update');
			//activating next inline, changing id to next row id
			new_id=new String(row.id);
			new_id=new_id.substring(new_id.lastIndexOf('_')+1);
			inline_show(data['name'],data['active_field'],new_id,data['submit_url'],true,data['show_submit']);
			return false;
		}else{
			// new row was not found, but still we need to close current
			inline_hide(inline_id,'update');
		}
	}
	return true;
}
function getInlineValue(inline_id){
	// original value without formatting is stored in title attr
	return $('#'+inline_id).attr('title');
}
/**
 * changes row content to a forms with input elements
 * submit_url is an URL that should store changes from inline to DB
 * name is a name of a grid
 * active_field - inline control that should be set active
 * id - record ID (from DB)
 * on_submit_fun callback function that executes after Ok button handler executed 
 * on_cancel_fun callback function that executes after Cancel button handler executed
 */
function inline_show(name,active_field,id,submit_url,activate_next,show_submit,on_submit_fun,on_cancel_fun){
	inline_id=active_field+"_"+id;
	// closing all open expanders
	close_expanders(name,id);
	// closing all open inlines
	close_inlines(name);
	// inline edit is activated for ONE field only - the one clicked
	field=$('#'+inline_id);
	if(!field)showMessage('Error getting inline edit field: '+inline_id);
	var form_id;
	if((new String(field.attr('id'))).indexOf('_inline')!=-1){
		// creating form
		form_id='form_'+inline_id;
		// setting input size to length of the text in it or 40 max
		value=getInlineValue(inline_id);
		size=value.length+5;
		if(size<6) size=6;
		if(size>40) size=40;
		
		var key;
		if (detect_ie() || detect_opera()){
			key='onKeyDown';
		}
		else{
			key='onKeyPress';
		}
		value=htmlspecialchars(value);
		field.html(
			'<form id="'+form_id+'" name="'+form_id+'" method="POST" onsubmit="return false;">'+
				'<input id="'+form_id+'_edit" name="'+form_id+'_edit" value="'+value+'" size="'+size+'" maxlength="255" type="text" '+key+'="'+
				'return inline_process_key(event,\''+inline_id+'\','+(activate_next?'\''+name+'_'+id+'\'':false)+');">'+
			'</form>');
	}
	// submit row
	if(show_submit){
		new_row=$('<tr class="inline_submit_tr"></tr>').insertAfter(field.parent());
		// setting on submit events
		if(typeof(on_submit_fun) == 'undefined') on_submit_fun = '';
		else on_submit_fun = ',\'' + on_submit_fun + '(\\\''+name+'\\\',\\\''+id+'\\\')\'';
		
		if(typeof(on_cancel_fun) == 'undefined') on_cancel_fun = '';
		else on_cancel_fun = ',\'' + on_cancel_fun + '(\\\''+name+'\\\',\\\''+id+'\\\')\'';
		// buttons
		event_handler="inline_hide('"+inline_id+"','";
		// column count for the row
		cnt=field.parent().children('td').length;
		// TODO: buttons will be placed right under the field
		new_row.html(
			'<td colspan="'+cnt+'"><form id="'+form_id+'" name="'+form_id+'" method="POST">'+
				'<input type="button" value="OK" onclick="'+event_handler+'update\''+on_submit_fun+');">'+
				'<input type="button" value="Cancel" onclick="'+event_handler+'cancel\''+on_cancel_fun+');">'+
			'</form></td>');
		new_row.show();
	}
	// setting input active
	try{
		document.getElementById('form_'+inline_id+'_edit').focus();
		document.getElementById('form_'+inline_id+'_edit').select();
	}catch(e){}
	// saving data that helps us to close inline
	data=new Array();
	data['submit_url']=submit_url;
	data['active_field']=active_field;
	data['name']=name;
	data['id']=id;
	data['show_submit']=show_submit;
	data['field_name']=active_field.substring(name.length+1, active_field.indexOf('_inline'));
	field.data('inline_data',data);
}
function inline_hide(inline_id, action, callback){
	field=$('#'+inline_id);
	data=field.data('inline_data');
	if(!data)return;//showMessage('Cannot find active inlines on a row: '+name+'_'+id);
	// default action is update
	if(!action)action='update';
	url=data['submit_url']+'&action='+action+'&id='+data['id'];
	if(action=='update'){
		// adding field value to url
		edit=$('#form_'+inline_id+'>input[type!=button]');
		value=edit.attr('value');
		// not to lose data
		if(edit.attr('id')==undefined){
			showMessage('Error occured during data processing. Debug data: '+edit.attr('id')+':'+edit.attr('type')+'='+value);
			return false;
		}
		url+='&field_'+data['field_name']+'='+encodeURIComponent(value);
	}
	// performing submit and row reload
	reloadGridRow(url,data['name'],data['id'],callback);
	// submit tr should be hidden if we showed it
	if(data['show_submit'])field.parent().next('.inline_submit_tr').remove();
	// removing data from field as it no longer needed
	field.data('inline_data',false);
}
/**
 * Closes all open inlines of the grid 
 * @param name - name (ID) of the grid
 */
function close_inlines(name){
	// closing all open inlines. walking through grid rows
	grid_path='#'+name+'>div>table.lister>tbody';
	$(grid_path).children('tr').each(function(){
		// looping through TDs
		$(this).children('td').each(function(){
			data=$(this).data('inline_data');
			if(data)inline_hide(data['active_field']+'_'+data['id']);
		});
		
	});
	/*for(var tr_index in $(grid_path).children('tr').get()){
		tr=$(grid_path).children('tr').get(tr_index);
		for(var td_index in $(tr).children('td').get()){
			td=$(tr).children('td').get(td_index);
			data=$(td).data('inline_data');
			if(data)inline_hide(data['active_field']+'_'+data['id']);
		}
	}*/
}
/**
 * Closes open expander of the grid 
 * @param name - name (ID) of the grid
 * @param id - record ID
 */
function close_expanders(name,id){
	$('#'+name+'_'+id).children().each(function(){
		if($(this).attr('class')=='expanded_this')expander_flip(name,id,$(this).data('button'));
	});
}
############################################
############ TreeView functions ############
function treenode_flip(expand,id,url){
	button=$('span#ec_'+id).html();
	cll=$('#p_'+id);
	if(expand==1){
		cll.append($('#gif_loading img')).append($('<b>Loading. Stand by...</b>'));
	}
	cll.load(url);
	
	if(expand==1){
		button=button.replace('plus.gif', 'minus.gif');
		button=button.replace('ec_action=expand', 'ec_action=collapse');
		button=button.replace('treenode_flip(1', 'treenode_flip(0');
	}else{
		button=button.replace('minus.gif', 'plus.gif');
		button=button.replace('ec_action=collapse', 'ec_action=expand');
		button=button.replace('treenode_flip(0', 'treenode_flip(1');
	}
	$('span#ec_'+id).html(button);
}
// redraws a node specified and its branch
function treenode_refresh(id,url){
	$('#p_'+id).load(url);
}
############################################

