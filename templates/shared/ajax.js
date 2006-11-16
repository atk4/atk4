// Copyright � 2005 ADevel.com ( https://adevel.com/ )
// Author: Kirill Chernyshov ( chk@adevel.com )
var ajaxIsIE = false;

/**
 *    success_handler = function(response_text, response_xml);
 *    error_handler = function();
 *
 */

// Calls success_handler() if src_url loaded successfully. 
// Otherwise error_handler() if specified
// ajaxRequest
function aarq( src_url, success_handler, error_handler ){
    var req = null;

    if((!src_url) || (!success_handler))
        return false;

    if (window.XMLHttpRequest){
        req = new XMLHttpRequest();
    }else{
        if (window.ActiveXObject){
            ajaxIsIE = true;
            req = new ActiveXObject("Microsoft.XMLHTTP");
        }
    }
    if (req) {
    	req.onreadystatechange = function(){
    		if (req.readyState == 4) {
			    try{
			    	if (req.status == 200) {
		        		success_handler(req.responseText, req.responseXML);
			        } else {
						if(error_handler)
			        		error_handler();
			        }
			    }
				finally{}
			}
    	};
        req.open('GET', src_url, true );            
        req.send("");
    }
    return false;
}

//Submits the form with specified id using its 'method' and 'action' properties.
// submission url could be replaced with custom_url
// if form is submitted successfully, success_handler() will be called, 
// on error - error_handler()
// If request returned the XML content and success_handler is not specified,
// the default XML parsed would be called (ajaxDefaultXMLParser)
// ajaxSubmitForm
function aasf( form_id, custom_url, success_handler, error_handler ){
	var req = null;
	var frm = document.getElementById( form_id );
	var url = '';
	var method = 'get';
	
	if ( !frm )
		return false;	
	if( !custom_url ) {
		if(frm.action )	
			url = frm.action;
	}
	else{
		url = custom_url;
	}
	
	if( url == '' )	 
		return false;
	if( frm.method )
		method = frm.method;
	
	var params = aacp( form_id )+'&ajax_submit=true';
	
	if (window.XMLHttpRequest)
		req = new XMLHttpRequest();
    else {
	    if (window.ActiveXObject){
	    	ajaxIsIE = true;
	    	req = new ActiveXObject("Microsoft.XMLHTTP");
	    }
    }
    
    if (req) {
    	req.onreadystatechange = function(){
    		if (req.readyState == 4) {
			    try{
			    	if (req.status == 200) {
			    		if(success_handler)
			        		success_handler(req.responseText, req.responseXML);
			        	else{
			        		if( req.responseXML )
			        			aadp( req.responseXML );
			        	}
			        } else {
						if(error_handler)
			        		error_handler();
			        }
			    }
				finally{
					aaec( form_id );
				}
			}
    	};
    	
    	if(method.toLowerCase() == 'get'){
        	req.open('get', url+'?'+params, true );            
        	req.send('');
        }
        else{
        	req.open('post', url, true );            
        	req.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    		req.send(params);
        }
        aadc( form_id );
    }
    return true;
}

