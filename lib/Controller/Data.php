<?php
/* 
Reference implementation for data controller

Data controllers are used by "Model" class to access their data source.
Additionally Model_Table can use data controllers for caching their data

*/

abstract class Model_Data extends AbstractController {
	public $source=null;

	function init(){
		parent::init();

		// default for caching
		if($this->owner instanceof Model_Table){
			$this->setModel($this->owner);
			$this->setSource(get_class($this->owner));
		}elseif($this->owner instanceof Model){
			$this->setModel($this->owner);
			$this->setSource($this->owner->table);
		}
	}

	abstract function load($id){}
	abstract function save(){}
	abstract function delete($id){}

	abstract function rewind(){}
	abstract function rewind(){}

	abstract function tryLoad($id){}
    abstract function loadBy($field,$cond=undefined,$value=undefined){}
    abstract function tryLoadBy($field,$cond=undefined,$value=undefined){}

    abstract function deleteAll(){}
    abstract function getRows(){}

    abstract function setOrder($field,$desc=false){}
    abstract function setLimit($count,$offset=0){}

}

