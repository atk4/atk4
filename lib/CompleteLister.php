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
**/
class CompleteLister extends Lister {

	protected $row_t;

    /** Will contain accumulated totals for all fields */
	public $totals=false;

    /** Will be initialized to "totals" template when addTotals() is called */
	public $totals_t=false;

	function init(){
		parent::init();
        if(!$this->template->is_set('row'))
            throw $this->exception('Template must have "row" tag');

		$this->row_t=$this->template->cloneRegion('row');
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
        @$this->totals['row_count']++;
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

    function render(){
        $this->odd_even='';
        $this->template->del('rows');

        foreach($this->getIterator() as $this->current_row){
            if($this->totals!==false)$this->updateTotals();
            $this->formatRow();
            $this->template->append('rows',$this->rowRender($this->row_t));
        }

        if($this->totals!==false && $this->totals_t){
            $this->current_row = $this->totals;
            $this->formatTotalsRow();
            $this->template->append('rows',$this->rowRender($this->totals_t));
        }

        $this->output($this->template->render());
    }

    function defaultTemplate(){
        return array('view/completelister');
    }
}
