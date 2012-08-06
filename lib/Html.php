<?php
class Html extends AbstractView {
    public $html='Your text goes <u>here</u>......';
    /* Set text */
    function set($html){
        $this->html=$html;
        return $this;
    }
    function render(){
        $this->output($this->html);
    }
    function initializeTemplate(){
        $this->spot=$this->defaultSpot();
    }
}
