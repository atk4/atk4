<?php
/*
* This object enables many advanced actions only possible with using AJAX technology.
*
* You would need a compliant browser too.
*
* In order to use this module do $ajax = $this->add('Ajax');
*/

class Ajax extends AbstractAjax {

	function init(){
		parent::init();
		// adding requred js includes
		if($this->api->template->is_set('ajax_scripts'))$this->api->template->append('ajax_scripts',
			'<script src="amodules3/templates/js/ajax.js"></script>' .
			'<script src="amodules3/templates/js/amodules3.js"></script>'
		);
	}

	function getFormFieldValue($form,$field_name,$var='fv'){
		return $this->ajaxFunc("$var=aagv('$form->name','".$form->getElement($field_name)->name."')");
	}

	function loadRegionURL($region_id,$url){
		$this->ajaxFunc("aasn('$region_id','$url')");
		return $this;
	}

	function executeURL($url){
		$this->ajaxFunc("aacu('$url')");
		return $this;
	}

	function reloadRegion($region_id,$args=array()){
		$args=array_merge(array('cut_region'=>$region_id),$args);
		$url=$this->api->getDestinationURL(null,$args);
		return $this->loadRegionURL($region_id,$url);
	}
	function reload($element,$args=array(),$page=null){
		if(is_null($element)||$element instanceof DummyObject)return $this;
		if(!isset($element->reloadable) && is_object($element)){
			$element->add('Reloadable');
		}
		if(is_object($element)){
			$element=$element->name;
		}
		$args['cut_object']=$element;
		$url=$this->api->getDestinationURL($page,$args);
		$this->setVisibility("RD_".$element);
		$this->loadRegionURL("RR_".$element,$url);
		return $this;
	}
	function loadFieldValue($field_id,$url){
		$this->ajaxFunc("aasv('$field_id','$url')");
		return $this;
	}
	// urlwrap_admin_Aliases_grid_delete_frame_1_form
	function setFieldValue($form,$field,$value){
		$this->ajaxFunc("setFieldValue('".$form->name."','$field','$value')");
		return $this;
	}
	function setInnerHTML($field_id,$value){
		$value=str_replace("'",'\'',$value);	// FIX: single quote was replaced by empty string
												// wonder why?
		$value=str_replace("\r\n",'\n',$value);  // this is for templates that saved with
												// Windows line delimiters -- mvs
		$value=str_replace("\n",'\\n',$value);

		return $this->ajaxFunc("aafc('$field_id','$value')");
	}
	function reloadExpandedField($url,$args=array()){
		$this->memorizeExpander();
		return $this->loadRegionURL($_GET['expanded'].'_'.$_GET['expander'].'_'.$_GET['id'],
			$this->api->getDestinationURL($url,array_merge(
				array('cut_object'=>$url,'grid_action'=>'return_field','expanded'=>$_GET['expanded'],
				'expander'=>$_GET['expander'],'id'=>$_GET['id']),
				$args)));
	}

	function reloadField($fld){
		$this->notImplemented("reloadField");
	}
	function submitForm($form){
		// buttonClicked var used later to define the button that was clicked
		$this
			->ajaxFunc("buttonClicked='".$this->owner->name.
				"'; submitForm('$form->name','".$this->spinner."')");
		$this->spinner=null;
		return $this;
	}
	/**
	* Uploads a file using IFrame. See AjaxFileUploader class description for details
	*/
	function uploadFile($form,$field_name){
		$this->api->add('AjaxFileUploader','uploader');
		$this->ajaxFunc("submitUpload('$form->name','".
			$this->api->getDestinationURL(null,array('file_upload'=>$field_name)).
			"','$this->spinner')");
		$this->spinner=null;
		return $this;
	}
	function resetForm($form){
		$this->ajaxFunc("resetForm('$form->name')");
		return $this;
	}
	function setVisibility($element,$visible=true){
		if(!is_string($element))$element=$element->name;
		$this->ajaxFunc("setVisibility('".$element."',".($visible?"true":"false").")");
		return $this;
	}
	function setFrameVisibility($frame,$visibility=true){
		$this->ajaxFunc("setFloatingFrame('{$frame->name}', " . ($visibility ? 'true' : 'false') . ")");
		$this->setVisibility($frame->name."_bg",$visibility);
		$this->setVisibility($frame->name."_fr",$visibility);
		return $this;
	}


	function useProgressIndicator($id,$timeout=null){
		$this->spinner=$id;
		$this->ajaxFunc("spinner_on('$id'".(is_null($timeout)?'':",$timeout").")");
		return $this;
	}
	function disableControl($control){
		$this->ajaxFunc("document.getElementById('$control->name').disabled=true");
		return $this;
	}
}
