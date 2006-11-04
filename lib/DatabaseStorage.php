<?php
/*
 * Created on 27.10.2006 by *Camper* (camper@adevel.com)
 */

class DatabaseStorage extends AbstractStorage{
	private $files;
	private $filespaces;
	private $filetypes;
	private $deleted_files;
	
	private $filespace_id;
		
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
		$file_list=$this->files->table('file file')->field('*')->calc_found_rows()->do_getAllHash();
		return $file_list;
	}
	function getFiles(){
		return $this->files;
	}
	function getFilespaces(){
		return $this->filespaces;
	}
	function getFilespacePath(){
		return $this->getFilespaces()->where('id',$this->getFilespaceId())->field('dirname')->do_getOne();
	}
    function getFilespaceId($filesize=0) {
    	if($filesize==0)return $this->filespace_id;
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

	function getFileSize($file_id){
		return $this->files->clear_args('where')->where('id',$file_id)->field('filesize')->do_getOne();
	}
	function getFilenum($file_id){
		return $this->files->clear_args('where')->clear_args('fields')->where('id',$file_id)->field('filenum')->do_getOne();
	}
	function deleteFile($file_id){
		$filenum=$this->getFilenum($file_id);
		$this->filespace_id=$this->files->clear_args('fields')->where('id',$file_id)->field('filespace_id')->do_getOne();
    	if ($res = @unlink($this->_real_filename($file_id,$filenum))) {
	    	// update filespace record (decrease used space)
	    	$this->filespaces
	    		->set('used_space = used_space - '.$this->getFilesize($file_id))
	    		->set('stored_files_cnt = stored_files_cnt - 1')
	    		->where('id',$this->getFilespaceId())
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
	    		->set('filespace_id',$this->getFilespaceId())
	    		->set('filenum',$filenum)
	    		->do_insert();
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
        header("Content-type: ".$file['mime_type']);
        header('Content-Disposition: attachment; filename="'.$file['filename'].'"');
        echo file_get_contents($this->_real_filename($file['id'],$file['filenum']));
	}
	function uploadFile($files_array, $saveas_name=''){
		//assigning vars
		$this->upload_orig_name=$files_array['name'];
		$this->upload_temp_name=$files_array['tmp_name'];
		$this->upload_save_name=$saveas_name==''?$this->upload_orig_name:$saveas_name;
		$this->upload_filetype=$files_array['type'];
		$this->upload_temp_name = $this->do_upload($this->upload_temp_name);
		if($this->upload_temp_name === false)
			throw new FileException("Error uploading file: ".$_FILES[$files_array]['error']);
		if($this->upload_filesize==0)throw new BaseException("Error uploading file.");
		if(!$this->getFilespaceId($this->upload_filesize))throw new FileException("Error getting filespace");
		//TODO hooks before saving
		$this->beforeSave();
		try{
			//locking
			if (($res_op = $this->api->db->getOne("/* hack */select get_lock('fs_fnum_lock',5)"))===false) 
				throw new FileException("Error getting record (wonder why?!)");
			if (empty($res_op)) throw new FileException('Error getting lock for filenumber calculation.');
			//trying to get number from the deleted list
			$filenum = $this->deleted_files->field('filenum')->where('filespace_id',$this->getFilespaceId())
				->do_getOne();
			//if successful - use deleted number...
			if (!empty($filenum)) {
				$this->deleted_files->where('filenum',$filenum)->where('filespace_id',$this->getFilespaceId())
					->do_delete();
			}else{
				//...else creating new one
				$filenum = $this->files->field('max(filenum)+1')->where('filespace_id',$this->getFilespaceId())
					->do_getOne();
				$filenum = (empty($filenum))?1:$filenum;
			}
			//inserting a record to files
	    	$this->files
	    		->set('filespace_id',$this->getFilespaceId())
	    		->set('filetype_id',$this->getFileType($this->upload_filetype))
	    		->set('filenum',$filenum)
	    		->set('filename',$this->upload_save_name)
	    		->set('filesize',$this->upload_filesize)
	    		->do_insert(); 
	
	    	$this->_filenum = $filenum;
			$_id = $this->api->db->lastId();  
			// release lock
			if (($res_op = $this->api->db->getOne("/* hack */select release_lock('fs_fnum_lock')"))===false) 
			           throw new FileException("Error releasing lock");
			
			// moving file to its actual destination
			if(!@rename($this->upload_temp_name,$this->_real_filename($_id,$filenum))) {
				$this->files->where('id',$_id)->do_delete();
				$_id = null;
				throw new FileException ('Error moving file '.$this->upload_orig_name.' into filespace ('.
					$this->_real_filename($_id,$filenum).')!'); 
			}else{
				// increase used space
		    	if ($this->filespaces->where('id',$this->getFilespaceId())
		    		->set('used_space = used_space + '.$this->upload_filesize)
		    		->set('stored_files_cnt = stored_files_cnt + 1')
		    		->do_update()===false) throw new FileException("Error updating filespace");
			}
		}catch (FileException $e){
			//releasing locks
			if (!($this->api->db->getOne("/* hack */select IS_FREE_LOCK('fs_fnum_lock')")))
				$this->api->db->getOne("/* hack */select release_lock('fs_fnum_lock')");
			throw $e;
		}
		//TODO hooks after upload
		return $_id;
	}
	function getFileType($filetype){
		$id=$this->filetypes->field('id')->where('mime_type',$filetype)->do_getOne();
		if (empty($id)) {
			$this->filetypes->set('mime_type',$filetype)->do_insert(); 
			$id = $this->api->db->lastId();
		}
		return $id;
	}
	//********** FileHandler routines ***********
    /**
     *  return filename in filespace, create directories if necessary
     */
    private function _real_filename($file_id,$filenum='') {
    	if($filenum=='')$filenum=$this->getFilenum($file_id);
    	$path = str_pad(substr(strtoupper(dechex($filenum)),-7,7), 7, '0', STR_PAD_LEFT);
    	$res = $this->getFilespacePath().DIRECTORY_SEPARATOR.substr($path,0,3);
    	
    	if (!file_exists($res)) 
    		if (!@mkdir($res)) 
    			throw new FileException('Error create directory '.$res);
    		
		$res .= DIRECTORY_SEPARATOR.substr($path,3,2);
    	if (!file_exists($res)) 
    		if (!@mkdir($res)) 
    			throw new FileException('Error create directory '.$res);
		
		$res .= DIRECTORY_SEPARATOR.substr($path,5,2);
    	
    	return $res; 
    }
}