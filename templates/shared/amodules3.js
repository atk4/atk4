// Support for expanders @ Lister

var expander_open=new Array();    
    /* we remember opened expanders so we know what to do when clicked */
var inline_active=new Array();
    /* same as for expanders, but applying to inline edits. 
       they should be processed differently 
       this array contains URLs to close inlines
    */
function inline_is_active(name,id){
	if(inline_active[name])return inline_active[name][id]&&inline_active[name][id]!=false;
	else return false;
}

function expander_is_open(name,id,button){
    /*
     * This would just return exander's state on a button click. If another expander is
     * open, it would not do anything, just return it's button's name.
     */
    //inlines should be hided anyway, no submission
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

        
        tmp=row.parentNode.firstChild;
        if(!tmp.firstChild)tmp=tmp.nextSibling;
        header = tmp.firstChild;
        for(cs=1;header=header.nextSibling;cs++);

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
        bg="#FFFFFF";
        
        /* chk */
        //tmp = row./*parentNode.firstChild.*/childNodes;
        //alert(tmp.length);
        
        cll = newrow.insertCell(0);
        cll.style.backgroundColor = bg;
        cll.style.borderWidth = '0 1px 1px 1px';
        cll.style.borderColor = '#000';
        cll.style.borderStyle = 'solid';
        cll.style.padding = '15px';
        
        cll.colSpan = cs; //row.parentNode.firstChild.childNodes.length;
        
        //cll.id = name+"_expandedcontent_"+id;        
        cll.setAttribute('id', name+"_expandedcontent_"+id);
        
        cll.innerHTML = '<table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img src=amodules3/img/loading.gif></td><td>&nbsp;</td><td class="smalltext" align=center id="autoexpander_'+id+'" valign=top><b>Loading. Stand by...</b></td></tr></table>';
        
        /* /chk */
        
        //newrow.innerHTML='<td style="background: '+bg+'; border: 1px solid black; border-top: 0px; padding: 15px" colspan="'+cs+'" id="'+name+"_expandedcontent_"+id+'" ><table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img src=amodules3/img/loading.gif></td><td>&nbsp;</td><td class="smalltext" align=center id="autoexpander_'+id+'" valign=top><b>Loading. Stand by...</b></td></tr></table></td>';
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
	var value=new String(document.getElementById(inline_id).innerHTML);
	/* stripping any tags from a value 
	   initial string is: <font color="blue">Value</font>
	*/
	v=value.split(">");
	//now we have v=array(0=>'<font color="blue"', 1=>'Value</font'
	value=new String(v[1]).split("<");
	//value='edt';
	return value[0];
}
function isKeyPressed(e, kCode){
	var characterCode;

	if(e && e.which){ 
		characterCode = e.which;
	}else{
		characterCode = e.keyCode;
	}

	return characterCode == kCode;
}
function inline_process_key(e, name, id, activate_next){
	kReturn=13;
	kTab=9;
	kEsc=27;
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
		row=document.getElementById(name+'_'+id);
		while(row.nextSibling){
			row=row.nextSibling;
			if(row.id)break;
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
function inline_show(name,active_field,row_id,submit_url,activate_next,show_submit){
	// changes row content to a forms with input elements
	// submit_url is an URL that should store changes from inline to DB
	// name is a name of a grid
	// active_field - inline control that should be set active
	// row_id - guess

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
				inline_hide(name,i,inline_active[name][i]);
		}
	}
	row=document.getElementById(id);
	//counting columns	
        tmp=row.parentNode.firstChild;
        if(!tmp.firstChild)tmp=tmp.nextSibling;
        header = tmp.firstChild;
        for(cs=1;header=header.nextSibling;cs++);
	//changing row contents to the forms. only for inlines...
	col=row.firstChild;
	var inline_collection=new Array();
	var index=0;
	for(i=1;col=col.nextSibling;i++){
		id=new String(col.id);
		if(id.indexOf('_inline')!=-1){
			var form_name='form_'+id;
			
			col.innerHTML='<form id="'+form_name+'" name="'+form_name+'" method="POST">'+
				'<input id="'+form_name+'_edit" value="'+
				getInlineValue(id)+'" type="text" onKeyPress="'+
				'return inline_process_key(event,\''+name+'\','+row_id+','+activate_next+');"></form>';
			inline_collection[index]=form_name;
			index++;
		}
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
	        bg="#FFFFFF";
        
        	cll = newrow.insertCell(0);
		cll.align='right';
        	cll.style.backgroundColor = bg;
	        cll.style.borderWidth = '0 1px 1px 1px';
        	cll.style.borderColor = '#000';
	        cll.style.borderStyle = 'solid';
        	cll.style.padding = '1px';
        
	        cll.colSpan = cs; 

		event_handler="inline_hide('"+name+"',"+row_id+",'";
		cll.innerHTML='<form id="'+form_name+'" name="'+form_name+'" method="POST">'+
			'<input type="button" value="OK" onclick="'+event_handler+'update\');">'+
			'<input type="button" value="Cancel" onclick="'+event_handler+'cancel\');">'+
			'</form>';
	}
	//selecting an edit
	document.getElementById('form_'+inline_id+'_edit').select();
	//setting an array value for further hiding
	if(!inline_active[name])inline_active[name]=new Array();
	inline_active[name][row_id]=new Array();
	inline_active[name][row_id]['submit_url']=submit_url;
	inline_active[name][row_id]['inline_collection']=inline_collection;
	inline_active[name][row_id]['active_field']=active_field;
	inline_active[name]['show_submit']=show_submit;
}
function inline_hide(name, row_id, action){
	//name is a grid name, id is a row id
	//processing inline: submit or cancel
	submit_url=inline_active[name][row_id]['submit_url']+'&row_id='+row_id;
	inline_collection=inline_active[name][row_id]['inline_collection'];
	if(action){
		url=submit_url+'&action='+action;
	}else{
		url=submit_url+'&action=cancel';
	}
	if(inline_collection){
		url_params="";
		for(i=0;i<inline_collection.length;i++){
			field_name=new String(inline_collection[i]);
			field_name=field_name.substring(name.length+6, field_name.indexOf('_inline'));
			url_params+='&'+'field_'+field_name+'='+document.forms[inline_collection[i]].elements[0].value;
		}
		url=url+url_params;
	}
	aasn(name+'_'+row_id, url);
	if(inline_active[name]['show_submit']){
		//hiding buttons
		row=document.getElementById(name+'_'+row_id);
	        nextrow=row.nextSibling;
        	row.parentNode.removeChild(nextrow);
	}
	inline_active[name][row_id]=false;
}
/******* TreeView functions *******/
function treenode_flip(expand,id){
	button=new String(document.getElementById('ec_'+id).innerHTML);
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
