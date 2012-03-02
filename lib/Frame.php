<?php
class Frame extends View {
    function setTitle($title){
        $this->template->trySet('title',$title);
        return $this;
    }
    function opt($opt){
        $this->template->trySet('opt',$opt);
        return $this;
    }
    function render(){
        if(!$this->template->get('title')){
            $this->template->tryDel('title_tag');
        }
        return parent::render();
    }
    function defaultTemplate(){
        return array('frames','MsgBox');
    }
}
