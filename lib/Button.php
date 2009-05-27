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
        list($label,$icon)=explode(',',$label);
        $this->template->set('value',$label);
        if($icon)$this->template->trySet('icon',$icon);
        return $this;
    }
    function onClick(){
        return $this->onclick=$this->ajax();
    }
    function setColor($color){
    	$this->setStyle('color',$color);
    	return $this;
    }
    function setStyle($key,$value=null){
    	if(is_null($value)&&is_array($key)){
    		foreach($key as $k=>$v)$this->setStyle($k,$v);
    		return $this;
    	}
    	$this->style[]="$key: $value";
    	return $this;
    }
    function setClass($class){
    	$this->class=$class;
    	return $this;
    }
    function render(){
        $this->template->set('name',$this->name);
        if(!is_null($this->class))$this->template->trySet('class',$this->class);
        $this->template->set('style',((count($this->style)?'style="'.implode(';',$this->style).'"':'')));
        if($this->onclick)$this->template->set('onclick',$this->onclick->getString());
        return parent::render();
    }
}
