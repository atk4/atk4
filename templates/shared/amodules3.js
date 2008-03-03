//KeyCodes
var kReturn=13;
var kTab=9;
var kEsc=27;

// Support for expanders @ Lister

var expander_open=new Array();    
    /* we remember opened expanders so we know what to do when clicked */
var inline_active=new Array();
    /* same as for expanders, but applying to inline edits. 
       they should be processed differently 
       this array contains URLs to close inlines
    */
function inline_is_active(name,id){
	if(inline_active[name])return inline_active[name][id];
	else return false;
}

function expander_is_open(name,id,button){
    /*
     * This would just return exander's state on a button click. If another expander is
     * open, it would not do anything, just return it's button's name.
     */
    //inlines should be hidden anyway
    if(inline_is_active(name,id))return "inline_active";

    id=name+"_"+id;

    if(expander_open[id]==button){
        expander_open[id]='';
        return "_closing";
    }else if(expander_open[id]){
        return expander_open[id];
    }else{
        expander_open[id]=button;
        return "_opening";
    }
}

function button_on(id){
    /*
     * Highlight expander button as it was pressed
     */
    button=document.getElementById(id);

    for(c=button.parentNode.firstChild;c;c=c.nextSibling){
        if(c==button)continue;
        if(!c.style)continue;
        c.className='expanded_other';
    }

    button.className='expanded_this';
}

function button_off(id){
    /*
     * highlight expander button as it was released
     */
    button=document.getElementById(id);

    for(c=button.parentNode.firstChild;c;c=c.nextSibling){
        if(c==button)continue;
        if(!c.style)continue;
        c.className='not_expanded';
    }

    button.className='not_expanded';
}

function expand(id,step){
    /*
     * This adds a bit of animation. It would "grow" div over time. Use this on a temporary div,
     * so that when it's gone from the page, expanding effect will cease itself
     */
    if(step>200)return;
    expander=document.getElementById(id);
    if(!expander)return;
    expander.style.paddingBottom=step+"px";
    setTimeout("expand('"+id+"',"+(30+step)+")",50);
}

function expander_flip(name,id,button,expander_url){
    /*
     * This opens / closes lister's expander. Use this function
     * under 'onclick' for your table row. It will take care of
     * adding additional row and loading content there. expander_url is
     * a prefix, which will have id appended
     */

    row=document.getElementById(name+"_"+id);
    expander_status = expander_is_open(name,id,button);
    if(expander_status=="_closing"){
        nextrow=row.nextSibling;
        row.parentNode.removeChild(nextrow);

        button_off(name+"_"+button+"_"+id);
    }else if(expander_status=="inline_active"){
	inline_hide(name,id);
        expander_flip(name,id,button,expander_url);
    }else if(expander_status=="_opening"){

        
		tmp=document.getElementById(row.id);
		tmp=tmp.getElementsByTagName('TD');
		cs=0;
		while (tmp[cs] != null) cs++;
        newrow=document.createElement("tr");
        nextrow=row.nextSibling;
        if(!nextrow){
            row.parentNode.appendChild(newrow);
        }else{
            row.parentNode.insertBefore(newrow,nextrow);
        }
        /* -- rem by chk
        my_td=document.createElement("td");
        my_td.colspan=cs;
        -- /rem by chk*/
        
        /* TODO: here is some template-dependant stuff. It's better to move this thing
         * out. Not sure if the temporary thing can me removed easily, but the background
         * color should be customizable. And it's even better to use class names, BTW!
         */
//        bg="#FFFFFF";
        
        /* chk */
        //tmp = row./*parentNode.firstChild.*/childNodes;
        //alert(tmp.length);

		cll = newrow.insertCell(0);
        cll.style.backgroundColor = "#FFFFFF";
        cll.style.borderWidth = '0 1px 1px 1px';
        cll.style.borderColor = '#000';
        cll.style.borderStyle = 'solid';
        cll.style.padding = '15px';
        
        cll.colSpan = cs; //row.parentNode.firstChild.childNodes.length;
        
        //cll.id = name+"_expandedcontent_"+id;        
        cll.setAttribute('id', name+"_expandedcontent_"+id);
        
        cll.innerHTML = '<table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img alt="" src="'+am_loading+'"></td><td>&nbsp;</td><td class="smalltext" align=center id="autoexpander_'+id+'" valign=top><b>Loading. Stand by...</b></td></tr></table>';
        
        /* /chk */
       
        //newrow.innerHTML='<td style="background: '+bg+'; border: 1px solid black; border-top: 0px; padding: 15px" colspan="'+cs+'" id="'+name+"_expandedcontent_"+id+'" ><table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img src="'+am_loading+'"></td><td>&nbsp;</td><td class="smalltext" align=center id="autoexpander_'+id+'" valign=top><b>Loading. Stand by...</b></td></tr></table></td>';
        // http://dev.adevel.com/chk/fssub.html 
        // http://dev.adevel.com/chk/tmp/aaa&bbb=ccc
        
        aasn(name+"_expandedcontent_"+id, expander_url+id);
        
        //aasn(name+"_expandedcontent_"+id, 'http://php5.chk/freespech/admin/'+expander_url+id);
        expand('autoexpander_'+id,0);

        button_on(name+"_"+button+"_"+id);
    }else{
        /* other button is active. Close that one and open new one */
        expander_flip(name,id,expander_status,expander_url);
        expander_flip(name,id,button,expander_url);
    }
}
/******* InlineEdit functions ********/
function getInlineValue(inline_id){
	//var value=new String(document.getElementById(inline_id).innerHTML);
	/* stripping any tags from a value 
	   initial string is: <font color="blue">Value</font>
	*/
	//v=value.split(">");
	//now we have v=array(0=>'<font color="blue"', 1=>'Value</font'
	//value=new String(v[1]).split("<");
	//value='edt';
	//return value[0];
	return document.getElementById(inline_id).title;
}
function isKeyPressed(e, kCode){
	var characterCode;

	if(e && e.which)characterCode = e.which;
	else characterCode=e.keyCode;

	return characterCode == kCode;
}
function denyEnter(e){
	return !isKeyPressed(e, kReturn);
}