// the Default XML Response Parser
// XML must contail the toplevel item 'elements' that soud consist of 
// subitems with html ids as tagnames. Every id tag may contain one 
// of the following nodes:
// a) innerHTML; 
// b) value; 
// c) src; 
// d) className;
// e) checked;
// This nodes specify the corresponding properties of the html element.
// ajaxDefaultXMLParser
function aadp( xml_response ){
	// TODO: just implement it ;)
	if(!xml_response)
		return false;
	
	// go through item ids
	var _elements;
	try{_elements= xml_response.getElementsByTagName("elements")[0];}
		catch(e){return false;	}
	if( _elements ){
		var i, _src, _innerHTML, _value, _className, xml_item;
		for(i=0; i<_elements.childNodes.length; i++){
			xml_item = _elements.childNodes[i];
			html_item = document.getElementById( xml_item.nodeName );
			if(html_item){
				_src = xml_item.getElementsByTagName("src")[0];
				if(_src)
					try{ html_item.src = aagn( null, 'src', xml_item, 0); }finally{}					
					
				_value = xml_item.getElementsByTagName("value")[0];
				if(_value)
					try{ html_item.value = aagn( null, 'value', xml_item, 0); }finally{}
					
				_innerHTML = xml_item.getElementsByTagName("innerHTML")[0];
				if(_innerHTML)
					try{ html_item.innerHTML = aagn( null, 'innerHTML', xml_item, 0); }finally{}
										
				_className = xml_item.getElementsByTagName("className")[0];
				if(_className)
					try{ html_item.className = aagn( null, 'className', xml_item, 0); }finally{}
					
				_checked = xml_item.getElementsByTagName("checked")[0];
				if(_checked)
					try{ html_item.checked = aagn( null, 'checked', xml_item, 0); }finally{}
			}
		}
	}
	
	return true;
}

// explicitly replaces the content of specified HTML element with result of requesting src_url
// ajaxSetContent
function aasn(element_id, src_url){
	var callback = function(response_text, response_xml){aafc( element_id, response_text);};
	return aarq( src_url, callback);
}

// eSimilar to aasn, but replaces form field value
// ajaxSetValue
function aasv(element_id, src_url){
	var callback = function(response_text, response_xml){aafv( element_id, response_text);};
	return aarq( src_url, callback);
}


// represents form params as a string
// supports text, textarea, radiobuttons, select (not multiple), checkboxes.. 
// ajaxComposeParams
function aacp( form_id ){
	var j;
	var val;
	var elem;
	var res = '';
	if(!form_id)
		return res;		
	var frm = document.getElementById( form_id );
	if(frm)
		for(var i=0; i<frm.elements.length; i++){
			if(res != '')
				res+='&';
			if(frm.elements[i].type == 'checkbox'){
                if(!frm.elements[i].checked)continue;
            }
			if(frm.elements[i].type == 'radio'){
				elem = frm.elements[frm.elements[i].name];
				val = 'null';
				for(j=0; j < elem.length; j++){
					if(elem[j].checked)
						val = elem[j].value;
				}
				res+=frm.elements[i].name+'='+val;
			}
			else if(frm.elements[i].type == 'file'){
				res+=frm.elements[i].name+'='+serializeJsToPhp(frm.elements[i].value);
			}
			else{
				res+=frm.elements[i].name+'='+encodeURIComponent(frm.elements[i].value);
			}
		}
	return res;
}
function isArray(obj) {
    if (obj.constructor.toString().indexOf("Array") == -1) {
        return false;
    } else {
        return true;
    }
}

function serializeJsToPhp(jsArray) {
    var arrayLength = jsArray.length;
    var phpString = "a:"+arrayLength+":{";

    for(var i=0; i<arrayLength; i++) {

        // prefix for integer based arrays
        phpString += "i:"+i;

        if(!isArray(jsArray[i])) {
            phpString += ";s:"+jsArray[i].length+
                           ":\""+jsArray[i]+"\";";
        } else {
            phpString += ";"+serializeJsToPhp(jsArray[i]);
        }
    }

    phpString += "}";
    return phpString;
}
// retrieves the content of XML item 'prefix:local[index]' of the parent item as a text.
// returns empty string on failure.
// getNodeText
function aagn(prefix, local, parentElem, index) {
    try{
	    var res = "";
	    if (prefix && ajaxIsIE) {
	        res = parentElem.getElementsByTagName(prefix + ":" + local)[index];
	    } else {
	        res = parentElem.getElementsByTagName(local)[index];
	    }
	    if (res) {
	        if (res.childNodes.length > 1) {
	            return res.childNodes[1].nodeValue;
	        } else {
	            return res.firstChild.nodeValue;    		
	        }
	    } else {
	        return "";
	    }
	}
	catch(e){
		return "";
	}
}

