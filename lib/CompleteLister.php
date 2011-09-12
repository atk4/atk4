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
class CompleteLister extends Lister {
	public $totals=false;
	protected $row_t;
	protected $totals_t=false;
	function init(){
		parent::init();
        if(!$this->template->is_set('row'))throw $this->exception('Template must have "row" tag');
		$this->row_t=$this->template->cloneRegion('row');
		if($this->template->is_set('totals')){
			$this->totals_t=$this->template->cloneRegion('totals');
		}
	}
	function addTotals(){
		$this->totals=array();
		return $this;
	}
	function updateTotals(){
		foreach($this->current_row as $key=>$val){
			@$this->totals[$key]+=strip_tags($val);
		}
		@$this->totals['row_count']++;
	}
	function formatTotalsRow(){
		$this->formatRow();
		$this->totals['plural_s']=$this->totals['row_count']>1?'s':'';
		if($this->totals['row_count']==0){
			$this->totals['row_count']='no';
			$this->totals['plural_s']='s';
		}
	}

	function rowRender($row) {
		return $this->row_t->render();
	}

	function render(){
		$this->tr_class='';
		$this->template->del('rows');
		while($this->fetchRow()){
			if($this->totals!==false)$this->updateTotals();
			$this->formatRow();
			$this->row_t->set($this->current_row);
			$this->setTRClass();
			$this->template->append('rows',$this->rowRender($this->current_row));
		}
		if($this->totals!==false && $this->totals_t){
			$t = $this->totals_t;
			$this->current_row = &$this->totals;
			$this->formatTotalsRow();
			$t->set($this->current_row);
			$this->template->append('rows',$t->render());
		}

		// If partial reload is requested, then we only return rows, not the complete template
		if(@$_GET[$this->name.'_reload_row']){
			$r=$this->template->cloneRegion('rows')->render();
			if($this->api->jquery)$this->api->jquery->getJS($this);
			$e=new RenderObjectSuccess($r);
			throw $e;
		}

		$this->output($this->template->render());
	}
	function setTRClass(){
		$this->tr_class=$this->tr_class=='odd'?'even':'odd';
		$this->row_t->trySet('odd_even',$this->tr_class);
	}
}
