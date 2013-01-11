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
class QuickSearch extends Filter {
    /*
     * Quicksearch represents one-field filter which goes perfectly with a grid
     */
    public $icon='ui-icon-search'; // to configure icon

    function init(){
        parent::init();

        $this->addClass('float-right span4 atk-quicksearch');
        $this->template->trySet('fieldset','atk-row');
        $this->template->tryDel('button_row');
        $this->search_field=$this->addField('line','q','')->setNoSave();
        $this->search_field->addButton('',array('options'=>array('text'=>false)))
            ->setHtml('&nbsp;')
            ->setIcon($this->icon)
            ->js('click',$this->js()->submit());
    }
    function useFields($fields){
        $this->fields=$fields;
        return $this;
    }
    function postInit(){
        parent::postInit();
        if(!($v=$this->get('q')))return;

        if($this->view->model){
            $q=$this->view->model->_dsql();
        }else{
            $q=$this->view->dq;
        }
        $or=$q->orExpr();
        foreach($this->fields as $field){
            $or->where($field,'like','%'.$v.'%');
        }
        $q->having($or);
    }
}
