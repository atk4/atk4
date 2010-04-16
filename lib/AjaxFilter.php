<?php
trigger_error("AjaxFilter is obsolete");
exit;
class AjaxFilter extends AjaxForm {
	public $dq;
	public $region=array();
	function init(){
		parent::init();
		if(isset($_GET[$this->name.'_filter'])){
			$this->data=unserialize(base64_decode($_GET[$this->name.'_filter']));
		}
	}
	function region($region){
		$this->region[]=$region;
		return $this;
	}
	function submited(){
		if(parent::submited()){
			if($this->isClicked('Clear')){
				$this->clearData();
			}

			$filter=base64_encode(serialize($this->data));
			foreach($this->region as $region){
				$this->loadRegion($region,array($this->name.'_filter'=>$filter));
			}
			return true;
		}
	}
	function useDQ($dq){
		$this->dq[]=$dq;
		$this->api->addHook('post-submit',$this,'applyHook');
		return $this;
	}
	function applyHook(){
		foreach($this->dq as $key=>$dq){
			$this->applyDQ($this->dq[$key]);
		}
	}
	function applyDQ($dq){
		// Redefine this function to apply limits to $dq.
	}
}
