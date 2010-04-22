<?
/*
 * This is class for ordering elements. 
 *
 */
	
class Order extends AbstractController {
	public $rules=array();
	public $array=null;

	function init(){
		parent::init();
		$this->useArray($this->owner->elements);
	}
	function useArray(&$array){
		$this->array=&$array;
		return $this;
	}
	function move($name,$where,$relative=null){
		$this->rules[]=array($name,$where,$relative);
		return $this;
	}
	function now(){
		foreach($this->rules as $rule){
			list($name,$where,$relative)=$rule;

			// check if element exists
			if(!isset($this->array[$name]))
				throw new Exception_InitError('Element '.$name.' does not exist when trying to move it '.$where.' '.$relative);

			$v=$this->array[$name];
			unset($this->array[$name]);

			switch($where){
				case 'first':
					// moving element to be a first child
					$this->array=array($name=>$v)+$this->array;
					break;
				case 'last':
					$this->array=$this->array+array($name=>$v);
					break;
				case 'after':
					$this->array=array_reverse($this->array);
				case 'before':
					$tmp=array();
					foreach($this->array as $key=>$value){
						if($key===$relative || (is_array($relative) && in_array($key,$relative))){
							$tmp[$name]=$v;
							$name=null;
						}
						$tmp[$key]=$value;
					}
					$this->array=$tmp;
					if($name)throw new Exception_InitError('Unable to perform move, relative key does not exist');

					if($where=='after')$this->array=array_reverse($this->array);
					break;

					
			}
		}
	}
	function onHook($object,$hook){
		$object->addHook($hook,array($this,'now'));
	}
}
