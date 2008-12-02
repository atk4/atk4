<?php
// This class is experemental!
//
//
class Button extends AbstractView {
    public $onclick=null;
    protected $style=array();
    protected $class=null;	// CSS class value
    function defaultTemplate(){
        return array('button','button');
    }
    function setLabel($label){
        $this->template->set('value',$label);
        return $this;
    }
    function onClick(){
        return $this->onclick=$this->ajax();
    }
    function setColor($color){
    	$this->setStyle('color',$color);
    	return $this;
    }
    function setStyle($key,$value){
    	$this->style[]="$key: $value";
    	return $this;
    }
    function setClass($class){
    	$this->class=$class;
    	return $this;
    }
    function render(){
        $this->template->set('name',$this->name);
        $class=is_null($this->class)?'':'class="'.$this->class.'" ';
        $this->template->set('style',$class.((count($this->style)?'style="'.implode(';',$this->style).'"':'')));
        if($this->onclick)$this->template->set('onclick',$this->onclick->getString());
        return parent::render();
    }
}
