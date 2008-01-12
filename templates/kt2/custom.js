function trim(stringToTrim)
{
	return stringToTrim.replace(/^\s+|\s+$/g,"");
} 


function isurl(url)
{
	var regexp=/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
	if (!regexp.test(url))
		{
			alert('Incorrect url (e.g.  http://www.adevel.com)');
			exit;
		}
		return true;
}

function isNumberKey(evt)
{
	var charCode = (evt.which) ? evt.which : event.keyCode
	if (charCode > 31 && (charCode < 48 || charCode > 57)) return false;
	return true;
}

function eraseBox(value)
{
	if (document.getElementById(value)!=null) document.getElementById(value).outerText="";
	return true;
}