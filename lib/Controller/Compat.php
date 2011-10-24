<?php
/* Adds more compatibility to application 

   $this->api->add('Controller_Compat_Frame');

 */

if(class_exists('TMail',false))die("Include Controller_Compat before you use TMail");
class TMail extends TMail_Compat{};

class Controller_Compat extends AbstractController {
    function init(){
        parent::init();
        $this->api->addGlobalMethod('frame',array($this,'frame'));
        $this->api->addGlobalMethod('ajax',array($this,'ajax'));
        $this->api->addHook('compat-addModelSave',array($this,'addModelSave'));
    }
    function addModelSave($obj,$other=null){
        $obj->addSubmit('Save');
    }
	function frame($obj,$spot=null,$title=null,$p=null,$opt=''){
		/*
		 * This function is just a shortcut in creating a frame
		 */
		if(!$p)$p=$obj;
		if(!isset($title)){
			$title=$spot;
			$spot='Content';
		}
		$f=$p->add('View','frame_'.(++$this->frame),$spot,array('frames','MsgBox'));
		$f->template->set('title',$title);
		$f->template->trySet('opt',$opt);
		return $f;
	}
    function ajax($obj,$instance=null){
        if(!is_null($instance)&&isset($obj->js['never'][$instance]))return $obj->js['never'][$instance];
        return $obj->js(null,null,$instance)->univ();
    }
}
