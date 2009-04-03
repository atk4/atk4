<?php
/**
 * Represents a Floating frame with ability to load content on frame display
 *
 * Created on 24.09.2008 by *Camper* (cmd@adevel.com)
 */
class ReloadableFrame extends FloatingFrame{
	protected $object=null;
	protected $frame=null;

	function init(){
		parent::init();
		//$this->api->addHook('post-submit',array($this,'loadObject'));
		//$this->api->memorize('ajax_frame_hide',$this->hide()->getString());
	}
	function loadObject(){
		if(isset($_GET['reload_frame'])&&$_GET['reload_frame']==$this->name){
			// the object required is not yet created
			$container=is_null($this->frame)?$this:$this->frame;
			$this->object=$container->add($this->object);
			// now rendering the object and exiting
		}
	}
	function frame($spot,$title=null,$p=null,$opt=''){
		$this->frame=parent::frame($spot,$title,$p,$opt);
		return $this;
	}
	function getFrame(){
		return $this->frame;
	}
	function setObject($object){
		$this->object=$object;
		return $this;
	}
	function getObject(){
		return $this->object;
	}
	function show(){
		$a=$this->add('Ajax')->setFrameVisibility($this,true);
		return $this->refresh($a);
	}
	function hide(){
		$a=$this->add('Ajax')->setFrameVisibility($this,false);
		return $a;
	}
	function refresh($ajax){
		if(is_null($this->object))throw new BaseException("Frame object is not defined");
		if(!is_object($this->object)){
			$ajax->loadRegionURL('frame_content',$this->api->getDestinationURL($this->object,array(
				'cut_object'=>$this->object,
			)));
		}else{
			$ajax->reload($this->object);
		}
		return $ajax;
	}
	function defaultTemplate(){
		return array('floating_frame','_top');
	}
}