// disables all elements of form
// disableControls
function aadc( form_id ){
	var frm = document.getElementById(form_id);
	if(frm)
		for(var i=0; i<frm.elements.length; i++){
			frm.elements[i].disabled = true;
		}
}

// enables all form elements
// enableControls
function aaec( form_id ){
	var frm = document.getElementById(form_id);
	if(frm)
		for(var i=0; i<frm.elements.length; i++){
			frm.elements[i].disabled = false;
		}
}

// sets the innerHTML property of specified element
// fillContent
function aafc( elem_id, new_val){
	var res = false;
	try{
		var elem = document.getElementById(elem_id);
		if (elem){
			elem.innerHTML = new_val;
			res = true;
		}
	}
	finally{}
	
	return res;
}

// sets the value property of form elemenet
// fillValue
function aafv( elem_id, new_val){
	var res = false;
	try{
		var elem = document.getElementById(elem_id);
		if (elem){
			elem.value = new_val;
			res = true;
		}
	}
	finally{}
	
	return res;
}
// gets the value of form element
// getValue
// camper@adevel.com
function aagv(form_id, elem_name){
	var res=false;
	try{
		if(!form_id){
			res=document.getElementById(elem_name).value;
		}else{
			form=document.getElementById(form_id);
			if(form){
				res=form.elements[elem_name].value;
			}
		}
	}
	finally{}
	return res;
}

// Get element by ID in all browsers
// jancha@adevel.com
function aagi( elem_id ){
	if(document.getElementById && document.getElementById(elem_id)) {
		return document.getElementById(elem_id);
	} else if (document.all && document.all(elem_id)) {
		return document.all(elem_id);
	} else if (document.layers && document.layers[elem_id]) {
		return document.layers[elem_id];
	} else {
		return false;
	}
}

function spinner_on(spinner, timeout){
    /* This function starts a spinning wheel */
    var s = document.getElementById(spinner);
    if(!s)return;
    if(s.src){
        /* seems to be img */
        s.src='amodules3/img/loading.gif';
    }else{
        s.innerHTML='<img src="amodules3/img/loading.gif" alt="loading">';
    }
    if(timeout == null)timeout=3000;
    if(timeout>0)setTimeout("spinner_off('"+spinner+"')",timeout);
}
function spinner_off(spinner){
    /* This function stops a spinning wheel */
    var s = document.getElementById(spinner);
    if(!s)return;
    if(s.src){
        /* seems to be img */
        s.src='amodules3/img/not_loading.gif';
    }else{
        s.innerHTML='<img src="amodules3/img/not_loading.gif" alt="loading">';
    }
}

function aaej(src_url, spinner, argument){
    if(spinner)spinner_on(spinner);
	var callback = function(response_text, response_xml){
        eval(response)
        aafc( element_id, response_text);
    };

	return aarq( src_url, callback);
}

function submitForm(form,spinner){
	var callback = function(response_text, response_xml){
        if(response_text){
            try {
                eval(response_text);
            }catch(e){
            	//while some browsers prevents popup we better use alert
            	alert("Error in AJAX response: "+e+"\n"+response_text);
                /*w=window.open(null,null,'height=400,width=700,location=no,menubar=no,scrollbars=yes,status=no,titlebar=no,toolbar=no');
                w.document.write('<h2>Error in AJAX response: '+e+'</h2>');
                w.document.write(response_text);
                w.document.write('<center><input type=button onclick="window.close()" value="Close"></center>');*/
                try{
                	eval(response_text.substring(response_text.indexOf('//ajax_script_start'),response_text.lastIndexOf('//ajax_script_start')));
                } catch(e) {
                	alert('Could not parse response. '+e);
                }
            }
        } else {
        	alert("Warning: Empty response from server");
        }
        if(spinner)spinner_off(spinner);
    };
    return aasf(form,null,callback);
}
