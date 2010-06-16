<?php
class View_Button extends View_HtmlElement {
	private $icon=null;
	private $link=null;
	function defaultTemplate(){
		return array('button','button');
	}
	function setIcon($icon){
		$this->icon=$icon;
		$this->template->set('icon',$icon);
	}
	function setLabel($label){
		list($label,$icon)=explode(',',$label);
		$this->setText($label);

		if($icon)$this->setIcon($icon);

		return $this;
	}
	function setButtonStyle($n){
		$this->template->set('button_style',$n);
		return $this;
	}
	function setStyle($key,$value=null){
		return $this->addStyle($key,$value);
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
		$this->js(true)->button();
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
		return $this->js('click')->univ();
	}
	function setAction($js=null,$page=null){
		if(strpos($page,'.')===false && strpos($page,':')===false && $page){
			$page=$this->api->getDestinationUrl($page);
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
}
