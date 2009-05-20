<?php
/**
 * This class performs file uploads for Ajax forms.
 * There is the only way I've found to do this: through IFrames.
 * The example I used as a basement is on the http://web-tec.info/2007/09/09/ajax_fundamentals_iframe/
 *
 * Usage:
 * 1) Form which would upload file through Ajax should NOT be submitted by submitForm(),
 *    use Ajax::uploadFile() instead:
 *
 *    $form->addButton('Submit')->uploadFile($form,$form->getElement('file')->name);
 * 2) AjaxFileUploader class will be created and used to handle file upload. You can configure
 *    directory where uploaded files will be stored in config file.
 *    NOTE: always use absolute path
 *
 *    $config['file_uploads_dir']=ROOTDIR.'/uploads';
 *    uploads/ dir in project root will be used by default.
 * 3) You can reach uploaded file by calling:
 *
 *    $file=$this->api->getElement('uploader')->getFile();
 *    $file_path=$this->api->getElement('uploader')->getFilePath();
 * 4) Form is submitted from upload JS code after file is uploaded
 *
 * Ajax uploads use JS script functions from the ajax.js:
 * createIFrame()
 * submitUpload()
 * sendComplete()
 *
 * If no file specified in the form, nothing done and form is simply submitted
 *
 * You may upgrade and improve this class on your own, probably it is not the best implementation.
 *
 * Created on 21.06.2008 by *Camper* (camper@adevel.com)
 */
class AjaxFileUploader extends FileUploader{
	protected $file_path='';
	protected $mime_type=null;

	function init(){
		// checking file uploads
		if(isset($_GET['file_upload'])){
			$this->uploadFile($_GET['file_upload']);
		}
		parent::init();
	}
	function uploadFile($field_name){
		try{
			$result=parent::uploadFile($field_name);
		}catch(Exception $e){
			$result=$e->getMessage();
		}
		header("Content-type: application/xml; charset=UTF-8");
		echo '<?xml version="1.0" encoding="UTF-8" ?><result>'.$result.'</result>';
		exit;
	}
}
