<?php
/**
 * Class for file storage using database.
 * Actually files are stored on disk, in specific directory structure:
 * 	Each directory contains 255 files at the most
 * 	Each filename is a hex number (from 00 to FF)
 * Original file names are stored in database along with real storage info
 * 4 tables needed to store file data:
 * 	filespace: info about filespaces (directories, local or network discs, etc.)
 * 	filetype
 * 	filedelnum: deleted files storage info. Needed to reuse deleted file numbers
 * 	file: currently stored files
 *
 * Benefits:
 * 	no long names that could cause troubles
 * 	no overfilled directories that could cause access time increase
 * 	ability to use different types of datastorages, including network disks
 *
 * Created on 27.10.2006 by *Camper* (camper@adevel.com)
 */

class DatabaseStorage extends AbstractStorage{
	protected $files;
	protected $filespaces;
	protected $filetypes;
	protected $deleted_files;

	function init(){
		parent::init();
		//TODO may be assign tables in setSource
		$this->files=$this->api->db->dsql()->table('file');
		$this->filespaces=$this->api->db->dsql()->table('filespace');
		$this->filetypes=$this->api->db->dsql()->table('filetype');
		$this->deleted_files=$this->api->db->dsql()->table('filedelnum');
	}
	function getFileList(){
		$file_list = array();
		//TODO get file list from DB. array should be applicable for datasource usage
		$file_list=$this->files->field('*')->calc_found_rows()->do_getAllHash();
		return $file_list;
	}
	function getFiles(){
		return $this->files;
	}
	function getFilespaces(){
		return $this->filespaces;
	}
	function getFilespacePath(){
		return $this->getFilespaces()->clear_args('where')->clear_args('fields')
			->where('id',$this->getFilespaceId())->field('dirname')->do_getOne();
	}
	function getFilespaceId($filesize=false) {
		if($filesize===false)return $this->filespace_id;
		if (($res = $this->api->db->getHash("select id, dirname " .
						"from ".DTP.'filespace'.
						" where enabled = '1' and (used_space + $filesize) <= total_space " .
						" and stored_files_cnt < 4096*256*256 " .
						" limit 1"))===false) {
			throw new FileException("Error getting filespace");}
		if (empty($res) or (disk_free_space($res['dirname'])<$filesize)) {
			throw new FileException('Unavailable filespace for file!');
		}
		$this->filespace_id=$res['id'];
		return $this->filespace_id;
	}

