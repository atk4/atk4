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
class View_Button extends View_HtmlElement {
	private $icon=null;
	private $link=null;
	function defaultTemplate(){
		return array('button','button');
	}
	function setIcon($icon){
		$this->icon=$icon;
		$this->template->set('icon',$icon);
		return $this;
	}
	function setLabel($label){
		return $this->setText($label);
	}
	function setButtonStyle($n){
		$this->template->set('button_style',$n);
		return $this;
	}
	function setStyle($key,$value=null){
		return parent::setStyle($key,$value);
		//$this->style[]="$key: $value";
		//return $this;
	}
	function setLink($link){
		//$this->addClass('atk4-link');
		return $this->setAttr('href',$link);
	}
	function setClass($class){
		$this->class=$class;
		return $this;
	}
	function jsButton(){
		if(!($this->owner instanceof ButtonSet))$this->js(true)->button();
	}
	function render(){
		$this->jsButton();

		if($this->icon){
			$this->addClass('ui-button-and-icon');
		}else{
			//$this->addClass('ui-button');
			$this->template->tryDel('icon_span');
		}

		return parent::render();
	}
	function onClick(){
        // Obsolete. Use js('click') directly
        $this->exception('Use generic form of js("click")->univ() instead of onClick()','Obsolete');
		return $this->js('click')->univ();
	}
    function isClicked($confirm=null){

        $cl=$this->js('click')->univ();
        if($confirm)$cl->confirm($confirm);

        $cl->ajaxec($this->api->getDestinationURL(null,array($this->name=>'clicked')));

        return isset($_GET[$this->name]);
    }
	function setAction($js=null,$page=null){
		if(strpos($page,'.')===false && strpos($page,':')===false && $page){
			$page=$this->api->getDestinationURL($page);
		}
		// Set no-js compatibility link
		if($page)$this->setLink($page);

		// If we have JS, it's a custom action. Otherwise we will load pagelink
		if($js){
			$this->js('click',$js);
		}elseif($page){
			$this->js('click')->univ()->redirectURL($page);
		}
		return $this;
	}
	function redirect($page){
		return $this->js('click')->univ()->redirect($this->api->getDestinationURL($page));
	}
	function submitForm($form){
		return $this->js('click',$form->js()->submit());
	}
}
