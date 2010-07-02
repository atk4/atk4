<?
/**
  * This class is for manipulating file uploads. Apart from doing basic file uploads, 
  * this class supports validation, image resizes etc
  */


class Form_Field_Upload extends Form_Field {
	public $max_file_size=null;


	function init(){
		$this->owner->template->set('enctype', "enctype=\"multipart/form-data\"");
		$this->attr['type']='file';

		$max_post=$this->convertToBytes(ini_get('post_max_size'))/2;
		$max_upload=$this->convertToBytes(ini_get('upload_max_filesize'));

		$this->max_file_size=$max_upload<$max_post?$max_upload:$max_post;

		$this->js(true)->_load('ui.atk4_uploader')->atk4_uploader(array(
					'size_limit'=>$this->max_file_size
					)
				);

		if($_POST[$this->name.'_token']){
			$t=json_decode(stripslashes($_POST[$this->name.'_token']));
			$_FILES[$this->name]=array(
					'name'=>$t->fileInfo->name,
					'tmp_name'=>'upload/temp/'.$t->filename,
					);
		}
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


	function getInput(){
		if($this->max_file_size){
			$o='';
			if($this->owner->getController() && $this->owner->getController()->isInstanceLoaded()){
				$o=
					'<a href="/image/full.html'.($x=('?table='.
					$this->owner->getController()->getImageEntity().'&field='.
					$this->short_name.'&id='.
					$this->owner->getController()->get(
							$this->owner->getController()->getImageEntity()==
							$this->owner->getController()->getEntityCode()?'id':
							$this->owner->getController()->getImageEntity().'_id').'&fresh='.time())).
					'" target="_blank"><img border="0" src="/image/thumb.html'.
					$x.
					'"/>'.
					'</a><br/>';
			}
			$o.=
				$this->getTag('input',array(
						'type'=>'hidden',
						'name'=>'MAX_FILE_SIZE',
						'value'=>$this->max_file_size
						));
		}

		return $o.=parent::getInput();
	}
	function isUploaded(){
		return isset($_FILES[$this->name]);
	}
	function getOriginalName(){
		return $_FILES[$this->name]['name'];
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
