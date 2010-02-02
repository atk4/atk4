<?php
/*
   This class represents sequentall calls to one jQuery object
   */
class jQuery_Chain extends AbstractModel {
	private $str='';
	private $prepend='';
	private $selector=null;
	private $enclose=false;
	private $preventDefault=false;
	public $base='';
	function __call($name,$arguments){
		if($arguments){
			$a2=$this->_flattern_objects($arguments,true);
			$this->str.=".$name(".$a2.")";
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
	function _selectorThis(){
		$this->selector=true;
		return $this;
	}
	function _prepend($code){
		$this->prepend=$code.';'.$this->prepend;
		return $this;
	}
	function execute(){
		if(isset($_POST['ajax_submit'])){
			//if($this->api->jquery)$this->api->jquery->getJS($this->owner);

			// TODO: currently does not support ability to execute multiple chains
			// also in some cases we might be called without ajax_submit

			$x=$this->api->template->get('document_ready');
			if(is_array($x))$x=join('',$x);

			//echo $x.';';
			echo $this->_render();
			exit;
		}else return $this;
	}

	function _flattern_objects($arg,$return_comma_list=false){
		/*
		 * This function is very similar to json_encode, however it will traverse array
		 * before encoding in search of objects based on AbstractObject. Those would
		 * be replaced with their json representation if function exists, otherwise
		 * with string representation
		 */
		if(is_object($arg)){
			if($arg instanceof jQuery_Chain){
				$r=$arg->_render();
				if(substr($r,-1)==';')$r=substr($r,0,-1);
				return $r;
			}else{
				return "'#".$arg->name."'";
			}
		}elseif(is_array($arg)){
			$a2=array();
			// is array associative? (hash)
			$assoc=$arg!=array_values($arg);

			foreach($arg as $key=>$value){
				$v=$this->_flattern_objects($value);
				if(!$assoc || $return_comma_list){
					$a2[]=$v;
				}else{
					$a2[]='"'.$key.'":'.$v;
				}
			}
			if($return_comma_list){
				$s=join(',',$a2);
			}elseif($assoc){
					$s='{'.join(',',$a2).'}';
			}else{
					$s='['.join(',',$a2).']';
			}
		}else{
			$s=json_encode($arg);
		}

		return $s;
	}



	function redirect($page=null,$arg=null){
		$url=$this->api->getDestinationURL($page,$arg);
		return $this->_fn('redirect',array($url));
	}
	function reload($arguments=array(),$fn=null,$url=null){
		/*
		 * $obj->js()->reload();	 will now properly reload most of the objects. 
		 * This function can be also called on a low level, however URL have to be
		 * specified. 
		 * $('#obj').univ().reload('http://..');
		 *
		 * Difference between atk4_load and this function is that this function will
		 * correctly replace element and insert it into container when reloading. It
		 * is more suitable for reloading existing elements
		 *
		 * $fn if specified - should be ->js();
		 */
		if(!is_array($arguments)){
			throw new BaseException('symantic for js()->reload() have changed. Please consult documentation.');
		}
		if($fn)$fn->_enclose();
		$id=$this->owner;
		if(!$url)$url=$this->api->getDestinationURL(null,array('cut_object'=>$id->name));
		return $this->_fn('atk4_reload',array($url,$arguments,$fn));
	}
	function saveSelected($grid){
        $url=$this->api->getDestinationUrl(null,array('save_selected'=>1));
		return $this->_fn('saveSelected',array($grid,$url));
	}

	function _enclose($fn=null,$preventDefault=false){
		// builds structure $('obj').$fn(function(){ $('obj').XX; });
		if($fn===null)$fn=true;
		$this->enclose=$fn;
		$this->preventDefault=$preventDefault;
		return $this;
	}
	function _render(){
		$ret='';
		$ret.=$this->prepend;
		if($this->selector===false){
			$ret.="$";
		}elseif($this->selector===true){
			$ret.="$(this)";
		}else{
			if($this->str)$ret.="$('".($this->selector?$this->selector:'#'.$this->owner->name)."')";
		}
		$ret.=$this->str;
		if($this->enclose===true){
			if($this->preventDefault){
				$ret="function(ev){ev.preventDefault(); ".$ret." }";
			}else{
				$ret="function(){ ".$ret." }";
			}
		}elseif($this->enclose){
			$ret="$('".($this->selector?$this->selector:'#'.$this->owner->name)."')".
				".bind('".$this->enclose."',function(ev){ ev.preventDefault(); ".$ret." })";
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
