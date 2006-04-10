<?
class QuickSearch extends Filter {
    /*
     * Quicksearch represents one-field filter which goes perfectly with a grid
     */
    function defaultTemplate(){
        return array('compact_form','form');
    }
    function init(){
        parent::init();
        $this->useDQ($this->owner->dq);
        $this->addField('Search','q');
        $this->addSubmit('Go');
    }
    function useFields($fields){
        $this->fields=$fields;
    }
    function applyDQ($dq){
        if(!($v=$this->get('q')))return;

        $v=addslashes($v);  // quote it

        $q=array();
        foreach($this->fields as $field){
            $q[]="$field like '%".$v."%'";
        }
        if($q){
            $dq->where(join(' or ',$q));
        }
    }
}
