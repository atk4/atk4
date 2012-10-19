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

    /** Set number of items displayed per page */
    function ipp($ipp){
        $this->ipp=$ipp;
        return $this;
    }
    /** Set a custom source. Must be an object with foundRows() method */
    function setSource($source){
        $this->skip=$this->learn('skip', @$_GET[$this->name.'_skip'])+0;
        if($source instanceof Model_Table){

            // Start iterating early
            $source = $source->_preexec();

            $source->limit($this->ipp,$this->skip);
            $source->calc_found_rows();

            $this->source=$source;

        }elseif($source instanceof DB_dsql){
            $source->_dsql()->calc_found_rows();

        }else{
            $this->source &= $source;
        }
    }
    function recursiveRender(){

        if(!$this->source){
            if($this->owner->model)$this->setSource($this->owner->model);
        }

        if(!isset($this->source))
            throw $this->exception('Unable to find source for Paginator');

        if($this->source instanceof DB_dsql){
            $this->source->preexec();
            $this->found_rows=$this->source->foundRows();
        }else{
            $this->found_rows=count($this->source);
        }

        $this->cur_page=floor($this->skip / $this->ipp) +1;
        $this->total_pages = ceil($this->found_rows / $this->ipp);

        if($this->cur_page>$this->total_pages){
            $this->cur_page=1;
            $this->skip=$this->ipp*($this->cur_page-1);
            $this->source->limit($this->ipp,$this->skip);
            $this->source->rewind();                 // re-execute the query
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
        return parent::recursiveRender();

    }
    function defaultTemplate(){
        return array('paginator42','paginator');
    }
    function defaultSpot(){
        return 'paginator';
    }
}
