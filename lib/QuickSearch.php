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

	function defaultTemplate(){
		return array('form/quicksearch','form');
	}
	function init(){
		parent::init();
		$this->js(true)->_load('ui.atk4_form')->atk4_form();

		//on field change we should change a name of a button also: 'clear' in the name will clear fields

		/*
		   $ff->js('focus',array(
		   $x->js()->show(),
		   $s->js()->hide()
		   ));
		   $ff->js('blur',array(
		   $x->js()->hide(),
		   $s->js()->show()
		   ));
		 */
		//$this->addSubmit('Go');
	}
	function useGrid($grid){
		$this->grid=$grid;
		$this->useDQ($this->grid->dq);
		return $this;
	}
	function recallAll(){
		$ff=$this->addField('line','q','');//->onChange()->ajaxFunc($this->setGoFunc());
		parent::recallAll();
		//$ff->js(true)->univ()->autoChange(1);
		//$ff->js('change',$this->js()->submit());
		$search_cross=$ff->add('HtmlElement',null,'after_field')
			->setElement('i')
			->addClass('atk-icon')
			->addClass('atk-icons-nobg')
			->addClass('atk-icon-basic-ex')
			->set('')
			;

		if(!$this->get('q')){
			$search_cross->js(true)->hide();
		}
		$search_cross->js('click',array($ff->js()->val(''),$this->js()->submit()));
		$this->icon=$s=$this->add('Icon',null,'form_buttons');
        $s->set('basic-search')
			->setColor('gray');

		$s->js('click',array(
					$this->js()->submit()
				    ));
	}
	function setGoFunc(){
		return "btn=document.getElementById('".$this->name.'_Clear'."'); if(btn){btn.value='Go'; btn.name='".
			$this->name."_go'; btn.id='".$this->name."_go';}";
	}
	function useFields($fields){
		$this->fields=$fields;
		return $this;
	}
	function applyDQ($dq){
		if(!($v=$this->get('q')))return;

		$v=addslashes($v);  // quote it

		$q=array();
		foreach($this->fields as $field){
			$q[]="$field like '%".$v."%'";
		}
		if($q){
			$dq->having(join(' or ',$q));
		}
	}
	function submitted(){
		if(parent::submitted()){
			$this->grid->js()->reload()->execute();
		}
	}
}
