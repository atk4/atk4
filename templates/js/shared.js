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
// Refreshes available months and days for date selector <
function refreshDateSelector(name) {
	
	// Get day select  <
	var days_select = document.getElementById(name + '_day');
	
	// get month and year value <
	var month = document.getElementById(name + '_month').value;
	var year = document.getElementById(name + '_year').value;
	
	// calculate max depending on year and month <
	var max_day;
	switch (month) {
		case '1', '3', '5', '7', '8', '10', '12' : {max_day = 31; break;}
		case '4', '6', '9', '11' : {max_day = 30; break;}
		case '2' : {max_day = (year % 4 ? 28 : 29); break;}
	}

	// Get options <
	var options = days_select.getElementsByTagName('option');
	
	// display all options except the one with the value <
	for (var i = 0; i < options.length; i++) {
		if (options.item(i).value > max_day) {
			options.item(i).style.display = "none";
		} else {
			options.item(i).style.display = "";
		}
	}
	
	// Make new selection if the currently selected is the hidden <
	if (days_select.value > max_day) {
		days_select.value = max_day;
	}
}
// enables or disables DateSelector on checkbox click
function switchDateSelector(control,name){
	var d=document;
	var day=d.getElementById(name+'_day');
	var month=d.getElementById(name+'_month');
	var year=d.getElementById(name+'_year'); 
	
	if(!control.checked){
		if(day)day.disabled=true;
		if(month)month.disabled=true;
		if(year)year.disabled=true;
	}else{
		if(day)day.disabled=false;
		if(month)month.disabled=false;
		if(year)year.disabled=false;
	}
}
