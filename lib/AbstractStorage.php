<?php
/*
 * Created on 27.10.2006 by *Camper* (camper@adevel.com)
 */

class AbstractStorage extends AbstractController{

	protected $filespace_path='';
	public $file;

	function init(){
		parent::init();
		$this->file=$this->initFile();
	}
	function initFile(){
		$file=array(
			'filename'=>'', // user specified filename ('save as' name) or original filename
			'filesize'=>0,  // size of file, updates on file changes
			'filetype'=>'', // mime type of the file
			'temp_filename'=>'', // filename by which it is stored after upload, in temp dir
			'original_filename'=>'' // original file name, which was specified in 'browse' field of the form
		);
		return $file;
	}
	function getFileList(){
		return array();
	}
	protected function do_upload($filename) {
		if(is_uploaded_file($filename)) {
			clearstatcache();
			$this->file['filesize']=filesize($filename);
			return $filename;
		} else {
			return false;
		}
	}
	function downloadFile($file_id){
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
