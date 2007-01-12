<?php
/*
 * This object enables many advanced actions only possible with using AJAX technology.
 *
 * You would need a compliant browser too.
 *
 * In order to use this module do $ajax = $this->add('Ajax');
 */

class Ajax extends AbstractModel {
    public $api;
    public $owner;


    private $form;

    public $ajax_output="";

    public $spinner = null;

    function execute(){
        echo $this->ajax_output;
        exit;
    }
    function ajaxFlush(){
        // Now, since we are returning AJAX stuff, we don't need to render anything.
        if($this->ajax_output){
            $this->execute();
        }
    }
    function getString(){
        if($this->spinner)$this->ajaxFunc("spinner_off('".$this->spinner."')");
        $s=//"//ajax_script_start\n".
        	$this->ajax_output."//ajax_script_end\n";
        $this->ajax_output="";
        return $s;
    }


    function ajaxFunc($func_call){
        $this->ajax_output.="$func_call;\n";
        return $this;
    }
    function redirect($page=null,$args=array()){
        return $this->redirectURL($this->api->getDestinationURL($page,$args));
    }
    function redirectURL($url){
        return $this->ajaxFunc("document.location='".$url."'");
    }
    function loadRegionURL($region_id,$url){
        $this->ajaxFunc("aasn('$region_id','$url')");
        return $this;
    }
    function reloadRegion($region_id,$args=array()){
		$args=array_merge(array('cut_region'=>$region_id),$args);
		$url=$this->api->getDestinationURL(null,$args);
		return $this->loadRegionURL($region_id,$url);
    }
    function loadFieldValue($field_id,$url){
        $this->ajaxFunc("aasv('$field_id','$url')");
        return $this;
    }
    function setInnerHTML($field_id,$value){
        $value=str_replace("'",'',$value);
        $value=str_replace("\r\n",'\\n',$value);  // this is for templates what saved with
        										  // Windows line delimiters -- mvs
        $value=str_replace("\n",'\\n',$value);

        return $this->ajaxFunc("aafc('$field_id','$value')");
    }
    function notImplemented($msg){
        return $this->ajaxFunc("alert('not implemented: $msg')");
    }
    function closeExpander($lister=null){
        $lister=$_GET['expanded'];
        $id=(int)$_GET['id'];
        $button=preg_replace('/.*_(.*)/','\\1',$this->api->page);
        return $this->ajaxFunc("expander_flip('".$lister."',".$id.",'".$button."','')");
    }
    function reloadExpander($url,$args=array()){
    	/**
    	 * Reloads expander after some action permormed in it
    	 * (like form submission, nested grid record deletion, etc.)
    	 * You should stickyGET('expanded') and stickyGET('id') to make it work properly
    	 */
    	return $this->loadRegionURL($_GET['expanded'].'_expandedcontent_'.$_GET['id'],
			$this->api->getDestinationURL($url, array_merge(array('cut_object'=>$url), 
				$args)));
    }
    function confirm($msg="Are you sure?"){
        return $this->ajaxFunc("if(!confirm('$msg'))return false");
    }

    // form specific fnuctions
    function withForm($form){
        // associates this class with form class
        $this->form=$form;
        return $this;
    }
    function reloadField($fld){
        $this->notImplemented("reloadField");
    }
	function submitForm($form){
		$this->ajaxFunc("submitForm('$form->name','".$this->spinner."')");
        $this->spinner=null;
		return $this;
	}
    function makeVisible($element){
        $this->ajaxFunc("setVisibility('".$element->name."',true)");
        return $this;
    }
    function makeInvisible($element){
        $this->ajaxFunc("setVisibility('".$element->name."',false)");
        return $this;
    }


    function displayFormError($fld,$message){
        $this->ajaxFunc("alert('$field: $message')");
        return $this;
    }
    function displayAlert($msg){
        $this->ajaxFunc("alert('$msg')");
        return $this;
    }
    function useProgressIndicator($id){
        $this->spinner=$id;
        $this->ajaxFunc("spinner_on('$id')");
        return $this;
    }



}
