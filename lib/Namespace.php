<?php
OBSOLETE!!
class Namespace extends AbstractView {
	function init(){
		parent::init();
		$this->subdir=$this->short_name;
		$this->api->addHook('init-namespaces',array($this,'initNamespace'));
		$this->api->namespaces[$this->short_name]=$this;

		$p=ini_get('include_path');
		ini_set('include_path',"lib/".$this->subdir.
				PATH_SEPARATOR.AMODULES3_LIB.'/'.$this->subdir.
				PATH_SEPARATOR.$p);
	}
	function initNamespace(){
		/*
		 * That's where you should initialize your namespace. At this point most of the other
		 * initialization tasks are complete
		 */
	}
	function defaultTemplate(){
		return array('empty','_top');
	}
	function initLayout(){
		if(method_exists($this,$pagefunc='page_'.$this->api->page)){
			$p=$this->add('Page',$this->api->page,'Content');
			$this->$pagefunc($p);
		}else{
			$this->add('page_'.$this->api->page,$this->api->page,'Content');
		}
	}
	function render(){
		if($this->api->ns!=$this)return;
		if(!($this->template)){
			throw new BaseException("You should specify template for API object");
		}
		echo $this->template->render();
	}
}
