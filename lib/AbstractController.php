<?php
class AbstractController extends AbstractObject {
	protected $model;
	
	public function setModel($classname) {
		$this->model = $this->add($classname);
		return $this;
	}
	
	public function getModel() {
		return $this->model;
	}
}
