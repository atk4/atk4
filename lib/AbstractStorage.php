<?php
/*
 * Created on 27.10.2006 by *Camper* (camper@adevel.com)
 */

class AbstractStorage extends AbstractController{

	protected $filespace_path='';
	public $upload_temp_name;
	public $upload_orig_name;
	public $upload_save_name;
	public $upload_filesize;
	public $upload_filetype;

	function init(){
		parent::init();
	}
	function getFileList(){
		return array();
	}
	protected function do_upload($filename) {
		if(is_uploaded_file($filename)) {
			$this->upload_filesize=filesize($filename);
			return $filename;
		} else {
			return false;
		}
	}
	function downloadFile($filespace_id, $file_id){
		/**
		 * Returns file data by filespace id and file id
		 */
		return null;
	}
	function uploadFile($file){
		/**
		 * Uploads a file from a given $_FILES argument. File type is determined in this method
		 */
		return null;
	}
	function getFileType($filename){
		/**
		 * returns file type based on a file extension.
		 */
	}
	function deleteFile($file_id){
		throw new FileException('No default handler. Redefine in your ansestor');
	}
	function getFilespacePath(){
		return $this->filespace_path;
	}
	function getFilespaceId(){
		return $this->filespace_id;
	}
	function beforeSave(){
		/**
		 * Executes just after file was uploaded and before it is saved to the specified filespace
		 */
	}
}
class FileException extends BaseException{}