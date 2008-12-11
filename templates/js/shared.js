//KeyCodes
var kReturn=13;
var kTab=9;
var kEsc=27;

function isKeyPressed(e, kCode){
	var characterCode;

	if(e && e.which)characterCode = e.which;
	else characterCode=e.keyCode;

	return characterCode == kCode;
}
function denyEnter(e){
	return !isKeyPressed(e, kReturn);
}

function modifierPressed(e){
	var ctrlPressed=0;
	var altPressed=0;
	var shiftPressed=0;

	if (parseInt(navigator.appVersion)>3) {

	var evt = navigator.appName=="Netscape" ? e:event;

	if (navigator.appName=="Netscape" && parseInt(navigator.appVersion)==4) {
	// NETSCAPE 4 CODE
	var mString =(e.modifiers+32).toString(2).substring(3,6);
	shiftPressed=(mString.charAt(0)=="1");
	ctrlPressed =(mString.charAt(1)=="1");
	altPressed  =(mString.charAt(2)=="1");
	self.status="modifiers="+e.modifiers+" ("+mString+")"
	}
	else {
	// NEWER BROWSERS [CROSS-PLATFORM]
	shiftPressed=evt.shiftKey;
	altPressed  =evt.altKey;
	ctrlPressed =evt.ctrlKey;
	self.status=""
		+  "shiftKey="+shiftPressed 
		+", altKey="  +altPressed 
		+", ctrlKey=" +ctrlPressed 
	}
	}
	result=new Array(ctrlPressed,altPressed,shiftPressed);
	return result;
}


function detect_ie(){
	var agent=navigator.userAgent.toLowerCase();
	var i=agent.indexOf('msie');
	if (i!=-1) return parseFloat(agent.slice(i+5));
	else return false;
}


function detect_opera(){
	var agent=navigator.userAgent.toLowerCase();
	var i=agent.indexOf('opera');
	if (i!=-1) return parseFloat(agent.slice(i+6));
	else return false;
}
function htmlspecialchars(string, quote_style) {
	
	string = string.toString();
	
	// Always encode
	string = string.replace('/&/g', '&amp;');
	string = string.replace('/</g', '&lt;');
	string = string.replace('/>/g', '&gt;');
	
	// Encode depending on quote_style
	if (quote_style == 'ENT_QUOTES') {
		string = string.replace('/"/g', '&quot;');
		string = string.replace('/\'/g', '&#039;');
	} else if (quote_style != 'ENT_NOQUOTES') {
		// All other cases (ENT_COMPAT, default, but not ENT_NOQUOTES)
		string = string.replace('/"/g', '&quot;');
	}
	
	return string;
}
function w(url,width,height){
	window.open(url,'','width='+width+',height='+height+',scrollbars=yes,resizable=yes');
}
// checks the extension of the filename in the field specified
function checkExtension(form_id, field_id, ext){
	if(!ext)ext = new Array("jpg","jpeg","gif","bmp","png");
	filename=aagv(form_id, field_id);
	if(filename==''){
		// no check for empty values, check them by other function
		return true;
	}
	allowSubmit=false;
	while (filename.indexOf("\\") != -1)filename = filename.slice(filename.indexOf("\\") + 1);
	fileext = filename.split(".");
	for (var i = 0; i < ext.length; i++) {
		// file can have multiple extensions like file.jpg.html
		for(j=0;j<fileext.length;j++){
			extStr=fileext[j].toLowerCase();
			if (ext[i] == extStr) { allowSubmit = true; break; }
		}
	}
	if (!allowSubmit){
		alert("Please only upload files with extension "
			+ (ext.join(", ")) + "\nPlease select a new "
			+ "file to upload and submit again.");
		return false;
	}
	return true;
}
