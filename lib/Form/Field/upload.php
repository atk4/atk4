<?
/**
  *
  * This class is for uploading files. It supports 3 types of upload:
  * - regular upload. No javascript is involved. When form is being submitted, data is collected
  * - javascript iframe upload. Upon selection of the file, it will start uploading it right away,
  *    and will display additional file upload field, so that you can pick another file.
  * - flash upload. Upload will be carried through by flash.

  * Mode can be set by
  *  setMode('plain')  - plain mode is not available with AJAX form
  *  setMode('iframe')
  *  setMode('flash')
  *  
  * by Default mode is iframe.



  * allowMultiple(boolean)
  *  this function will allow you to specify whether you want user to upload multiple files.
  * 
  * by default, multiple file upload is allowed.



  * Controller Support.
  *  You can use setController($c); where $c should be Class_Filestore_File. You can set necessary
  *  option for the file through controller, but overal once upload process is complete, file will
  *  be saved properly. Field will contain list of "id" for uploaded files.

  * If you are not using controllers, then $field->isUploaded() will return true every time file
  *  was uploaded. Note that without controller this field will no longer move any files, so it's
  *  up to you to perform the necessary moves. Also - you should perform this check outside form's
  *  isSubmitted().
  * 
  *  It is now your job to call $field->setReference(); once you have moved file into proper location.
  *
  * Example1: Simple use with controler

   $upl=$form->addField('upload','myfile')
     ->setController('Controller_Filestore_File')

  * Example2: Custom mode with controller

   $upl=$form->addField('upload','photo','Photo')
     ->setController('Controller_Filestore_Image')
	 ->setMode('flash')
	 ->allowMultiple(false)
	 ;
   $upl->template->set('after_field','Max size: 500k');


   * Example3: Use without controllers

   $upl=$form->addField('upload','photo','Photo')
	 ->setMode('plain')
	 ->allowMultiple(false)
	 ;
   if($upl->isUploaded()){
     $n=sanitize($upl->
   }
     
  

  */


class Form_Field_Upload extends Form_Field {
	public $max_file_size=null;
	public $mode='iframe';


	function setMode($mode){
		$this->mode=$mode;
		return $this;
	}
	function loadPOST(){
		parent::loadPOST();
		if($this->n=$_GET[$this->name.'_upload_action']){
			// This is JavaScript upload. We do not want to trigger form submission event
			$_POST=array();
		}

		if($_GET[$this->name.'_upload_action'] || $this->isUploaded()){
			if($c=$this->getController()){

				try{
					$c->set('filestore_volume_id',1);
					$c->set('original_filename',$this->getOriginalName());
					$c->set('filestore_type_id',$c->getFiletypeID($this->getOriginalType()));
					$c->import($this->getFilePath());
					$c->update();
				}catch(Exception $e){
					$this->uploadFailed($e->getMessage());
				}

				$this->uploadComplete($c->get());
			}
		}
	}
	function uploadComplete($data=null){
		echo "<html><head><script>window.top.$('#".
			$_GET[$this->name.'_upload_action']."').atk4_uploader('uploadComplete',".
			json_encode($data).");</script></head></html>";
		exit;
	}
	function uploadFailed($message){
		echo "<html><head><script>window.top.$('#".
			$_GET[$this->name.'_upload_action']."').atk4_uploader('uploadFailed',".
			json_encode($message).");</script></head></html>";
		exit;
	}

	function init(){


		$this->owner->template->set('enctype', "enctype=\"multipart/form-data\"");
		$this->attr['type']='file';

		$max_post=$this->convertToBytes(ini_get('post_max_size'))/2;
		$max_upload=$this->convertToBytes(ini_get('upload_max_filesize'));

		$this->max_file_size=$max_upload<$max_post?$max_upload:$max_post;

		/*
				*/

		/*
		if($_POST[$this->name.'_token']){
			$t=json_decode(stripslashes($_POST[$this->name.'_token']));
			$_FILES[$this->name]=array(
					'name'=>$t->fileInfo->name,
					'tmp_name'=>'upload/temp/'.$t->filename,
					);
		}
		*/
	}
	function convertToBytes($val){
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		switch($last) {
			case 'g':
				$size = $val * 1024 * 1024 * 1024;
				break;
			case 'm':
				$size = $val * 1024 * 1024;
				break;
			case 'k':
				$size = $val * 1024;
				break;
			default:
				$size = (int) $val;
		}
		return $size;
		
	}

	// TODO:
	// add functions for file type and extension validation
	// those can be done in flash thingie as well

	function getUploadedFiles(){
		if($c=$this->getController()){
			$data=$c->getRows(array('id','original_filename','filesize'));
			return $this->formatFiles($data);
		}
	}
	function formatFiles($data){
		$o='<table border=1>';
		foreach($data as $row){
			$o.='<tr><td>'.$row['original_filename'].'</td><td><a href="javascript:$(this).univ().alert(\'delete\')">del</a></tr>';
		}
		$o.='</table>';
		return $o;
	}


	function getInput(){
		if($_GET[$this->name.'_upload_action'])$this->uploadComplete();
		$o='';

		$options=array('size_limit'=>$this->max_file_size);

		switch($this->mode){
			case'simple':break;
			case'iframe':
						 $options['iframe']=$this->name.'_iframe';
						 break;
			case'flash':
						 $options['flash']=true;
						 break;

		}
		if($this->mode!='simple'){
			$this->js(true)->_load('ui.atk4_uploader')->atk4_uploader($options);
		}

		// First - output list of files wi already have uploaded
		$o.='<div id="'.$this->name.'_files" class="uploaded_files">'.
			$this->getUploadedFiles().
			'</div>';

		$o.=
			$this->getTag('input',array(
						'type'=>'hidden',
						'name'=>'MAX_FILE_SIZE',
						'value'=>$this->max_file_size
						));


		$o.=parent::getInput();
		return $o;
	}
	function isUploaded(){
		return isset($_FILES[$this->name]);
	}
	function getOriginalName(){
		return $_FILES[$this->name]['name'];
	}
	function getOriginalType(){
		return $_FILES[$this->name]['type'];
	}
	function getFilePath(){
		return $_FILES[$this->name]['tmp_name'];
	}
	function getFile(){
		return file_get_contents($this->getFilePath());
	}
	function getFileSize(){
		return filesize($this->getFilePath());
	}

	function saveInto($directory){
		// Moves file into a directory.
		// TODO: we should make sure we are not overwriting anything here
		if(!move_uploaded_file($_FILES[$this->name],$directory)){
			throw new BaseException('Unable to save uploaded file into '.$directory);
		}
	}
	function displayUploadInfo(){
		$this->owner->info('Was file uploaded: '.($this->isUploaded()?'Yes':'No'));
		$this->owner->info('Filename: '.$this->getFilePath());
		$this->owner->info('Size: '.$this->getFileSize());
		$this->owner->info('Original Name: '.$this->getOriginalName());
	}
}
