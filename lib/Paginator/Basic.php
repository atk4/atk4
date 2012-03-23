<?php
/**
 * Paginator needs to have source set (which can be
 * either dq or array). It will render itself into
 * parent
 */
class Paginator_Basic extends CompleteLister {
	public $ipp=30;
	public $skip=0;
	public $range=4;

    public $source=null;

    function init(){
        parent::init();
		$this->api->addHook('pre-exec',array($this,'applyHook'), 1);

		$this->skip=$this->learn('skip', @$_GET[$this->name.'_skip'])+0;
    }
    /** Set number of items displayed per page */
    function ipp($ipp){
        $this->ipp=$ipp;
        return $this;
    }
    /** Specify DQ object */
    function setSource(&$s){
        $s->calc_found_rows();
        $this->source =& $s;
    }
    function applyHook(){
        if(!isset($this->source)){
            if($this->owner->dq){
                $this->setSource($this->owner->dq);
            }
        }
        if(!isset($this->source))
            throw $this->exception('Unable to find source for Paginator');

        if($this->source instanceof DB_dsql || $this->source instanceof Model_Array){

            // Set the limit first, then execute
            $this->source->limit($this->ipp,$this->skip);
            if(!$this->source->stmt)$this->source->execute();       // execute early but not fetch
            $this->found_rows=$this->source->foundRows();
            $this->cur_page=floor($this->skip / $this->ipp) +1;
            $this->total_pages = ceil($this->found_rows / $this->ipp);

            if($this->cur_page>$this->total_pages){
                $this->cur_page=1;
                $this->skip=$this->ipp*($this->cur_page-1);
                $this->source->limit($this->ipp,$this->skip);
                $this->source->rewind()->execute();                 // re-execute the query
            }


        }else{
            // TODO: array_slice
            $this->found_rows=count($this->source);
        }

        if($this->total_pages<=1)return $this->destroy();


        if($this->cur_page>1){
            $this->add('View',null,'prev')
                ->setElement('a')
                ->setAttr('href',$this->api->url(null,$u=array($this->name.'_skip'=>
                    $pn=max(0,$this->skip-$this->ipp)
                )))
                ->setAttr('data-skip',$pn)
                ->set('← Prev')
                ;
        }
        if($this->cur_page<$this->total_pages){
            $this->add('View',null,'next')
                ->setElement('a')
                ->setAttr('href',$this->api->url(null,$u=array($this->name.'_skip'=>
                    $pn=$this->skip+$this->ipp
                )))
                ->setAttr('data-skip',$pn)
                ->set('Next →')
                ;
        }
        //
        // generate our source now
        $data=array();

        foreach(range(max(1,$this->cur_page-$this->range), min($this->total_pages, $this->cur_page+$this->range)) as $p){
            $data[]=array(
                'href'=>$this->api->url(null,array($this->name.'_skip'=>$pn=($p-1)*$this->ipp)),
                'pn'=>$pn,
                'cur'=>$p==$this->cur_page?$this->template->get('cur'):'',
                'label'=>$p
            );
        } 

        $this->js('click',$this->owner->js()->reload(array($this->name.'_skip'=>$this->js()->_selectorThis()->attr('data-skip'))))->_selector('#'.$this->name.' a');


        parent::setSource($data);

    }
    function defaultTemplate(){
        return array('paginator42','paginator');
    }
    function defaultSpot(){
        return 'paginator';
    }
}
