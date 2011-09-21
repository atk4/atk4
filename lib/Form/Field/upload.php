<?php
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



 * allowMultiple(boolean=true)
 *  this function will allow you to specify whether you want user to upload multiple files.
 * 
 * by default, single mode is used



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
	public $multiple=false;
	public $debug=false;

	function allowMultiple($multiple=50){
		// Allow no more than $multiple files to be present in the table
		$this->multiple=$multiple;
		return $this;
	}
	function setMode($mode){
		$this->mode=$mode;
		return $this;
	}
	function loadPOST(){
		parent::loadPOST();
		if($_GET[$this->name.'_upload_action']){
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
					$this->api->logger->logException($e);
					$this->uploadFailed($e->getMessage());
				}

				$this->uploadComplete($c->get());
			}
		}
		if($_POST[$this->name.'_token']){
			$a=explode(',',$_POST[$this->name.'_token']);$b=array();
			foreach($a as $val)if($val)$b[]=$val;
			$this->set(join(',',filter_var_array($b,FILTER_VALIDATE_INT)));
		}
		else $this->set($this->default_value);
	}
	function uploadComplete($data=null){
		echo "<html><head><script>window.top.$('#".
			$_GET[$this->name.'_upload_action']."').atk4_uploader('uploadComplete',".
			json_encode($data).");</script></head></html>";
		exit;
	}
	function uploadFailed($message){

		$d='';
		if($this->debug)$d=','.json_encode($_FILES[$this->name]);

		echo "<html><head><script>window.top.$('#".
			$_GET[$this->name.'_upload_action']."').atk4_uploader('uploadFailed',".
			json_encode($message).
			$d.
			");</script></head></html>";
		exit;
	}

	function init(){
        parent::init();
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

			$a=explode(',',$this->value);$b=array();
			foreach($a as $val)if($val)$b[]=$val;
			$files=join(',',filter_var_array($b,FILTER_VALIDATE_INT));
			$c->addCondition('id in',($files?$files:0));

			$data=$c->getRows(array('id','original_filename','filesize'));
			return $this->formatFiles($data);
		}
	}
	function formatFiles($data){
		$this->js(true)->atk4_uploader('addFiles',$data);
		$o = $this->add('SMLite')->loadTemplate("view/uploaded_files")->render();

		/*
		   foreach($data as $row){
		   $o.='<tr><td>'.$row['original_filename'].
		   '</td><td><a href="javascript:$(this).univ().ajaxec('.
		   addslashes($this->api->getDestinationURL(null,)).')">del</a></tr>';
		   }
		 */
		return $o;
	}


	function getInput(){
		if($id=$_GET[$this->name.'_delete_action']){
			// this won't be called in post unfortunatelly, because ajaxec does not send POST data
			// This is JavaScript upload. We do not want to trigger form submission event
			if($c=$this->getController()){
				$c->loadData($id);
				$c->delete();
				$this->js()->_selector('[name='.$this->name.']')->atk4_uploader('removeFiles',array($id))->execute();
				//$this->js(true,$this->js()->_selector('#'.$this->name.'_token')->val(''))->_selectorRegion()->closest('tr')->remove()->execute();
			}
		}

		if($id=$_GET[$this->name.'_save_action']){
			// this won't be called in post unfortunatelly, because ajaxec does not send POST data
			// This is JavaScript upload. We do not want to trigger form submission event
			if($c=$this->getController()){
				$c->loadData($id);
				$f=$c;
				$mime = $f->getRef('filestore_type_id')->get('mime_type');
				$path = $f->getPath();
				$name = $f->get("original_filename");
				$len = $f->get("filesize");
				header("Content-type: $mime");
				header("Content-legnth: $len");
				if($_GET["redirect"]){
					/* it should be possible to use redirect method as well */
					header("HTTP/1.1 301 Moved Permanently"); 
					header("Location: $path");
				} else {
					if(!$_GET['view']){
						header("Content-disposition: attachment; filename=\"$name\"");
					}
					print(file_get_contents($path));
				}
				exit;

				$this->js()->_selector('[name='.$this->name.']')->atk4_uploader('removeFiles',array($id))->execute();
				//$this->js(true,$this->js()->_selector('#'.$this->name.'_token')->val(''))->_selectorRegion()->closest('tr')->remove()->execute();
			}
		}
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
		if($this->multiple){
			$options['multiple']=$this->multiple;
		}
		if($this->mode!='simple'){
			$options['form']=$this->owner;
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
		$o.=
			$this->getTag('input',array(
						'type'=>'hidden',
						'name'=>$this->name.'_token',
						'value'=>$this->value,
						'id'=>$this->name.'_token',
						));


		$o.=parent::getInput();
		return $o;
	}
	function isUploaded(){
		return isset($_FILES[$this->name]) && $_FILES[$this->name]['name'];
	}
	function getOriginalName(){
		return $_FILES[$this->name]['name'];
	}
	function getOriginalType(){
		// detect filetype instead of relying on uploaded type
		if(function_exists('mime_content_type'))return mime_content_type($this->getFilePath());
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
	function debug(){
		$this->debug=true;
		return $this;
	}
}
