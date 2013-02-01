<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
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
    public $show_header = true;
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
        $this->add($this->default_controller)->importFields($model,$fields);
    }

    function addColumn($formatters,$name=null,$descr=null){
        if($name===null){
            $name=$formatters;
            $formatters='text';
        }

        if($descr===null)$descr=ucwords(str_replace('_',' ',$name));

        $descr=$this->api->_($descr);

        $this->columns[$name]=array('type'=>$formatters);

        if(is_array($descr)){
            $this->columns[$name]=array_merge($this->columns[$name],$descr);
        } else {
            $this->columns[$name]['descr']=$descr;
        }

        $this->last_column=$name;

        if(!is_string($formatters) && is_callable($formatters)){
            $this->columns[$name]['fx']=$formatters;
            return $this;
        }

        $subtypes=explode(',',$formatters);
        // TODO call addFormatter instead!
        foreach($subtypes as $subtype){
            if(strpos($subtype,'/')){

                if(!$this->elements[$subtype.'_'.$name]){
                    // add-on functionality
                    $addon=$this->api->normalizeClassName($subtype,'Controller_Grid_Format');
                    $this->elements[$subtype.'_'.$name]=$this->add($addon);
                }

                $addon = $this->getElement($subtype.'_'.$name);
                $addon->initField($name,$descr);
                return $addon;

            }elseif(!$this->hasMethod($m='init_'.$subtype)){
                if(!$this->hasMethod($m='format_'.$subtype)){
                    throw $this->exception('No such formatter')->addMoreInfo('formater',$subtype);
                }
            }else $this->$m($name,$descr);
        }

        return $this;
    }
    function addFormatter($field,$formatter,$options=null){
        /*
         * add extra formatter to existing field
         */
        if(!isset($this->columns[$field])){
            throw new BaseException('Cannot format nonexistant field '.$field);
        }
        if($this->columns[$field]['type']){
            $this->columns[$field]['type'].=','.$formatter;
        }else{
            $this->columns[$field]['type']=$formatter;
        }

        if($options){
            $this->columns[$field]=array_merge($this->columns[$field],$options);
        }
        $descr = $this->columns[$field];


        if(strpos($formatter,'/')){

            if(!$this->elements[$formatter.'_'.$field]){
                // add-on functionality
                $addon=$this->api->normalizeClassName($formatter,'Controller_Grid_Format');
                $this->elements[$formatter.'_'.$field]=$this->add($addon,$formatter);
            }

            $addon = $this->getElement($formatter.'_'.$field);
            $addon->initField($field,$descr);
            return $addon;

        }elseif($this->hasMethod($m='init_'.$formatter))$this->$m($field, $descr);
        return $this;
    }
    function setFormatter($field,$formatter){
        /*
         * replace current formatter for field
         */
        if(!isset($this->columns[$field])){
            throw new BaseException('Cannot format nonexistant field '.$field);
        }
        $this->columns[$field]['type']='';
        $this->addFormatter($field,$formatter);
        $this->last_column=$field;
        return $this;
    }
    function precacheTemplate(){
        // pre-cache our template for row
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

            if(isset($t_row) && isset($t_col)){
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
                //$l = $this->api->url(null,array($this->name.'_sort'=>$s[1]));

                $header_sort->trySet('order',$s[0]);
                $header_sort->trySet('sorticon',$this->sort_icons[$s[0]]);
                $header_col->trySet('sortid',$sel=$this->name.'_sort_'.$name);

                $this->js('click',$this->js()->reload(array($this->name.'_sort'=>$s[1])))
                    ->_selector('#'.$sel);
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

        if(isset($t_row) && $this->totals_t){
            $this->totals_t = $this->api->add('SMlite');
            $this->totals_t->loadTemplateFromString($t_row->render());
        }
        if ($this->show_header){
            $this->template->setHTML('header',$header->render());
        } else {
            $this->template->setHTML('header', '');
        }
    }
    function formatRow(){
        if(!$this->columns)throw $this->exception('No columns defined for grid');

        foreach($this->columns as $tmp=>$column){ 
            $this->current_row[$tmp.'_original']=@$this->current_row[$tmp];
            
            // if model field has listData structure, then get value instead of key
            if($this->model && $f=$this->model->hasElement($tmp)){
                if($values=$f->listData())
                    $this->current_row[$tmp] = $values[$this->current_row[$tmp]];
            }

            $formatters = explode(',',$column['type']);
            foreach($formatters as $formatter){
                if(!$formatter)continue;
                if(strpos($formatter,'/')){
                    $this->getElement($formatter.'_'.$tmp)->formatField($tmp,$column);
                }elseif($this->hasMethod($m="format_".$formatter)){
                    $this->$m($tmp,$column);
                }else throw new BaseException("Grid does not know how to format type: ".$formatter);
            }
            // setting cell parameters (tdparam)
            $this->applyTDParams($tmp);
            if($this->current_row[$tmp]=='')$this->current_row[$tmp]=' ';
        }
        $this->hook('formatRow');
        return $this->current_row;
    }
    function applyTDParams(){}
    function renderRows(){
        $this->precacheTemplate();
        parent::renderRows();

        // if no rows, then remove totals, otherwise remove not_found message
        if(!$this->total_rows) {
        }else{
            $this->template->del('not_found');
        }
    }

    function format_text($field){
    }

}
