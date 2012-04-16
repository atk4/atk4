<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * CompleteLister is very similar to regular Lister,
 * but will use <?rows?><?row?>blah<?/?><?/?> structrue
 * inside template. Also adds support for totals.
 * 
 * @link http://agiletoolkit.org/doc/lister
 *
 * Use:
 *  $list=$this->add('CompleteLister');
 *  $list->setModel('User');
 *  $list->addTotals();
 *
 * Template (view/users.html):
 *  <h3>Users</h3>
 *  <?rows?>
 *   <?row?>
 *    <h4><?$name?></h4>
 *    <p><?$desc?></p>
 *   <?/row?>
 *   <h4>Joe Blogs</h4>
 *   <p>Sample template. Will be ignored</p>
 *  <?/rows?>
 *  <?totals?>
 *    <?$row_count?> user<?$plural_s?>.
 *  <?/?>
 *
 * @license See http://agiletoolkit.org/about/license
 *
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2012 Romans Malinovskis <romans@agiletoolkit.org>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class CompleteLister extends Lister {

    protected $item_tag='row';
    protected $container_tag='rows';

	protected $row_t;

    /** Will contain accumulated totals for all fields */
	public $totals=false;

    /** Will be initialized to "totals" template when addTotals() is called */
	public $totals_t=false;

	function init(){
		parent::init();
        if(!$this->template->is_set($this->item_tag))
            throw $this->exception('Template must have "'.$this->item_tag.'" tag');

		$this->row_t=$this->template->cloneRegion($this->item_tag);
	}

    /** Enable total calculation for specified array of fields. If not specified, all field totals are calculated */
	function addTotals($fields=null){
		if($this->template->is_set('totals')){
			$this->totals_t=$this->template->cloneRegion('totals');
		}

        if($fields){
            foreach($fields as $field)$this->totals[$field]=0;
        }elseif($this->totals===false){
            $this->totals=array();
        }
		return $this;
	}

    /** Update totals on rows. Called at the start of formatRow() */
    function updateTotals(){
        foreach($this->totals as $key=>$val)
            $this->totals[$key]=$val+$this->current_row[$key];
    }

    /** Additional formatting for Totals row */
    function formatTotalsRow(){
        $this->formatRow();
        $this->hook('formatTotalsRow');

        $this->current_row['plural_s']=$this->current_row['row_count']>1?'s':'';
        if($this->current_row['row_count']==0){
            $this->current_row['row_count']='no';
            $this->current_row['plural_s']='s';
        }
    }

    protected $odd_even=null;
    function formatRow(){
        parent::formatRow();
        $this->odd_even=$this->odd_even=='odd'?'even':'odd';
        $this->current_row['odd_even']=$this->odd_even;
    }

    function renderRows(){
        $this->odd_even='';
        $this->template->del($this->container_tag);

        foreach($this->getIterator() as $this->current_id=>$this->current_row){
            @$this->totals['row_count']++;
            if($this->totals!==false)$this->updateTotals();
            $this->formatRow();
            $this->template->appendHTML($this->container_tag,$this->rowRender($this->row_t));
        }

        if($this->totals!==false && $this->totals_t){
            $this->current_row = $this->totals;
            $this->formatTotalsRow();
            $this->template->appendHTML($this->container_tag,$this->rowRender($this->totals_t));
        }
    }
    function render(){
        $this->renderRows();
        $this->output($this->template->render());
    }

    function defaultTemplate(){
        return array('view/completelister');
    }
}