	function getFilesize($file_id){
		return $this->files->clear_args('where')->where('id',$file_id)->field('filesize')->do_getOne();
	}
	function getFilenum($file_id){
		return $this->files->clear_args('where')->clear_args('fields')->where('id',$file_id)->field('filenum')->do_getOne();
	}
	function getFilename($file_id){
		return $this->files->clear_args('where')->clear_args('fields')->where('id',$file_id)->field('filename')->do_getOne();
	}
	function deleteFile($file_id){
		$filenum=$this->getFilenum($file_id);
		$filespace_id=$this->files->clear_args('fields')->where('id',$file_id)->field('filespace_id')->do_getOne();
		if ($res = @unlink($this->realFilePath($file_id))) {
			// update filespace record (decrease used space)
			$this->filespaces
				->set('used_space = used_space - '.$this->getFilesize($file_id))
				->set('stored_files_cnt = stored_files_cnt - 1')
				->where('id',$filespace_id)
				->do_update();
			/*
			// enabled filespace if used space less than total space
			if ($db->query('update '.tbn('filespace').
			'   set enabled = \'1\' '.
			' where enabled = \'0\' and used_space < total_space and id = :id',
			array('int id'=>$this->_filespace_id))===false) db_error(__FILE__,__LINE__);
			 */
			// delete record desctibe currect file
			$this->files->clear_args('where')
				->where('id',$file_id)
				->do_delete();

			$this->deleted_files
				->set('filespace_id',$filespace_id)
				->set('filenum',$filenum)
				->do_insert();
		}else{
			throw new FileNotFoundException("Could not delete file ID=".$file_id."; path: ".
					$this->realFilePath($file_id));
		}

		return $res;
	}
	function downloadFile($file_id){
		/**
		 * Returns file data by filespace id and file id
		 */
		$this->filespace_id=$this->files->clear_args('fields')->where('id',$file_id)->field('filespace_id')->do_getOne();
		$file=$this->files->table('file file')->clear_args('where')
			->join('filetype filetype','filetype.id=file.filetype_id')
			->where('file.id',$file_id)
			->field('file.id,file.filename,filetype.mime_type,file.filenum')
			->do_getHash();
		header("Content-type: ".$file['filetype']);
		header('Content-Disposition: attachment; filename="'.$file['filename'].'"');
		echo file_get_contents($this->realFilePath($file['id']));
	}
	function uploadFile($files_array, $saveas_name=''){
		//assigning vars
		$this->file['original_filename']=$files_array['name'];
		$this->file['temp_filename']=$files_array['tmp_name'];
		$this->file['filename']=$saveas_name==''?$this->file['original_filename']:$saveas_name;
		$this->file['filetype']=$files_array['type'];
		$this->file['temp_filename']=$this->do_upload($this->file['temp_filename']);
		if($this->file['temp_filename']===false){
			switch($files_array['error']){
				case 1: case 2:
					throw new FileException("File size exceeds the limit");
				case 3:
					throw new FileException("File was uploaded partially");
				case 4:
					throw new FileException("File was not uploaded");
				default:
					throw new FileException("Unknown reason: ".$files_array['error']);
			}
		}
		// checking if file was uploaded
		if($this->file['filesize']==0)throw new BaseException("Error uploading file.");
		// getting filespace for this file
		$this->file['filespace_id']=$this->getFilespaceId($this->file['filesize']);
		if(!$this->file['filespace_id'])throw new FileException("Error getting filespace");
		// savinf file into storage
		$this->file=$this->saveFile($this->file);
		return $this->file['id'];
	}
	function beforeSave($file){
		return $file;
	}
	function afterSave($file){
		return $file;
	}
	protected function saveFile($file){
		/**
		 * Save file into storage. Takes an array as argument (see AbstractStorage::file property)
		 */
		// hooks before saving
		$file=$this->beforeSave($file);
		$this->files->clear_args('where')->clear_args('fields');
		$this->deleted_files->clear_args('where')->clear_args('fields');
		try{
			//locking
			if (($res_op = $this->api->db->getOne("/* hack */select get_lock('fs_fnum_lock',5)"))===false)
				throw new FileException("Error getting record (wonder why?!)");
			if (empty($res_op)) throw new FileException('Error getting lock for filenumber calculation.');
			//trying to get number from the deleted list
			$filenum=$this->deleted_files->field('filenum')->where('filespace_id',$file['filespace_id'])
				->do_getOne();
			//if successful - use deleted number...
			if (!empty($filenum)) {
				$this->deleted_files->where('filenum',$filenum)->where('filespace_id',$file['filespace_id'])
					->do_delete();
			}else{
				//...else creating new one
				$filenum=$this->api->db->dsql()->table('file')
					->field('max(filenum)+1')
					->where('filespace_id',$file['filespace_id'])
					->do_getOne();
				$filenum=(empty($filenum))?1:$filenum;
			}
			//storing file number in its array
			$file['filenumber']=$filenum;
			//inserting a record to files
			$this->files
				->set('filespace_id',$file['filespace_id'])
				->set('filetype_id',$this->getFileTypeId($file['filetype']))
				->set('filenum',$file['filenumber'])
				->set('filename',$file['filename'])
				->set('filesize',$file['filesize'])
				->do_insert();

			$_id=$this->api->db->lastId();
			// release lock
			if (($res_op=$this->api->db->getOne("/* hack */select release_lock('fs_fnum_lock')"))===false)
				throw new FileException("Error releasing lock");

			// moving file to its actual destination
			$file['real_filename']=$this->realFilePath($_id);
			if(!@rename($file['temp_filename'],$file['real_filename'])) {
				$this->files->where('id',$_id)->do_delete();
				$_id=null;
				@unlink($file['temp_filename']);
				throw new FileException('Error moving file '.$this->file['temp_filename'].' into filespace ('.
							$this->realFilePath($_id).')!');
			}else{
				// increase used space
				if ($this->filespaces->where('id',$file['filespace_id'])
						->set('used_space = used_space + '.$file['filesize'])
						->set('stored_files_cnt = stored_files_cnt + 1')
						->do_update()===false) throw new FileException("Error updating filespace");
				// erasing temp name as it is no more
				$file['temp_filename']='';
			}
		}catch (FileException $e){
			//releasing locks
			if (!($this->api->db->getOne("/* hack */select IS_FREE_LOCK('fs_fnum_lock')")))
				$this->api->db->getOne("/* hack */select release_lock('fs_fnum_lock')");
			throw $e;
		}
		//saving file id
		$file['id']=$_id;
		//hooks after upload
		$file=$this->afterSave($file);
		return $file;
	}
	function getFileTypeId($filetype){
		/**
		 * Returns an ID of filetype by name
		 */
		$id=$this->filetypes->field('id')->where('mime_type',$filetype)->do_getOne();
		if (empty($id)) {
			$this->filetypes->set('mime_type',$filetype)->do_insert();
			$id = $this->api->db->lastId();
		}
		unset($this->filetypes->args['where']);
		return $id;
	}
	//********** FileHandler routines ***********
	/**
	 *  return filename in filespace, create directories if necessary
	 */
	protected function _real_filename($file_id,$filenum='',$create_dir=true) {
		if($filenum=='')$filenum=$this->getFilenum($file_id);
		$path = str_pad(substr(strtoupper(dechex($filenum)),-7,7), 7, '0', STR_PAD_LEFT);
		$res = $this->getFilespacePath().DIRECTORY_SEPARATOR.substr($path,0,3);

		if (!file_exists($res)){
			if($create_dir){if(!@mkdir($res))throw new FileException('Error create directory '.$res);}
			else return null;
		}

		$res .= DIRECTORY_SEPARATOR.substr($path,3,2);
		if (!file_exists($res)){
			if($create_dir){if(!@mkdir($res))throw new FileException('Error create directory '.$res);}
			else return null;
		}

		$res .= DIRECTORY_SEPARATOR.substr($path,5,2);

		return $res;
	}
	function realFilePath($file_id){
		/*$file=$this->api->db->dsql()->table('file')
		  ->where('id',$file_id)->field('filespace_id,filenum')->do_getHash();
		  $this->filespace_id=$file['filespace_id'];*/
		$filename=$this->api->db->dsql()->table('file b')
			->join('filespace c','c.id=b.filespace_id')
			->field("concat(c.dirname,'/',substring(lpad(hex(b.filenum),7,'0'),1,3),'/',substring(lpad(hex(b.filenum),7,'0'),4,2),'/',substring(lpad(hex(b.filenum),7,'0'),6,2)) filename")
			->where('b.id',$file_id)
			->do_getOne();
		return $filename;
		//$this->_real_filename($file_id,$file['filenum'],false);
	}
}
class FileNotFoundException extends FileException{}
