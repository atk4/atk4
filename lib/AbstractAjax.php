<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
/**
* Created on 26.11.2008 by *Camper* (cmd@adevel.com)
*/
abstract class AbstractAjax extends AbstractModel{
	public $api;
	public $owner;

	protected $ajax_output=array();
	protected $spinner = null;
	protected $timeout=null;

	function init(){
		parent::init();
		// before proceed we need to check session
		// only if we are on restricted page
		// TODO: terrible code, need removal. Session is always checked before any pages
		// are initialised, in app->init()
		if(isset($this->api->auth)&&!is_null($this->api->auth)){
			if($this->api->auth instanceof BasicAuth && !$this->api->auth->isPageAllowed($this->api->page))
				$this->checkSession();
		}
	}
	///////////////// Abstract methods /////////////////
	abstract function submitForm($form);
	abstract function useProgressIndicator($id,$timeout=null);
	abstract function executeUrl($url);
	abstract function loadRegionURL($region_id,$url);
	abstract function setInnerHTML($field_id,$value);
	abstract function disableControl($control);
//	abstract function getFormFieldValue($form,$field_name,$var='fv');
//	abstract function loadFieldValue($field_id,$url);
//	abstract function reload($element,$args=array(),$page=null);
//	/**
//	 * Reloads the field of the grid that had been expanded.
//	 * Useful for grid update after updates in expander. You can use it along with reloadExpander()
//	 * Specified URL should return the needed content (see also Grid::getFieldContent())
//	 */
//	abstract function reloadExpandedField($url,$args=array());
//	/**
//	 * Reloads the row of the grid that had been expanded.
//	 * Useful for grid refresh after updates in expander. You can use it along with reloadExpander()
//	 * Specified URL should return the needed content (see also Grid::getRowAsCommaString())
//	 */
//	abstract function reloadExpandedRow($url,$args=array());
//	/**
//	 * Reloads expander after some action permormed in it
//	 * (like form submission, nested grid record deletion, etc.)
//	 */
//	abstract function reloadExpander($url,$args=array());
//	abstract function reloadRegion($region_id,$args=array());
//	abstract function resetForm($form);
	///////////////// Common methods /////////////////
	function execute(){
		$this->api->not_html=true;
		echo $this->getAjaxOutput();
		exit;
	}
	function getAjaxOutput(){
		return join(';',$this->ajax_output) . ';';
	}
	function getString(){
		if($this->spinner)$this->ajaxFunc("spinner_off('".$this->spinner."')");
		return $this->getAjaxOutput();
	}
	function getLink($text){
		return '<a href="javascript:void(0)" onclick="'.$this->getString().'">'.$text.'</a>';
	}
	function ajaxFlush(){
		// Now, since we are returning AJAX stuff, we don't need to render anything.
		if($this->ajax_output){
			$this->execute();
		}
	}
	/**
	* Clears the ajax_output property, erasing all the functions
	* calls assigned so far
	*/
	function reset(){
		$this->ajax_output=array();
		return $this;
	}
	function notImplemented($msg){
		return $this->alert("not implemented: $msg");
	}
	function ajaxFunc($func_call){
		if(is_null($this->timeout))$this->ajax_output[]="$func_call";
		else{
			$this->ajax_output[]="setTimeout('".addslashes($func_call)."',$this->timeout)";
			$this->timeout=null;
		}

		return $this;
	}
	function checkSession($key='session is expired, relogin'){
		// in order for JS not to find a string in the parameters of function itself,
		// parameter should be encoded first
		$key=str_replace(' ','=',$key);
		return $this->ajaxFunc("checkSession('".$this->api->getDestinationURL(null)."','$key')");
	}
	/**
	* adds a setTimeout() to the next function. $timeout is in milliseconds
	* Use this function when you need to call AJAX function after some delay.
	* This can be useful if you call 2 functions:
	* 1) update DB
	* 2) reload a region to display updated data
	*
	* as AJAX is asynchronious technology, (1) could finish after (2) is finished, and
	* you won't see any updates on the page
	*/
	function delay($timeout){
		$this->timeout=$timeout;
		return $this;
	}
	function redirect($page=null,$args=array()){
		return $this->redirectURL($this->api->getDestinationURL($page,$args));
	}
	function redirectURL($url){
		return $this->ajaxFunc("window.location='".$url."'");
	}
	function confirm($msg="Are you sure?"){
		return $this->ajaxFunc("if(!confirm('$msg'))return false");
	}
	function displayFormError($fld,$message){
		$this->ajaxFunc("showMessage('$field: $message')");
		return $this;
	}
	function displayAlert($msg){
		$this->ajaxFunc("showMessage('$msg')");
		return $this;
	}
	function alert($msg){
		return $this->displayAlert($msg);
	}
	function setFormFocus($form,$field){
		$this->ajaxFunc("setFormFocus('".$form->name."','$field')");
		return $this;
	}
	///////////////// Expanders /////////////////
	function openExpander($lister,$id,$field){
		return $this->ajaxFunc('expander_flip(\''.$lister->name.'\',\''.$id.'\',\''.
					$field.'\',\''.
					$this->api->getDestinationURL($this->api->page.'_'.$field,array('expander'=>$field,
						'cut_object'=>$this->api->page.'_'.$field,'expanded'=>$lister->name,'id'=>$id)).'\')');
	}
	function closeExpander($lister=null){
		if(!$lister)$lister=$_GET['expanded'];
		$id=(int)$_GET['id'];
		$button=preg_replace('/.*_(.*)/','\\1',$this->api->page);
		return $this->ajaxFunc("expander_flip('".$lister."','".$id."','".$button."','')");
	}
	function closeExpanderWidget(){
		// TODO: $(this).closest('.expander').atk4_expander('collapse')
		return $this->ajaxFunc("$('.expander').atk4_expander('collapse')");

	}

	function memorizeExpander(){
		$this->api->stickyGET('id');
		$this->api->stickyGET('row_id');
		$this->api->stickyGET('expanded');
		$this->api->stickyGET('expander');
		return $this;
	}
	function forgetExpander(){
		$this->api->stickyForget('id');
		$this->api->stickyForget('row_id');
		$this->api->stickyForget('expanded');
		$this->api->stickyForget('expander');
		return $this;
	}
	function reloadExpander($url,$args=array()){
		$this->memorizeExpander();
		return $this->loadRegionURL($_GET['expanded'].'_expandedcontent_'.$_GET['id'],
			$this->api->getDestinationURL($url, array_merge(array('cut_object'=>str_replace('/','_',$url)),
				$args)));
	}
	function reloadExpandedRow($url,$args=array()){
		$this->memorizeExpander();
		return $this->reloadGridRow($_GET['expanded'],$_GET['id'],$url,array_merge(
				array('expander'=>$_GET['expander']), $args));
	}
	///////////////// /////////////////
	function reloadGridRow($grid,$id,$url=null,$args=array()){
		if(is_object($grid))$grid_name=$grid->name;
		else $grid_name=$grid;
		return $this->ajaxFunc('reloadGridRow(\''.
			$this->api->getDestinationURL($url,array_merge(
				array('cut_object'=>$url,'grid_action'=>'return_row','expanded'=>$grid_name,
				'id'=>$id),
				$args)).'\',\''.$grid_name.'\','.$id.')');
	}
}
