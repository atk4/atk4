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
 *    directory where uploaded files will be stored in config file
 * 
 *    $config['ajax_uploads_dir']='/uploads';
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
class AjaxFileUploader extends AbstractController{
	protected $file_path='';
	protected $mime_type=null;
	
	function init(){
		parent::init();
		// checking file uploads
		if(isset($_GET['file_upload'])){
			$this->uploadFile($_GET['file_upload']);
		}
		if(($path=$this->api->recall('uploader_file_path',false))!==false){
			$this->setFilePath($path);
			$this->setMimeType($this->api->recall('uploader_file_type'));
		}
	}
	function getFilePath(){
		return $this->file_path;
	}
	function setFilePath($path){
		$this->file_path=$path;
		$this->api->memorize('uploader_file_path',$path);
		return $this;
	}
	function getFile(){
		return file_get_contents($this->getFilePath());
	}
	function getMimeType(){
		return $this->mime_type;
	}
	function setMimeType($type){
		$this->mime_type=$type;
		$this->api->memorize('uploader_file_type',$type);
		return $this;
	}
	function getFileSize(){
		return filesize($this->getFilePath());
	}
	function deleteFile(){
		unlink($this->getFilePath());
		$this->api->forget('uploader_file_path');
		$this->api->forget('uploader_file_type');
		return $this;
	}
	private function uploadFile($field_name){
		$result=0;
		try{
			if(isset($_FILES[$field_name])&&$_FILES[$field_name]['name']!=''){
				$this->setFilePath($this->api->getConfig('ajax_uploads_dir','uploads').DIRECTORY_SEPARATOR.
					$_FILES[$field_name]['name']);
				// checking upload errors
				//$this->api->logger->logVar($_FILES[$field_name]['error']);
				if($_FILES[$field_name]['error']!=UPLOAD_ERR_OK){
					switch($_FILES[$field_name]['error']){
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							throw new BaseException("File size exceeds the allowed limit");
						case UPLOAD_ERR_PARTIAL:
							throw new BaseException("File uploaded partially");
					}
				}
				if(!move_uploaded_file($_FILES[$field_name]['tmp_name'],$this->getFilePath())){
					throw new BaseException("Error uploading file");
				}
				// saving file attributes
				$this->setMimeType($_FILES[$field_name]['type']);
				$result=$this->getFileSize();
			}
		}catch(BaseException $e){
			$result=$e->getMessage();
		}
		header("Content-type: application/xml; charset=UTF-8");
		echo '<?xml version="1.0" encoding="UTF-8" ?><result>'.$result.'</result>';
		exit;
	}
	function isFileUploaded(){
		return $this->getFilePath()!='';
	}
}
