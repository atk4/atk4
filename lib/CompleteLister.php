<?php
class CompleteLister extends Lister {
    public $totals=false;
    protected $row_t;
    protected $totals_t=false;
    function init(){
        parent::init();
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
            if(is_string($val))$this->totals[$key]+=strip_tags($val);
            $this->api->logger->logVar(strip_tags($val));
        }
        $this->totals['row_count']++;
    }
    function formatTotalsRow(){
        $this->formatRow();
        $this->totals['plural_s']=$this->totals['row_count']>1?'s':'';
        if($this->totals['row_count']==0){
            $this->totals['row_count']='no';
            $this->totals['plural_s']='s';
        }
    }
    function render(){
        $oe='';
        $this->template->del('rows');
        while($this->fetchRow()){
            $oe=$oe=='odd'?'even':'odd';
            if($this->totals!==false)$this->updateTotals();
            $this->formatRow();
            $this->row_t->set($this->current_row);
            $this->row_t->trySet('odd_even',$oe);
            $this->template->append('rows',$this->row_t->render());
        }
        if($this->totals!==false && $this->totals_t){ 
            $t = $this->totals_t;
            $this->current_row = $this->totals;
            $this->formatTotalsRow();
            $t->set($this->current_row);
            $this->template->append('rows',$t->render());
        }
        $this->output($this->template->render());
    }
}
