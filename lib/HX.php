<?php
class HX extends HtmlElement {
    public $text=null;
    public $sub=null;
    function set($text){
        $this->text=$text;
        return parent::set($text);
    }
    /** Adds subtitle */
    function sub($text){
        $this->sub=$text;
        return $this;
    }
    function recursiveRender(){
        if(!is_null($this->sub)){
            $this->add('Text')->set($this->text);
            $this->add('HtmlElement')->setElement('small')->set($this->sub);
        }
        parent::recursiveRender();
    }
}
