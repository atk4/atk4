<?
class Paginator extends AbstractView {
    /*
     * Paginator is a class you should use when you need to separate lists into
     * several pages 
     */
    public $ipp=30;
    public $skip=0;
    public $range=4;

    private $template_chunks=array();
    private $region='';
    private $found_rows=null;
    private $total_pages=null;

    private $main_dq=null;
    function init(){
        parent::init();

        $this->grabTemplateChunk('link');
        $this->grabTemplateChunk('prev');
        $this->grabTemplateChunk('cur_item');
        $this->grabTemplateChunk('separator');
        $this->grabTemplateChunk('next');

        $this->skip=$this->learn('skip',
                $_GET[$this->name.'_skip'])+0;
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
        $this->main_dq=$dq;
        $this->main_dq->calc_found_rows();
        $this->main_dq->limit($this->ipp,$this->skip);
        return $this;
    }
    function region($region){
        //$this->region=$region;
        $this->template_chunks['link']->trySet('region',$region);
        return $this;
    }
    function render(){
        if(!isset($this->found_rows)){
            if(isset($this->main_dq)){
                $this->found_rows = $this->main_dq->foundRows();
            }
        }

        if(!isset($this->found_rows))
            $this->found_rows=1000;
            //return $this->fatal('Unknown number of rows');

        $this->cur_page=floor($this->skip / $this->ipp) +1;
        $this->total_pages = floor($this->found_rows / $this->ipp);
        if($this->cur_page>$this->total_pages)$this->cur_page=$this->total_pages+1;
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
            $this->output($this->template_chunks['cur_item']->set('item',$n)->set('total_items',$this->found_rows)->render());
        }else{
            $this->output($this->linkNoTemplate($n,array($this->name.'_skip'=>($n-1)*$this->ipp)));
        }
    }
    function link($chunk,$url_args,$tpl_args=array()){
        $this->template_chunks['link']->set(array(
                                            'link_url'=>$this->api->getDestinationURL(null,$url_args),
                                            'link_text'=>$this->template_chunks[$chunk]->set($tpl_args)->render()
                                           ));
        return $this->template_chunks['link']->render();
    }
    function linkNoTemplate($text,$url_args,$tpl_args=array()){
        $this->template_chunks['link']->set(array(
                                            'link_url'=>$this->api->getDestinationURL(null,$url_args),
                                            //'region'=>$this->region,
                                            'link_text'=>$text
                                           ));
        return $this->template_chunks['link']->render();
    }
}
