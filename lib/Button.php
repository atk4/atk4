<?php
// This class is experemental!
//
//
class Button extends AbstractView {
    public $onclick=null;
    function defaultTemplate(){
        return array('button','button');
    }
    function setLabel($label){
        $this->template->set('value',$label);
        return $this;
    }
    function onClick(){
        return $this->onclick=$this->add('Ajax');
    }
    function setColor($color){
    	$this->style[]='color: '.$color;
    	return $this;
    }
    function render(){
        $this->template->set('name',$this->name);
        $this->template->set('style',((count($this->style)?'style="'.implode(';',$this->style).'"':'')));
        if($this->onclick)$this->template->set('onclick',$this->onclick->getString());
        return parent::render();
    }
}
