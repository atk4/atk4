<?php
class jQuery_Chain extends AbstractView {
	private $str='';
	private $base='';
	function __call($name,$arguments){
		if($arguments){
			$a2=array();
			foreach($arguments as $arg){
				if(is_object($arg)){
					$s="'#".$arg->name."'";
				}elseif(is_int($arg)){
					$s="$arg";
				}elseif(is_bool($arg)){
					$s=$arg?"true":"false";
				}elseif(is_string($arg)){
					$s="'".addslashes($arg)."'";
				}else{
					throw new CoreException("wrong argument type to jQuery_Chain");
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
	function _tag($tag){
		if(is_object($tag))$tag='#'.$tag->name;
		$this->base="$('$tag')";
		return $this;
	}
	function _univ($tag){
		if(is_object($tag))$tag='#'.$tag->name;
		$this->base="$.atk4.chain('$tag')";
		return $this;
	}
	function render(){
		$this->output($this->base.$this->str.";\n");
	}
}
