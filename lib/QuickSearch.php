<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
class QuickSearch extends Filter {
	/*
	 * Quicksearch represents one-field filter which goes perfectly with a grid
	 */

	public $js_widget='ui.atk4_form';
    public $icon;// to configure icon
	var $region=null;
	var $region_url=null;
	public $search_cross=null;
	public $grid;

	function init(){
		parent::init();
        //$this->addClass('span3');

        $this->addClass('float-right stacked span4');
        $this->template->trySet('fieldset','atk-row');
        $this->template->tryDel('button_row');
        $this->search_field=$this->addField('line','q','')->setNoSave();
        $this->search_field->addButton('')
            ->setHtml('&nbsp;')
            ->setIcon('search')
            ->js('click',$this->js()->submit());
            ;
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
