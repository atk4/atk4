<?php
/*
   This class represents sequentall calls to one jQuery object
   */
class jQuery_Chain extends AbstractModel {
	private $str='';
	private $prepend='';
	private $selector=false;
	private $enclose=false;
	function __call($name,$arguments){
		if($arguments){
			$a2=array();
			foreach($arguments as $arg){
				if(is_object($arg)){
					if($arg instanceof jQuery_Chain){
						$s=$arg->_render();
					}else{
						$s="'#".$arg->name."'";
					}
				}elseif($arg===null){
					$s="undefined";
				}elseif(is_int($arg)){
					$s="$arg";
				}elseif(is_array($arg)){
					$s=json_encode($arg);
				}elseif(is_bool($arg)){
					$s=$arg?"true":"false";
				}elseif(is_string($arg)){
					$s="'".addslashes($arg)."'";
				}else{
					throw new BaseException("wrong argument type to jQuery_Chain: ".$arg );
				}
				$a2[]=$s;
			}
			$this->str.=".$name(".join(",",$a2).")";
		}else{
			$this->str.=".$name()";
		}
		return $this;
	}
	function _fn($name,$arguments=array()){
		// Wrapper for functons which use reserved words
		return $this->__call($name,$arguments);
	}
	function __toString(){
		return $this->_render();
	}
	function _selector($selector){
		$this->selector=$selector;
		return $this;
	}
	function _prepend($code){
		$this->prepend=$code.';'.$this->prepend;
		return $this;
	}
	function execute(){
		if(isset($_POST['ajax_submit'])){
			echo $this->_render();
			exit;
		}else return $this;
	}



	function redirect($page=null,$arg=null){
		$url=$this->api->getDestinationURL($page,$arg);
		return $this->_fn('redirect',array($url));
	}
	function reload($id=null,$url=null){
		if(!$id)$id=$this->owner;
		if($url==null)$url=$this->api->getDestinationURL(null,array('cut_object'=>$id->name));
		return $this->_fn('reload',array($id,$url));
	}
	function reloadArgs($key,$value){
		$id=$this->owner;
		$url=$this->api->getDestinationURL(null,array('cut_object'=>$id->name));
		return $this->_fn('reloadArgs',array($url,$key,$value));
	}
	function saveSelected($grid){
        $url=$this->api->getDestinationUrl(null,array('save_selected'=>1));
		return $this->_fn('saveSelected',array($grid,$url));
	}

	function _enclose($fn=null){
		// builds structure $('obj').$fn(function(){ $('obj').XX; });
		if($fn===null)$fn=true;
		$this->enclose=$fn;
		return $this;
	}
	function _render(){
		$ret='';
		$ret.=$this->prepend;
		if($this->str)$ret.="$('".($this->selector?$this->selector:'#'.$this->owner->name)."')";
		$ret.=$this->str;
		if($this->enclose===true){
			$ret="function(){ ".$ret." }";
		}elseif($this->enclose){
			$ret="$('".($this->selector?$this->selector:'#'.$this->owner->name)."')".
				".".$this->enclose."(function(ev){ ev.preventDefault(); ".$ret." })";
		}
		return $ret;
	}
	function getLink($text){
		return '<a href="javascript:void(0)" onclick="'.$this->getString().'">'.$text.'</a>';
	}
	function getString(){
		return $this->_render();
	}
	function _css($file){
		$this->api->jquery->addStylesheet($file);
		return $this;
	}
	function _load($file){
		$this->api->jquery->addInclude($file);
		return $this;
	}
	function render(){
		$this->output($this->base.$this->str.";\n");
	}
}