function detect_ie()
{
	var agent=navigator.userAgent.toLowerCase();
	var i=agent.indexOf('msie');
	if (i!=-1) return parseFloat(agent.slice(i+5));
	else return false;
}


function detect_opera()
{
	var agent=navigator.userAgent.toLowerCase();
	var i=agent.indexOf('opera');
	if (i!=-1) return parseFloat(agent.slice(i+6));
	else return false;
}


function inline_process_key(e, name, id, activate_next){
//	alert(e+" "+name+" "+id+" "+activate_next);

	if(isKeyPressed(e, kEsc)){
		//submitting and hiding current inline
		inline_hide(name, id, 'cancel');
		return false;
	}
	if(isKeyPressed(e, kReturn)){
		//submitting and hiding current inline
		inline_hide(name, id, 'update');
		return false;
	}

	if(activate_next&&isKeyPressed(e, kTab)){
		this_row_id=name+'_'+id;
		row=document.getElementById(this_row_id);
		if(e.shiftKey||e.modifiers==4){
			//shit is pressed, locating previous row
			while(row.previousSibling){
				row=row.previousSibling;
				if(row.id&&row.id!=this_row_id)break;
			}
		}else{
			//no shift, locating next row
			while(row.nextSibling){
				row=row.nextSibling;
				if(row.id)break;
			}
		}
		if(row.id){ 
			//we need to parse an ID from row.id
			row_id=new String(row.id);
			while(row_id.indexOf("_")!=-1)
				row_id=row_id.substring(row_id.indexOf('_')+1);
			//storing values cause they will be erased on hide
			active_field=inline_active[name][id]['active_field'];
			submit_url=inline_active[name][id]['submit_url'];
			//submitting and hiding current inline
			inline_hide(name, id, 'update');
			//activating next inline
			inline_show(name,active_field,row_id,submit_url,true,inline_active[name]['show_submit']);
			return false;
		}
	}
	return true;
}

