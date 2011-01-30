<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
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
