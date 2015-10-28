<?php // vim:ts=4:sw=4:et:fdm=marker
/*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * Paginator needs to have source set (which can be either Model,
 * DSQL or Array). It will render itself into parent and will
 * limit the source to display limited number of records per page
 * with ability to travel back and forth
 */
class Paginator_Basic extends CompleteLister {
    public $ipp=30;         // By default, show 30 records per page
    public $skip=0;         // By default, do not skip anything
    public $range=4;        // Display 4 adjacent pages from current one

    public $ajax_reload=true;   // Reload parent with AJAX
    public $memorize=true;      // Remember page, when user comes back
    public $skip_var=null;      // Argument to use to specify page

    public $source=null;        // Set with setSource()
    public $base_page=null;     // let's redefine page nicely

    /**
      * use_calc_found_rows instructs how to calculate total number of rows
      * in this model. By default (null) $model->count() will be used for
      * models and use_calc_found_rows will be used for DSQL data sources.
      */
    public $use_calc_found_rows = null; // This can be painfully slow sometimes
        //

    function init(){
        parent::init();

        if (!$this->skip_var) {
            $this->skip_var = $this->name . '_skip';
        }
        $this->skip_var = $this->_shorten($this->skip_var);
    }

    /** Set number of items displayed per page */
    function setRowsPerPage($rows) {
        $this->ipp = $rows;
        return $this;
    }
    // obsolete, should be removed in 4.4
    function ipp($rows){
        return $this->setRowsPerPage($rows);
    }
    /** Set a custom source. Must be an object with foundRows() method */
    function setSource($source){
        if($this->memorize){
            if (isset($_GET[$this->skip_var])){
                $this->skip=$this->memorize('skip', (int)$_GET[$this->skip_var]);
            } else {
                $this->skip=(int)$this->recall('skip');
            }
        }else{
            $this->skip=@$_GET[$this->skip_var]+0;
        }

        // Start iterating early ($source = DSQL of model)
        if($source instanceof SQL_Model){
            if(is_null($this->use_calc_found_rows))$this->use_calc_found_rows=false;
            $source = $source->_preexec();
        }

        if($source instanceof DB_dsql){
            $source->limit($this->ipp, $this->skip);
            if($this->use_calc_found_rows !== false)$source->calcFoundRows();
            $this->source = $source;

        }elseif($source instanceof Model){
            $this->source = $source->setLimit($this->ipp,$this->skip);

        }else{
            // NOTE: no limiting enabled for unknown data source
            $this->source =& $source;
        }
    }
    function recursiveRender(){

        // get data source
        if (! $this->source) {

            // force grid sorting implemented in Grid_Advanced
            if($this->owner instanceof Grid_Advanced) {
                $this->owner->getIterator();
            }

            // set data source for Paginator
            if ($this->owner->model) {
                $this->setSource($this->owner->model);
            } elseif ($this->owner->dq) {
                $this->setSource($this->owner->dq);
            } else {
                throw $this->exception('Unable to find source for Paginator');
            }
        }

        // calculate found rows
        if($this->source instanceof DB_dsql){
            $this->source->preexec();
            $this->found_rows=$this->source->foundRows();
        }elseif($this->source instanceof Model){
            $this->found_rows=(string)$this->source->count();
        }else{
            $this->found_rows=count($this->source);
        }

        // calculate current page and total pages
        $this->cur_page=floor($this->skip / $this->ipp) +1;
        $this->total_pages = ceil($this->found_rows / $this->ipp);

        if($this->cur_page>$this->total_pages || ($this->cur_page==1 && $this->skip!=0)){
            $this->cur_page=1;
            if($this->memorize){
                $this->memorize('skip',$this->skip=0);
            }
            if($this->source instanceof DB_dsql){
                $this->source->limit($this->ipp,$this->skip);
                $this->source->rewind();                 // re-execute the query
            }elseif($this->source instanceof Model){
                $this->source->setLimit($this->ipp,$this->skip);
            }else{
                // Imants: not sure if this is correct, but it was like this before
                $this->source->setLimit($this->ipp,$this->skip);
            }
        }

        // no need for paginator if there is only one page
        if($this->total_pages<=1)return $this->destroy();

        if($this->cur_page>1){
            $this->add('View',null,'prev')
                ->setElement('a')
                ->setAttr('href',$this->api->url($this->base_page,$u=array($this->skip_var=>
                    $pn=max(0,$this->skip-$this->ipp)
                )))
                ->setAttr('data-skip',$pn)
                ->set('« Prev')
                ;
        }else($this->template->tryDel('prev'));

        if($this->cur_page<$this->total_pages){
            $this->add('View',null,'next')
                ->setElement('a')
                ->setAttr('href',$this->api->url($this->base_page,$u=array($this->skip_var=>
                    $pn=$this->skip+$this->ipp
                )))
                ->setAttr('data-skip',$pn)
                ->set('Next »')
                ;
        }else($this->template->tryDel('next'));

        // First page
        if($this->cur_page>$this->range+1){
            $this->add('View',null,'first')
                ->setElement('a')
                ->setAttr('href',$this->api->url($this->base_page,$u=array($this->skip_var=>
                    $pn=max(0,0)
                )))
                ->setAttr('data-skip',$pn)
                ->set('1')
                ;
            if($this->cur_page>$this->range+2){
                $this->add('View',null,'points_left')
                    ->setElement('span')
                    ->set('...')
                    ;
            }
        }

        // Last page
        if($this->cur_page<$this->total_pages-$this->range){
            $this->add('View',null,'last')
                ->setElement('a')
                ->setAttr('href',$this->api->url($this->base_page,$u=array($this->skip_var=>
                    $pn=max(0,($this->total_pages-1)*$this->ipp)
                )))
                ->setAttr('data-skip',$pn)
                ->set($this->total_pages)
                ;
            if($this->cur_page<$this->total_pages-$this->range-1){
                $this->add('View',null,'points_right')
                    ->setElement('span')
                    ->set('...')
                    ;
            }
        }

        // generate source for Paginator Lister (pages, links, labels etc.)
        $data=array();

        //setting cur as array seems not working in atk4.3. String is working
        $tplcur = $this->template->get('cur');
        $tplcur = (isset($tplcur[0])) ? $tplcur[0] : '';

        foreach(range(max(1,$this->cur_page-$this->range), min($this->total_pages, $this->cur_page+$this->range)) as $p)
        {
        	$data[]=array(
                'href'=>$this->api->url($this->base_page,array($this->skip_var=>$pn=($p-1)*$this->ipp)),
                'pn'=>$pn,
                'cur'=>$p==$this->cur_page?$tplcur:'',
                'label'=>$p
            );
        }

        if($this->ajax_reload){
            $this->js('click',$this->owner->js()->reload(array($this->skip_var=>$this->js()->_selectorThis()->attr('data-skip'))))
                ->_selector('#'.$this->name.' a');
        }

        parent::setSource($data);
        return parent::recursiveRender();
    }
    function defaultTemplate(){
        return array('paginator42','paginator');
    }
    function defaultSpot(){
        return 'Paginator';
    }
}