function inline_show(name,active_field,row_id,submit_url,activate_next,show_submit,on_submit_fun,on_cancel_fun){
	// changes row content to a forms with input elements
	// submit_url is an URL that should store changes from inline to DB
	// name is a name of a grid
	// active_field - inline control that should be set active
	// row_id - guess
	// on_submit_fun callback function that executes after Ok button handler executed 
	// on_cancel_fun callback function that executes after Cancel button handler executed
	inline_id=active_field+"_"+row_id;
	//closing all open expanders
	id=name+"_"+row_id;
	if(expander_open[id]){
		expander_flip(name,row_id,expander_open[id]);
	}
	//closing all active inlines
	if(inline_active[name]){
		for(i=0;i<inline_active[name].length;i++){
			if(inline_active[name][i])
				inline_hide(name,i);
		}
	}
	row=document.getElementById(id);
	if(!row)alert("Row is empty: "+row+"\nID used is: "+id);
	//counting columns	

	tmp=document.getElementById(row.id);
	tmp=tmp.getElementsByTagName('TD');
	cs=0;
	while (tmp[cs] != null) cs++;
	
	//changing row contents to the forms. only for inlines...
	col=document.getElementById(inline_id);
	var inline_collection=new Array();
	var index=0;
	//MS IE js works different to Mozilla: nextSibling does needed action erlier
	//so for IE we should delay switching to next sibling
	goback=col.id!=undefined;
		
		id=new String(col.id);
		if(id.indexOf('_inlinex')!=-1) { // extended inline element - ajax content loading
			
			var extend_url=id;
			extend_url_arr = extend_url.split('_');
			extend_url ='';
			for(var i=1;i<extend_url_arr.length-1;i++) {
				extend_url += ((i==1)?'':'_')+extend_url_arr[i];
				
			}
			
			extend_url += '&id='+extend_url_arr[extend_url_arr.length-1];

			var form_name='form_'+id;
			aasn(id,extend_url);
			inline_collection[index]=form_name;
			index++;
		} else 	if(id.indexOf('_inline')!=-1){
			var form_name='form_'+id;
			
			// setting input size to length of the text in it or 40 max
			value=getInlineValue(id);
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
			col.innerHTML='<form id="'+form_name+'" name="'+form_name+'" method="POST" onsubmit="return false;">'+
				'<input id="'+form_name+'_edit" value="'+
				value+'" size="'+size+'" maxlength="255" type="text" '+key+'="'+
				'return inline_process_key(event,\''+name+'\','+row_id+','+activate_next+');"></form>';
			inline_collection[index]=form_name;
			index++;
		}
	if(show_submit){
		//expanding a row with submits
        	newrow=document.createElement("tr");
	        nextrow=row.nextSibling;
        	if(!nextrow){
	            row.parentNode.appendChild(newrow);
        	}else{
	            row.parentNode.insertBefore(newrow,nextrow);
        	}
//	        bg="#FFFFFF";  
        
        	cll = newrow.insertCell(0);
			cll.align='right';
        	cll.style.backgroundColor = "#FFFFFF";
	        cll.style.borderWidth = '0 1px 1px 1px';
        	cll.style.borderColor = '#000';
	        cll.style.borderStyle = 'solid';
        	cll.style.padding = '1px';
			cll.colSpan = cs; 
	        
		if(typeof(on_submit_fun) == 'undefined') on_submit_fun = '';
		else on_submit_fun = ',\'' + on_submit_fun + '(\\\''+name+'\\\',\\\''+row_id+'\\\')\'';
		
		if(typeof(on_cancel_fun) == 'undefined') on_cancel_fun = '';
		else on_cancel_fun = ',\'' + on_cancel_fun + '(\\\''+name+'\\\',\\\''+row_id+'\\\')\'';
		
		event_handler="inline_hide('"+name+"',"+row_id+",'";
		cll.innerHTML='<form id="'+form_name+'" name="'+form_name+'" method="POST">'+
			'<input type="button" value="OK" onclick="'+event_handler+'update\''+on_submit_fun+');">'+
			'<input type="button" value="Cancel" onclick="'+event_handler+'cancel\''+on_cancel_fun+');">'+
			'</form>';
	}
	
//selecting an edit
	try{
			document.getElementById('form_'+inline_id+'_edit').focus();
			document.getElementById('form_'+inline_id+'_edit').select();
	}catch(e){}
	
	
	//setting an array value for further hiding
	if(!inline_active[name])inline_active[name]=new Array();
	inline_active[name][row_id]=new Array();
	inline_active[name][row_id]['submit_url']=submit_url;
	inline_active[name][row_id]['inline_collection']=inline_collection;
	inline_active[name][row_id]['active_field']=active_field;
	inline_active[name]['show_submit']=show_submit;
}



