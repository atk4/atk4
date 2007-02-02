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


    public $ajax_output="";

    public $spinner = null;

    function execute(){
        $this->api->not_html=true;
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
        //$this->ajax_output="";
        return $s;
    }
    function getLink($text){
        return '<a href="javascript: void(0)" onclick="'.$this->getString().'">'.$text.'</a>';
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
    function reload($element,$args=array()){
        if(is_object($element)){
            if(!$element instanceof ReloadableView)throw new BaseException('Only views inherited from ReloadableView may be reloaded');
            $element=$element->name;
        }
        $args['cut_object']=$element;
        $url=$this->api->getDestinationURL(null,$args);
        $this->setVisibility("RD_".$element);
        $this->loadRegionURL("RR_".$element,$url);
        return $this;
    }
    function loadFieldValue($field_id,$url){
        $this->ajaxFunc("aasv('$field_id','$url')");
        return $this;
    }
    // urlwrap_admin_Aliases_grid_delete_frame_1_form
    function setFieldValue($form,$field,$value){
        $this->ajaxFunc("setFieldValue('".$form->name."','$field','$value')");
        return $this;
    }
    function setFormFocus($form,$field){
        $this->ajaxFunc("setFormFocus('".$form->name."','$field')");
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
        if(!$lister)$lister=$_GET['expanded'];
        $id=(int)$_GET['id'];
        $button=preg_replace('/.*_(.*)/','\\1',$this->api->page);
        return $this->ajaxFunc("expander_flip('".$lister."',".$id.",'".$button."','')");
    }
    function reloadExpander($url,$args=array()){
    	/**
    	 * Reloads expander after some action permormed in it
    	 * (like form submission, nested grid record deletion, etc.)
    	 */
		$this->api->stickyGET('id');
		$this->api->stickyGET('expanded');
		$this->api->stickyGET('expander');
    	return $this->loadRegionURL($_GET['expanded'].'_expandedcontent_'.$_GET['id'],
			$this->api->getDestinationURL($url, array_merge(array('cut_object'=>$url), 
				$args)));
    }
    function reloadExpandedField($url,$args=array()){
    	/**
    	 * Reloads the field of the grid that had been expanded.
    	 * Useful for grid update after updates in expander. You can use it along with reloadExpander()
    	 * Specified URL should return the needed content (see also Grid::getFieldContent())
    	 */
		$this->api->stickyGET('id');
		$this->api->stickyGET('expanded');
		$this->api->stickyGET('expander');
    	return $this->loadRegionURL($_GET['expanded'].'_'.$_GET['expander'].'_'.$_GET['id'],
    		$this->api->getDestinationURL($url,array_merge(
    			array('cut_object'=>$url,'grid_action'=>'return_field','expanded'=>$_GET['expanded'],
    			'expander'=>$_GET['expander'],'id'=>$_GET['id']), 
				$args)));
    }
    function reloadExpandedRow($url,$args=array()){
    	/**
    	 * Reloads the row of the grid that had been expanded.
    	 * Useful for grid update after updates in expander. You can use it along with reloadExpander()
    	 * Specified URL should return the needed content (see also Grid::getRowAsCommaString())
    	 */
		$this->api->stickyGET('id');
		$this->api->stickyGET('expanded');
		$this->api->stickyGET('expander');
    	return $this->ajaxFunc('reloadGridRow("'.
    		$this->api->getDestinationURL($url,array_merge(
    			array('cut_object'=>$url,'grid_action'=>'return_row','expanded'=>$_GET['expanded'],
    			'expander'=>$_GET['expander'],'id'=>$_GET['id']), 
				$args)).'","'.$_GET['expanded'].'",'.$_GET['id'].')');
    }
    function confirm($msg="Are you sure?"){
        return $this->ajaxFunc("if(!confirm('$msg'))return false");
    }

    function reloadField($fld){
        $this->notImplemented("reloadField");
    }
	function submitForm($form){
		$this->ajaxFunc("submitForm('$form->name','".$this->spinner."')");
        $this->spinner=null;
		return $this;
	}
    function resetForm($form){
		$this->ajaxFunc("resetForm('$form->name')");
		return $this;
    }
    function setVisibility($element,$visible=true){
        if(!is_string($element))$element=$element->name;
        $this->ajaxFunc("setVisibility('".$element."',".($visible?"true":"false").")");
        return $this;
    }
    function setFrameVisibility($frame,$visibility=true){
        $this->setVisibility($frame->name."_bg",$visibility);
        $this->setVisibility($frame->name."_fr",$visibility);
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
    function alert($msg){
        return $this->displayAlert($msg);
    }
    function useProgressIndicator($id){
        $this->spinner=$id;
        $this->ajaxFunc("spinner_on('$id')");
        return $this;
    }



}
