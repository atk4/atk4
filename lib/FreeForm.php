<?php
/**
 * This form allows you to draw its template on your own
 * You can change template as you want, fields will be added from the template
 *
 * Form buttons are added in a generic way
 *
 * Created on 04.09.2008 by *Camper* (camper@adevel.com)
 */
class FreeForm extends Form{
	function init(){
		AbstractView::init();
		$this->template->set('form_name',$this->name);
		$this->template_chunks['form']=$this->template;
		$this->template_chunks['form']->del('form_buttons');
		$this->grabTemplateChunk('field_error');    // template for error code, must contain field_error_str
		$this->api->addHook('pre-exec',array($this,'loadData'));
		$this->getTemplateFields();
	}
	function getTemplateFields($tags=null){
		/*
		 * Fields are searched for through template tags
		 * tags starting with "field_" are collected as form fields
		 */
		if(is_null($tags)){
			$tags=$this->template->tags;
			// TODO: we should delete field data before collecting
		}
		foreach($tags as $name=>$tag){
			if(stripos($name,'field_')!==false){
				list($field,$number)=split('#',$name);
				if(is_null($this->getElement($field=substr($field,strlen('field_')),false)))$this->addField($field);
			}
			elseif(is_array($tag))$this->getTemplateFields($tag);
		}
	}
	function addField($name){
		// tag name might contain a number. cutting it
		list($name,$number)=split('#',$name);
		$this->last_field=$this->add("Form_Field_Free",$name,"field_$name","field_$name");
		$this->last_field->short_name=$name;
		return $this;
	}
	function validateNotNULL($field,$msg=''){
		$this->getElement($field)->addHook('validate','if(!$this->get())$this->displayFieldError("'.
			($msg?$msg:'".$this->short_name." is a mandatory field!').'");');
		return $this;
	}
	function validateField($field,$condition,$msg=''){
		$this->getElement($field)->addHook('validate','if(!'.$condition.')$this->displayFieldError("'.
			($msg?$msg:'Error in ".$this->short_name."').'");');
		return $this;
	}
	function defaultTemplate(){
		return array('freeform','_top');
	}
}
