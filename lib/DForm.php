<?php
/**
 * Form with predefined template
 * Fields are insertend into their own tags which should be defined on the form
 * 
 * This form has no comments (they should be placed on template), separators or sections (form template
 * is free to design)
 * 
 * @author Camper (cmd@adevel.com) on 07.05.2009
 */
class DForm extends Form{
	
	protected function getChunks(){
		// commonly replaceable chunks
		$this->grabTemplateChunk('form_line');      // template for form line, must contain field_caption,field_input,field_error
		if($this->template->is_set('hidden_form_line'))
			$this->grabTemplateChunk('hidden_form_line');
		$this->grabTemplateChunk('field_error');    // template for error code, must contain field_error_str
		//$this->grabTemplateChunk('form');           // template for whole form, must contain form_body, form_buttons, form_action,
													//  and form_name
		$this->grabTemplateChunk('field_mandatory'); // template for marking mandatory fields

		// ok, other grabbing will be done by field themselves as you will add them to the form.
		// They will try to look into this template, and if you don't have apropriate templates
		// for them, they will use default ones.
		$this->template_chunks['form']=$this->template;
		$this->template_chunks['form']->del('form_body');
		$this->template_chunks['form']->del('form_buttons');
		$this->template_chunks['form']->set('form_name',$this->name);
		return $this;
	}
	/**
	 * Adds template from external file to form_body tag
	 * It is useful when you have one template with common tags and separate template with actual
	 * design and fields
	 */
	function addTemplate($template,$tag){
		$template=$this->add('SMlite')->loadTemplate($template);
		$this->template->tags['form_body'][]=$template->tags[$tag];
		return $this;
	}
	function addComment($comment){
		throw new BaseException('DForm does not support comments');
	}
	function addSeparator($p=null){
		throw new BaseException('DForm does not support separators');
	}
	function addField($type,$name,$caption=null,$attr=null){
		if($caption===null)$caption=$name;
		
		// fields inserted into self-named tags. if no tag exist - they added on form_body
		$spot=$this->template->is_set($name)?$name:'form_body';

		$this->last_field=$this->add('Form_Field_'.$type,$name,$spot,'form_line')
			->setCaption($caption);
		if (is_array($attr)){
			foreach ($attr as $key => $value){
				$this->last_field->setProperty($key, $value);
			}
		}

		$this->last_field->short_name = $name;

		return $this;
	}
	function defaultTemplate($template = null, $tag = null){
		if ($template){
			$this->form_template = $template;
		}
		if ($tag){
			$this->form_tag = $tag;
		}
		return array($this->form_template?$this->form_template:"dform", $this->form_tag?$this->form_tag:"form");
	}
}