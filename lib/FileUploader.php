<?php
/**
 * This class takes all required actions concerning file uploads, including errors resolving
 * It takes form field name as identifier for $_FILES array
 * Files uploaded are moved to $config['file_uploads_dir'] directory (don't forget to create it and set permissions)
 * 
 * Usage:
 * 
 * 1) Add field of type 'file'
 * 		$form->addField('file','file','Upload file')
 * 2) Add uploader to your form:
 * 		$form->add('FileUploader')
 * 3) On form submit call:
 * 		$form->getElement('fileuploader',false)->uploadFile($form->getElement('file')->name)
 * 4) Do what you want with file uploaded using FileUploader methods, e.g.:
 * 		$form->dq->set('file_data',$form->getElement('fileuploader',false)->getFile())
 * 5) Don't forget to delete temporary file if it is not required anymore, this will also clear
 *    session variables created for upload:
 * 		$form->getElement('fileuploader',false)->deleteFile()
 * 
 * @author Camper (cmd@adevel.com) on 20.05.2009
 */
class FileUploader extends AbstractController{
	protected $file_path='';
	protected $mime_type=null;

	function init(){
		parent::init();
		if(($path=$this->api->recall('uploader_file_path',false))!==false){
			$this->setFilePath($path);
			$this->setMimeType($this->api->recall('uploader_file_type'));
		}
	}
	function getFilePath(){
		if(!$this->file_path)return '';
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
		@unlink($this->getFilePath());
		$this->api->forget('uploader_file_path');
		$this->api->forget('uploader_file_type');
		return $this;
	}
	function uploadFile($field_name){
		$result=0;
		try{
			if(isset($_FILES[$field_name])&&$_FILES[$field_name]['name']!=''){
				$this->setFilePath($this->api->getConfig('file_uploads_dir','uploads').DIRECTORY_SEPARATOR.
					$_FILES[$field_name]['name']);
				// checking upload errors
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
					throw new FileException("Error uploading file");
				}
				// saving file attributes
				$this->setMimeType($_FILES[$field_name]['type']);
				$result=$this->getFileSize();
				if($result==0)throw new FileException("Unknown error during upload, file size is zero");
			}
		}catch(BaseException $e){
			// we should forget the file here
			$this->api->forget('uploader_file_path');
			$this->api->forget('uploader_file_type');
			throw $e;
		}
		return $result;
	}
	function isFileUploaded(){
		return $this->getFilePath()!='';
	}
}
