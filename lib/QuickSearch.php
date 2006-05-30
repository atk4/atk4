<?
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
        $this->addField('Search','q','Find');
        $this->addButton('Go')->submitForm($this);

        $this->onSubmit()->submitForm($this);
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
            $a=$this->add('Ajax');
            if(!$this->region){
                $a->redirect();
            }else{
                if($this->region_url){
                    $a->loadRegionURL($this->region,$this->region_url);
                }else{
                    $a->reloadRegion($this->region);
                }
            }
            $a->execute();
        }
    }
}
