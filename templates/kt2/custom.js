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
}