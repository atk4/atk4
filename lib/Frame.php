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
    function defaultTemplate(){
        return array('frames','MsgBox');
    }
}
