<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
class Reloadable extends AbstractController {
	/*
	 * When you add Reloadable to your object, it will become
	 * capable of reloading by ajax instruction. The rendering of
	 * the field is going to change - additional surrounding div
	 * will be placed arround and the element will know how to
	 * properly render itself
	 */

	// Holds template for loading progress meter <
	protected $loading_template = null;

	function init(){
		parent::init();
		$this->owner->reloadable=$this;
		if($this->owner instanceof Form_Field){
			// If we are reloading field, everything is much different. Rendering of an object
			// is nothing regular, it's a <tr> element. So we need to be a bit smarter about
			// placing our <div>
			if(!$this->isCut()){
				$this->owner->template->append('field_input_pre','<div id="RR_'.$this->owner->name.'">');
				$this->owner->template->append('field_input_pre','<div id="RD_'.$this->owner->name.'" style="display: none; position:absolute; width:200;font-weight: bold; background: white"><table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img alt="" src="amodules3/img/loading.gif"></td><td>&nbsp;</td><td class="smalltext" align=center><b>Loading. Stand by...</b></td></tr></table></div><!-- RD close -->');
				$this->owner->template->append('field_input_post','</div><!-- RR close -->');
				return;
			}
		}
		$this->owner->addHook('pre-recursive-render',array($this,'preRecursiveRender'));
		$this->owner->addHook('post-recursive-render',array($this,'postRecursiveRender'));

		// Get config from api for default loading template <
		try {
			$this->loading_template = $this->api->getConfig('reloadable/loading_template');
		} catch (ExceptionNotConfigured $e) {
			$this->loading_template = null;
		}
	}

	public function setLoadingTemplate($template) {
		$this->loading_template = $template;
	}

	function isCut(){
		return(isset($_GET['cut_object']) && ($_GET['cut_object']==$this->owner->name || $_GET['cut_object']==$this->owner->short_name));
	}
	function renderLoadingDiv(){

		// Add template engine <
		$tmp = $this->add('SMLite');

		// If no template found, render default <
		if (empty($this->loading_template) || ($tmp->findTemplate($this->loading_template) == null)) {
			$this->owner->output('<div id="RD_'.$this->owner->name.'" style="display: none; position:absolute; width:200;font-weight: bold; background: white"><table cellspacing=0 cellpadding=0 border=0><tr><td valign=top><img alt="" src="amodules3/img/loading.gif"></td><td>&nbsp;</td><td class="smalltext" align=center><b>Loading. Stand by...</b></td></tr></table></div>');

		// Else render template <
		} else {
			$this->owner->output($tmp->loadTemplate($this->loading_template)
									 ->trySet('name', $this->owner->name)
									 ->render());
		}
	}

	function preRecursiveRender(){
		/*
		 * If cut_object is present, then we are currently reloading
		 * this object only and NOT surrounding div.
		 */
		if($this->isCut()){
			$this->renderLoadingDiv();
			if($this->owner instanceof Form_Field){
				// cut the template crap
				$this->owner->template->loadTemplateFromString('<?$field_input?>');
			}
		}else{
			$this->owner->output('<div id="RR_'.$this->owner->name.'">');
			$this->renderLoadingDiv();
		}
	}
	function postRecursiveRender(){
		if(!$this->isCut()){
			$this->owner->output('</div>');
		}
	}
}
