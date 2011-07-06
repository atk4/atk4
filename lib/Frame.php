<?php
class Frame extends AbstractView {
    function title($title){
        $this->trySet('title',$title);
        return $this;
    }
    function opt($opt){
        $this->trySet('opt',$opt);
        return $this;
    }
    function defaultTemplate(){
        return array('frames','MsgBox');
    }
}
