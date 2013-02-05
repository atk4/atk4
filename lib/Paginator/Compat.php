<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Implements ability for a Grids (or listers) to divide their rows into multiple
 * pages. Usage: $grid->addPaginator(20);
 *
 * @link http://agiletoolkit.org/learn/understand/view/usage
 * @link http://agiletoolkit.org/learn/template
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class Paginator_Compat extends AbstractView {
    /*
     * Paginator is a class you should use when you need to separate lists into
     * several pages
     */
    public $ipp=30;
    public $skip=0;
    public $range=4;

    protected $template_chunks=array();
    protected $region='';
    protected $object='';
    protected $found_rows=null;
    protected $total_pages=null;

    protected $limiters=array();
    function init(){
        parent::init();

        $this->grabTemplateChunk('link');
        $this->grabTemplateChunk('prev');
        $this->grabTemplateChunk('cur_item');
        $this->grabTemplateChunk('separator');
        $this->grabTemplateChunk('next');
        $this->skip=$this->learn('skip',
                @$_GET[$this->name.'_skip'])+0;

        $this->api->addHook('pre-exec',array($this,'applyHook'), array(), 1);
    }
    function applyHook(){
        if(isset($this->owner->dq)){
            $this->limiters[]=$this->owner->dq;
        }elseif(isset($this->owner->data)){
            $this->limiters[]=&$this->owner->data;
        }
        foreach($this->limiters as $key=>$dq){
            if($this->limiters[$key] instanceof DB_dsql)$this->applyDQ($this->limiters[$key]);
        }
    }
    function applyDQ($dq){
        $dq->calcFoundRows();
        $dq->limit($this->ipp,$this->skip);
    }
    function applyData(&$data){
        $data=array_slice($data,$this->skip,$this->ipp);
    }
    function defaultTemplate(){
        return array('paginator','paginator');
    }
    function grabTemplateChunk($name){
        if($this->template->is_set($name)){
            $this->template_chunks[$name] = $this->template->cloneRegion($name);
        }else{
            // hmm.. i wonder what ? :)
        }
    }
    function ipp($ipp){
        $this->ipp=$ipp;
        return $this;
    }
    function pageRange($pageRange){
        $this->range=$pageRange;
        return $this;
    }
    function useDQ($dq){
        if($dq===$this->owner->dq){
            throw new ObsoleteException("You shouldn't use useDQ for Paginator if you are adding it into parent. This metod can be used to add additional DQ's controlled by this paginator. This is useful if you are having more than one lister controlled by paginator.");
        }
        $this->limiters[]=$dq;
        return $this;
    }
    function region($region){
        //$this->region=$region;
        $this->template_chunks['link']->trySet('region',$region);
        return $this;
    }
    function cutObject($object){
        $this->template_chunks['link']->trySet('object',$object);
        return $this;
    }
    function render(){
        if(!isset($this->found_rows)){
            if(isset($this->limiters[0])){
                if($this->limiters[0] instanceof DB_dsql)$this->found_rows = $this->limiters[0]->foundRows();
                else $this->found_rows=sizeof($this->limiters[0]);
            }
        }
        if(!isset($this->found_rows))
            $this->found_rows=1000;

        $this->cur_page=floor($this->skip / $this->ipp) +1;
        $this->total_pages = ceil($this->found_rows / $this->ipp);

        if($this->cur_page>$this->total_pages){
            // We are on a wrong page. Recalculate everything.
            $this->cur_page=1;
            $this->skip=$this->ipp*($this->cur_page-1);
            foreach($this->limiters as $key=>$l){
                if($l instanceof DB_dsql){
                    $this->limiters[$key]->limit($this->ipp,$this->skip);
                    $this->limiters[$key]->do_select();
                }
            }
        }
        // static source should be filtered here, after all calculations
        if(is_array($this->limiters[0]))$this->applyData($this->limiters[0]);

        //displaying only there is more than 1 page
        if($this->total_pages<=1)return;

        $s = $this->template_chunks['separator']->render();

        // left
        if($this->cur_page==1){
            $this->output($this->template_chunks['prev']->render());
        }else{
            $this->output($this->link('prev',array($this->name.'_skip'=>max(0,$this->skip-$this->ipp))));
        }
        $this->output(' ');

        // first
        $this->outputNum(1);
        if($this->total_pages>1){
            //$this->output($s);

            $range = $this->range;
            // first_in_range
            $left=$this->cur_page-$range;
            $right=$this->cur_page+$range;

            if($left<3)$left=2;else $this->output(' .. ');
            for($n=$left;$n<=$right;$n++){
                if($n>$left || $left==2)$this->output($s);
                $this->outputNum($n);
                if($n==$this->total_pages)break;
            }
            if($n<$this->total_pages){
                $this->output(' .. ');
                $this->outputNum($this->total_pages);
            }
            $this->output(' ');
        }else{
            $this->output(' ');
        }
        if($this->cur_page>=$this->total_pages){
            $this->output($this->template_chunks['next']->render());
        }else{
            $this->output($this->link('next',array($this->name.'_skip'=>$this->skip+$this->ipp)));
        }
    }
    function outputNum($n){
        if($n==$this->cur_page){
            $this->output($this->template_chunks['cur_item']->trySet('item',$n)->trySet('total_items',$this->found_rows)->render());
        }else{
            $this->output($this->linkNoTemplate($n,array($this->name.'_skip'=>($n-1)*$this->ipp)));
        }
    }
    function linkUrl($url_args = array()){
        return $this->api->url(null, $url_args);
    }
    function link($chunk,$url_args,$tpl_args=array()){
        $this->template_chunks['link']->setHTML(array(
                    'link_url'=>$this->linkUrl($url_args),
                    'link_text'=>$this->template_chunks[$chunk]->set($tpl_args)->render()
                    ));
        return $this->template_chunks['link']->render();
    }
    function linkNoTemplate($text,$url_args,$tpl_args=array()){
        $this->template_chunks['link']->set(array(
                    'link_url'=>$this->linkUrl($url_args),
                    //'region'=>$this->region,
                    'link_text'=>$text
                    ));
        return $this->template_chunks['link']->render();
    }
}
