<?php
class Text extends AbstractView {
    public $text='Your text goes here......';
    function set($text){
        $this->text=$text;
        return $this;
    }
    function render(){
        $this->output($this->text);
    }
}
