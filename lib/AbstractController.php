<?php
class AbstractController extends AbstractObject {
	protected $model;
	
	function __clone(){
		parent::__clone();
		if($this->model)$this->model=clone $this->model;
	}
	public function setModel($classname) {
		$this->model = $this->add($classname);
		return $this;
	}
	
	public function getModel() {
		return $this->model;
	}
}
