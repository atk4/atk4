<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class Page_EntityManager extends Page {
    public $controller=null;
    public $model=null;
    public $c;

    public $allow_add=true;
    public $allow_edit=true;
    public $allow_delete=true;

    public $grid_actual_fields=false;
    public $edit_actual_fields=false;
    public $add_actual_fields; // by default same as edit
    public $read_only=false;

    public $grid;
    public $form;

    function init(){
        parent::init();
        if(!@$_GET['entitymanager'])$_GET['entitymanager']=$this->name;
        $this->api->stickyGET('entitymanager');
        if(!isset($this->add_actual_fields))$this->add_actual_fields=$this->edit_actual_fields;
        if(!$this->c){
            if($this->controller){
                $this->c=$this->add($this->controller);
            }elseif($this->model){
                $this->c=$this->add('Controller');
                $this->c->setModel('Model_'.$this->model);
            }
        }
    }
    function reloadJS(){
        return $this->js()->_selector('#'.$_GET['entitymanager'].'_grid')->atk4_loader('reload');
    }

    function initMainPage(){
        $this->grid=$g=$this->add('MVCGrid','grid');
        $g->js(true)->atk4_loader(array('url'=>$this->api->url(null,array('cut_object'=>$g->name))));


        if($this->grid_actual_fields)
            $c=$this->c->setActualFields($this->grid_actual_fields);

        $g->setController($c=$this->c);

        if($this->allow_edit)
            $g->addColumn('expander_widget', 'edit', $this->read_only?'View':'Edit');
        if($this->allow_add){
            $g->addButton('Add')->js('click')->univ()->dialogURL('Add new',$this->api->url('./edit'));
        }
        if($this->allow_delete){
            $g->addColumn('confirm','delete');
            if(@$_GET['delete']){
                $c->tryLoad($_GET['delete']);
                $c->delete();
                $g->js(null,$g->js()->univ()->successMessage('Record deleted'))->reload()->execute();
            }
        }
    }
    function page_edit(){
        if(!$this->allow_edit)exit;
        $this->form=$f=$this->add('MVCForm','form');
        $c=$this->c;

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

        if($_GET['id'] && !$this->read_only){
            if(!$f->hasElement('Save'))
                $f->addSubmit('Save');
        }else{
            unset($f->elements['Save']);
        }

        if($_GET['id'])$c->tryLoad($_GET['id']);

        if($f->isSubmitted() && !$this->read_only){
            $f->update();
            $f->js(null,$this->reloadJS())->univ()
                ->successMessage($_GET['id']?'Changes saved':'Record added')
                ->closeDialog()
                ->execute();
        }
    }
}
