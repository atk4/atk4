<?php
class QuickSearch extends Filter {
    /*
     * Quicksearch represents one-field filter which goes perfectly with a grid
     */

    var $region=null;
    var $region_url=null;

    function defaultTemplate(){
        return array('compact_form','form');
    }
    function init(){
        parent::init();
        $this->useDQ($this->owner->dq);
        //on field change we should change a name of a button also: 'clear' in the name will clear fields
        $this->addField('Search','q','Find')->onKeyPress()->ajaxFunc($this->setGoFunc());
        $this->addButton('Clear','Clear')->submitForm($this);

        $this->onSubmit()->submitForm($this);
    }
    function setGoFunc(){
    	return "btn=document.getElementById('".$this->name.'_Clear'."'); btn.value='Go'; btn.name='".
    		$this->name."_go';";
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
        echo "here";
        exit;
        if(parent::submitted()){
            $a=$this->add('Ajax');
            $a->reload($this->owner);
            $a->execute();
        }
    }
}