function inline_hide(name, row_id, action, callback){
	//name is a grid name, id is a row id
	//processing inline: submit or cancel
	//callback - callback function with params
	submit_url=inline_active[name][row_id]['submit_url']+'&row_id='+row_id;
	inline_collection=inline_active[name][row_id]['inline_collection'];
	if(action){
		url=submit_url+'&action='+action;
	}else{
		url=submit_url+'&action=update';
	}
	reload_row=false;
	if(inline_collection){
		url_params="";
		for(i=0;i<inline_collection.length;i++){
			field_name=new String(inline_collection[i]);
			field_name=field_name.substring(name.length+6, field_name.indexOf('_inline'));
			form=document.getElementById(inline_collection[i]);
			if(form){
				url_params+='&'+'field_'+field_name+'='+encodeURIComponent(form.elements[0].value);
				//if form was not found - probably we moved to another page by some ajax action
				//but, may be, it is a browser incompatibility...
				reload_row=true;
			}
		}
		url=url+url_params;
	}
	if(reload_row){
		reloadGridRow(url,name,row_id,callback,true);
		//aasn(name+'_'+row_id, url);
		if(inline_active[name]['show_submit']){
			//hiding buttons
			row=document.getElementById(name+'_'+row_id);
		        nextrow=row.nextSibling;
        		row.parentNode.removeChild(nextrow);
		}
	}
	inline_active[name][row_id]=false;
}




/******* TreeView functions *******/
function treenode_flip(expand,id,url){
	button=new String(document.getElementById('ec_'+id).innerHTML);
	cll=document.getElementById('p_'+id);
        if(expand==1)cll.innerHTML = '<table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img alt="" src="'+am_loading+'"></td><td>&nbsp;</td><td class="smalltext" align=center id="autoexpander_'+id+'" valign=top><b>Loading. Stand by...</b></td></tr></table>';

	aasn('p_'+id,url);
	
	if(expand==1){
		button=button.replace('plus.gif', 'minus.gif');
		button=button.replace('ec_action=expand', 'ec_action=collapse');
		button=button.replace('treenode_flip(1', 'treenode_flip(0');
	}else{
		button=button.replace('minus.gif', 'plus.gif');
		button=button.replace('ec_action=collapse', 'ec_action=expand');
		button=button.replace('treenode_flip(0', 'treenode_flip(1');
	}
	document.getElementById('ec_'+id).innerHTML=button;
}

/******* MISC FUNCTIONS *******/
/**
 * Reloads a row of a Grid
 */
 
function reloadGridRow(url,name,row_id,callback,settitle){
	//row contents could not be replaced with aasn
	set_row_c = function(response_text, response_xml){
		//exploding string to an array of column values
		cols=response_text.split('<row_end>');
		id=name+'_'+row_id;
		row=document.getElementById(id);
		col=row.firstChild;
		i=0;
		while(col){
			if(col.innerHTML!=undefined){
				value=cols[i].split('<t>');
				col.innerHTML=value[0];
				if(settitle==true)col.title=value[1];
				// value[2] contains styles separated by <s>
				if(value[2]!=''){
					styles=value[2].split('<s>');
					for(j=0;j<styles.length;j++){
						// style cannot be assigned directly, 
						// so we analyze and set every property
						style=styles[j].split(':');
						switch(style[0]){
							case 'color':
								col.style.color=style[1];
								break;
							case 'cursor':
								col.style.cursor=style[1];
						}
					}
				}
				i++;
			}
			col=col.nextSibling;
		}
		try {
			if(typeof(callback) != 'undefined') eval(callback);
		} catch(e) {
			
		}
	}
	display_error = function(response_text,response_xml){
		alert(response_text);
	}
	aarq(url, set_row_c, display_error);
}
function w(url,width,height){
    window.open(url,'','width='+width+',height='+height+',scrollbars=yes,resizable=yes');
}

function ajax_done(){
    /* This function is called last from Ajax class. If this function is not called, then the whole output might contain
     * errors
     */
}
function w(url,width,height){
    window.open(url,'','width='+width+',height='+height+',scrollbars=yes,resizable=yes');
}
/**
 * Switches grouped field when given length is met
 */
function switchFieldOn(check_field_id, switchto_field_id, check_len){
	selected='';
	cf=document.getElementById(check_field_id);
	sf=document.getElementById(switchto_field_id);

	if (window.getSelection)   selected = cf.value.substring(cf.selectionStart, cf.selectionEnd);      
	else if (document.selection)   selected = document.selection.createRange().text;

	if (cf.value.length>=check_len && cf.value!=selected){
		sf.select();
		sf.focus();
		return false;
	}
}
