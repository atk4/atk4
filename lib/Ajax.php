<?php
/**
 * AJAX class implemented using jQuery (http://jquery.com/)
 *
 * Created on 26.11.2008 by *Camper* (cmd@adevel.com)
 */
class Ajax extends AbstractAjax{
	function init(){
		parent::init();
	}
	function checkSession(){
		return $this->ajaxFunc("checkSession('".$this->api->getDestinationURL(null)."')");
	}
	function useProgressIndicator($id,$timeout=null){
		$this->spinner=$id;
		$this->ajaxFunc("spinner_on('$id'".(is_null($timeout)?'':",$timeout").")");
		return $this;
	}
	function setInnerHTML($field_id,$value){
		$value=str_replace("'",'\'',$value);	// FIX: single quote was replaced by empty string
												// wonder why?
		$value=str_replace("\r\n",'\n',$value);  // this is for templates that saved with
												// Windows line delimiters -- mvs
		$value=str_replace("\n",'\\n',$value);

		return $this->ajaxFunc("$('#$field_id').html('$value')");
	}
	function loadRegionURL($region_id,$url){
		/*return $this->ajaxFunc("$.get('$url',function(result){".
			"$('#$region_id').html(result);".
		"})");*/
		return $this->ajaxFunc("$('#$region_id').load('$url')");
	}
	function executeUrl($url){
		return $this->ajaxFunc("$.get('$url')");
	}
	function submitForm($form){
		$this->ajaxFunc("submitForm('".$form->name."','".$this->owner->name."','".$this->spinner."')");
		// spinner will be turned off by JS
		$this->spinner=null;
		return $this;
	}
	function disableControl($control){
		return $this->ajaxFunc("$('#{$control->name}').attr('disabled',true)");
	}
	function reloadGridRow($grid,$row_id,$url=null,$args=array()){
		if(is_object($grid))$grid_name=$grid->name;
		else $grid_name=$grid;
		return $this->ajaxFunc('reloadGridRow(\''.
			$this->api->getDestinationURL($url,array_merge(
				array('cut_object'=>$url,'grid_action'=>'return_row','datatype'=>'jquery','expanded'=>$grid_name,
				'id'=>$row_id),
				$args)).'\',\''.$grid_name.'\','.$row_id.')');
	}
	/**
	 * Puts form field value into a JS variable
	 * @param $form - Form instance
	 * @param $fild_name - Field's  short name
	 * @param $var - optional, name of the variable to use for field value
	 */
	function getFormFieldValue($form,$field_name,$var='fv'){
		$field_name=$form->getElement($field_name)->name;
		return $this->ajaxFunc("$var=$('#$form->name input[name=$field_name]').val()");
	}
	/**
	 * Sets the 'disabled' attribute to true
	 * @param $control - object instance
	 */
	function disableControl($control){
		return $this->ajaxFunc("$('#$control->name')->attr('disabled',true)");
	}
	/**
	 * Loads field value from the specified URL
	 * @param $field - Field instance
	 * @param $url - URL to get value from. Calling the URL should return the text
	 */
	function loadFieldValue($field,$url){
		return $this->ajaxFunc("loadFieldValue('$field->name','$url')");
	}
}