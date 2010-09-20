<?php
class Page_EntityManager extends Page {
	public $controller='Controller_Coupon';
	public $returnpage='coupons';

	public $allow_add=true;
	public $allow_edit=true;
	public $allow_delete=true;

	public $grid_actual_fields=false;
	public $edit_actual_fields=false;
	public $add_actual_fields; // by default same as edit
	public $read_only=false;

	public $grid;

	function init(){
		parent::init();
		if(!isset($this->add_actual_fields))$this->add_actual_fields=$this->edit_actual_fields;
	}

	function initMainPage(){
		$this->grid=$g=$this->add('MVCGrid','grid');

		$c=$g->add($this->controller);
		
		if($this->grid_actual_fields)
			$c->setActualFields($this->grid_actual_fields);

		$g->setController($c);

		if($this->allow_edit)
			$g->addColumnPlain('expander_widget', 'edit', $this->read_only?'view':'edit');
		if($this->allow_add){
			$g->addButton('Add')->js('click')->univ()->dialogURL('Add new',$this->api->getDestinationURL('./edit'));
		}
		if($this->allow_delete){
			$g->addColumnPlain('confirm','delete');
			if($_GET['delete']){
				$c->loadData($_GET['delete']);
				$c->delete();
				$g->js(null,$g->js()->univ()->successMessage('Record deleted'))->reload()->execute();
			}
		}
		
		
		if($this->allow_edit){
			if($_GET['edit']){
				$this->js()->univ()->location($this->api->getDestinationURL($this->returnpage,
							Array('id' => $_GET['edit'])))->execute();
			}
		}
	}
	function page_edit(){
		if(!$this->allow_edit)exit;
		$f=$this->add('MVCForm','form');
		$c=$f->add($this->controller);

		if($_GET['id']){
			if($this->edit_actual_fields)
				$c->setActualFields($this->edit_actual_fields);
		}else{
			if($this->add_actual_fields)
				$c->setActualFields($this->add_actual_fields);
		}

		$f->setController($c);
		if($this->read_only){
			unset($f->elements['Save']);
			$f->js(true)->find('input,select')->attr('disabled',true);
		}

		if($_GET['id']){
			if(!$f->hasElement('Save'))
			$f->addSubmit('Save');
		}else{
			unset($f->elements['Save']);
		}

		if($_GET['id'])$c->loadData($_GET['id']);

		if($f->isSubmitted() && !$this->read_only){
			$f->update();
			$f->js()->univ()
				->successMessage($_GET['id']?'Changes saved':'Record added')
				->closeDialog()
				->page($this->api->getDestinationURL($this->returnpage))
				->execute();
		}
	}
}
