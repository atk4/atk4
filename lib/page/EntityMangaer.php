<?php
class Page_EntityManager extends AbAdminPage {
	public $controller='Controller_Coupon';
	public $returnpage='coupons';

	public $allow_add=true;
	public $allow_edit=true;
	public $allow_delete=true;

	public $grid_actual_fields=false;
	public $read_only=false;
	function init(){
		parent::init();
	}

	function initMainPage(){
		$g=$this->add('AbGrid');

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
		$f=$this->add('AbForm');

		$c=$f->add($this->controller);
		$f->setController($c);
		if($this->read_only){
			unset($f->elements['Save']);
			$f->js(true)->find('input,select')->attr('disabled',true);
		}

		if($_GET['id'])$c->loadData($_GET['id']);

		if($f->isSubmitted() && !$this->read_only){
			$f->update();
			$f->js()->univ()->successMessage('Changes saved')->location($this->api->getDestinationURL($this->returnpage))
				->execute();
		}
	}
}
