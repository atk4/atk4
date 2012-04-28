<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * This is a Basic Grid implementation, which produces fully
 * functional HTML grid capable of filtering, sorting, paginating
 * and using multiple column formatters. Basic Grid no longer
 * implements the column formatters, instead they have been
 * moved into Grid_Advanced
 * 
 * @link http://agiletoolkit.org/doc/grid
 *
 * Use:
 *  $grid=$this->add('Grid');
 *  $grid->setModel('User');
 *
 * @license See http://agiletoolkit.org/about/license
 *
**/
class Grid_Basic extends CompleteLister {
    public $columns=array();

    public $default_controller='Controller_MVCGrid';

    public $sort_icons=array(
        'ui-icon ui-icon-arrowthick-2-n-s',
        'ui-icon ui-icon-arrowthick-1-n',
        'ui-icon ui-icon-arrowthick-1-s',
    );

    function init(){
        parent::init();
        $this->initWidget();
    }

    /** You might want Grid to be enganced with a widget. Initialize it here or define this as empty function to avoid */
    function initWidget(){
    }

    function defaultTemplate(){
        return array('grid');
    }

    function importFields($model,$fields=undefined){
        $this->add('Controller_MVCGrid')->importFields($model,$fields);
    }

    function addColumn($formatters,$name=null,$descr=null){
        if($name===null){
            $name=$formatters;
            $formatters='text';
        }

        if($descr===null)$descr=ucwords(str_replace('_',' ',$name));

        $this->columns[$name]=array('type'=>$formatters);

        if(is_array($descr)){
            $this->columns[$name]=array_merge($this->columns[$name],$descr);
        } else {
            $this->columns[$name]['descr']=$descr;
        }

        $this->last_column=$name;

        if(is_callable($formatters)){
            $this->columns[$name]['fx']=$formatters;
            return $this;
        }

        $subtypes=explode(',',$formatters);
        foreach($subtypes as $subtype){
            if(!$this->hasMethod($m='init_'.$subtype)){
                if(!$this->hasMethod($m='format_'.$subtype)){
                    throw $this->exception('No such formatter')->addMoreInfo('formater',$subtype);
                }
            }else $this->$m($name,$descr);
        }

        return $this;
    }
    function precacheTemplate(){
        // pre-cache our template for row
        // $full=false used for certain row init
        $row = $this->row_t;
        $col = $row->cloneRegion('col');

        // tbody -> column
        $row->setHTML('row_id','<?$id?>');
        $row->trySetHTML('odd_even','<?$odd_even?>');
        $row->del('cols');

        // thead -> column
        $header = $this->template->cloneRegion('header');
        $header_col = $header->cloneRegion('col');
        $header_sort = $header_col->cloneRegion('sort');

        // Totals -> column
        if($t_row = $this->totals_t){
            $t_col = $t_row->cloneRegion('col');
            $t_row->del('cols');
        }

        $header->del('cols');

        foreach($this->columns as $name=>$column){
            $col->del('content');
            $col->setHTML('content','<?$'.$name.'?>');

            if(isset($t_row)){
                $t_col->del('content');
                $t_col->setHTML('content','<?$'.$name.'?>');
                $t_col->trySetHTML('tdparam','<?tdparam_'.$name.'?>style="white-space: nowrap"<?/?>');
                $t_row->appendHTML('cols',$t_col->render());
            }

            // some types needs control over the td

            $col->setHTML('tdparam','<?tdparam_'.$name.'?>style="white-space: nowrap"<?/?>');

            $row->appendHTML('cols',$col->render());

            $header_col->set('descr',$column['descr']);
            $header_col->trySet('type',$column['type']);

            // TODO: rewrite this (and move into Advanced)
            if(isset($column['sortable'])){
                $s=$column['sortable'];
                // calculate sortlink
                $l = $this->api->getDestinationURL(null,array($this->name.'_sort'=>$s[1]));

                $header_sort->trySet('order',$column['sortable'][0]);
                $header_sort->trySet('sorticon',$this->sort_icons[$column['sortable'][0]]);
                $header_sort->set('sortlink',$l);
                $header_col->setHTML('sort',$header_sort->render());
            }else{
                $header_col->del('sort');
                $header_col->tryDel('sort_del');
            }

            if($column['thparam']){
                $header_col->trySetHTML('thparam',$column['thparam']);
            }else{
                $header_col->tryDel('thparam');
            }

            $header->appendHTML('cols',$header_col->render());

        }
        $this->row_t = $this->api->add('SMlite');
        $this->row_t->loadTemplateFromString($row->render());

        if(isset($t_row)){
            $this->totals_t = $this->api->add('SMlite');
            $this->totals_t->loadTemplateFromString($t_row->render());
        }

        $this->template->setHTML('header',$header->render());
    }
    function formatRow(){
        if(!$this->columns)throw $this->exception('No columns defined for grid');

        foreach($this->columns as $tmp=>$column){ 
            $this->current_row[$tmp.'_original']=@$this->current_row[$tmp];

            $formatters = explode(',',$column['type']);
            foreach($formatters as $formatter){
                if(!$formatter)continue;
                if(method_exists($this,$m="format_".$formatter)){
                    $this->$m($tmp,$column);
                }else throw new BaseException("Grid does not know how to format type: ".$formatter);
            }
            // setting cell parameters (tdparam)
            $this->applyTDParams($tmp);
            if($this->current_row[$tmp]=='')$this->current_row[$tmp]=' ';
        }
        return $this->current_row;
    }
    function renderRows(){
        $this->precacheTemplate();
        parent::renderRows();

        if(!$this->totals['row_count']){
            $def_template = $this->defaultTemplate();
            $this->totals=false;
            $this->template->del('full_table');
        }else{
            $this->template->del('not_found');
        }
    }

    function format_shorttext($field){
        $text=$this->current_row[$field];
        //TODO counting words, tags and trimming so that tags are not garbaged
        if(strlen($text)>60)$text=substr($text,0,28).' ~~~ '.substr($text,-28);;
        $this->current_row[$field]=$text;
        $this->tdparam[$this->getCurrentIndex()][$field]['title']=$this->current_row[$field.'_original'];
    }

}
