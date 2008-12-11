<?php
/**
 * AJAX class implemented using jQuery (http://jquery.com/)
 *
 * Created on 26.11.2008 by *Camper* (cmd@adevel.com)
 */
class AjaxJ extends AbstractAjax{
	function init(){
		parent::init();
		if($this->api->template->is_set('ajax_scripts'))$this->api->template->trySet('ajax_scripts',
			'<script src="amodules3/templates/js/jquery.js"></script>' .
			'<script src="amodules3/templates/js/jquery.form.js"></script>' .
			'<script src="amodules3/templates/js/jam3.js"></script>'
		);
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
		$this->ajaxFunc("submitForm('".$form->name."','".$this->spinner."')");
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
}