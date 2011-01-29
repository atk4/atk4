<?php
class View_Icon extends View_HtmlElement {
	public $color=null;
	function init(){
		parent::init();
		$this->setElement('i');
		parent::set('');
		$this->setColor($this->api->getConfig('icon/default-color','orange'));
	}
	function set($shape){
		$this->shape=$shape;
		return $this;
	}
	function setColor($color){
		$this->color=$color;
		return $this;
	}
	function render(){
		$this->addClass('atk-icon');
		$this->addClass('atk-icons-'.$this->color);
		$this->addClass('atk-icon-'.$this->shape);

		parent::render();
	}

}
