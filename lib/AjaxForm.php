<?
trigger_error("AjaxForm is obsolete");
exit;
/*
 * AjaxForm is a Form variant which uses AJAX procedure to submit itself.
 */
class AjaxForm extends Form {
	function getDefaultTemplate(){
		return array('ajaxform','content');
	}
	function init(){
		parent::init();
		$this->template->set('form_id',$this->name);	// this is important for aasf()
		if(isset($_GET['reload_field_'.$this->name])){
			$this->api->addHook('post-init',$this,'reloadFieldCallback');
		}
	}
	function reloadFieldCallback(){
        $f=substr($_GET['reload_field_'.$this->name],strlen($this->name)+1);
        if(!isset($this->elements[$f]))return;

        echo $this->elements[$f]->getInput();
        exit;
	}
	function addSubmit($label,$name=null){
        $field = $this->add('Form_AjaxSubmit',isset($name)?$name:$label);
        $field -> setLabel($label);
        return $field;
	}
	function submited(){
		if(parent::submited()){
            $this->api->addHook('post-submit',$this,'ajaxFlush');
            return true;
        }
	}
	function ajaxFlush(){
		exit;
	}


	// some functions to help you perform actions on the form
	function displayMessage($msg){
		$msg=$this->jsEncode($msg);
		echo "aagi('msg_".$this->name."').innerHTML='".addslashes($msg)."';";
	}
	function reloadField($fld){
		echo "alert('reload field $fld - not implemented');";
	}
	function redirect($url){
		echo "document.location='".$url."';";
	}
	function loadRegion($id,$args=array()){
		$args=array_merge(array('ajax'=>'content'),$args);
		$url=$this->api->getDestinationURL(null,$args);
		echo "aasn('$id','$url');";
	}
	function jsEncode($msg){
		// addslashes?
		return $msg;
	}
}
