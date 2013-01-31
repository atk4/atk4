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
 * and using multiple column formatters.
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
class Grid_Advanced extends Grid_Basic {
    protected $no_records_message="No matching records to display";

    public $last_column;
    public $sortby='0';
    public $sortby_db=null;
    public $buttonset=null;

    private $totals_title_field=null;
    private $totals_title="";
    public $totals_t=null;
    public $totals_value_na = '-';
    public $data=null;

    private $record_order=null;

    public $title_col=array();

    public $paginator=null;

    /**
     * $tdparam property is an array with cell parameters specified in td tag.
     * This should be a hash: 'param_name'=>'param_value'
     * Following parameters treated and processed in a special way:
     * 1) 'style': nested array, style parameter. items of this nested array converted to a form of
     *       style: style="param_name: param_value; param_name: param_value"
     * 2) OBSOLTE! wrap: possible values are true|false; if true, 'wrap' is added
     *      use style/white-space property or simply format_wrap()
     *
     * All the rest are not checked and converted to a form of param_name="param_value"
     *
     * This is a tree-like array with the following structure:
     * array(
     *      [level1]=>dataset_row=array(
     *          [level2]=>field=array(
     *              [level3]=>tdparam_elements=array(
     *                  param_name=>param_value
     *              )
     *          )
     *      )
     * )
     */
    protected $tdparam=array();

    public $js_widget='ui.atk4_grid';
    public $js_widget_arguments=array();

    public $default_controller='MVCGrid';

    function init(){
        parent::init();
        //$this->add('Reloadable');
        //$this->api->addHook('pre-render',array($this,'precacheTemplate'));

        $this->sortby=$this->learn('sortby',@$_GET[$this->name.'_sort']);
    }
    function importFields($model,$fields=undefined){
        $this->add('Controller_MVCGrid')->importFields($model,$fields);
    }
    function defaultTemplate(){
        return array('grid');
    }
    /**
     * Returns the ID value of the expanded content on the basis of GET parameters
     */
    function getExpanderId(){
        return $_GET['expanded'].'_expandedcontent_'.$_GET['id'];
    }
    function getColumn($column){
        $this->last_column=$column;
        return $this;
    }
    function hasColumn($column){
        if(!is_string($column))throw $this->exception('WRong');
        return isset($this->columns[$column]);
    }
    function removeColumn($name){
        unset($this->columns[$name]);
        if($this->last_column==$name)$this->last_column=null;
        return $this;
    }
    function addButton($label){
        if(!$this->buttonset)
            $this->buttonset=$this->add('ButtonSet',null,'grid_buttons');
        return $this->buttonset
            ->add('Button','gbtn'.count($this->elements))
            ->setLabel($label);
    }
    function addQuickSearch($fields,$class='QuickSearch'){
        return $this->add($class,null,'quick_search')
            ->useWith($this)
            ->useFields($fields);
    }
    function makeSortable($db_sort=null){
        // Sorting
        $reverse=false;
        if($db_sort[0]=='-'){
            $reverse=true;
            $db_sort=substr($db_sort,1);
        }
        if(!$db_sort)$db_sort=$this->last_column;

        if($this->sortby==$this->last_column){
            // we are already sorting by this column
            $info=array('1',$reverse?0:("-".$this->last_column));
            $this->sortby_db=$db_sort;
        }elseif($this->sortby=="-".$this->last_column){
            // We are sorted reverse by this column
            $info=array('2',$reverse?$this->last_column:'0');
            $this->sortby_db="-".$db_sort;
        }else{
            // we are not sorted by this column
            $info=array('0',$reverse?("-".$this->last_column):$this->last_column);
        }
        $this->columns[$this->last_column]['sortable']=$info;

        return $this;
    }
    function makeTitle(){
        $this->title_col[]=$this->last_column;
        return $this;
    }
    function render(){
        if($this->js_widget){
            $fn=str_replace('ui.','',$this->js_widget);
            $this->js(true)->_load($this->js_widget)->$fn($this->js_widget_arguments);
        }
        return parent::render();
    }
    function format_number($field){
    }
    function format_shorttext($field){
        $text=$this->current_row[$field];
        if(strlen($text)>60) {
            // Not sure about multi-byte support and execution speed for this
            $a = explode(PHP_EOL,wordwrap($text,28,PHP_EOL,true),2);
            $b = explode(PHP_EOL,wordwrap(strrev($text),28,PHP_EOL,true),2);
            $text = $a[0] . ' ~~~ ' . strrev($b[0]);
        }
        $this->current_row[$field]=$text;
        $this->tdparam[$this->getCurrentIndex()][$field]['title']=$this->current_row[$field.'_original'];
    }

    function format_html($field){
        $this->current_row_html[$field] = $this->current_row[$field];
    }
    function init_money($field){
        @$this->columns[$field]['thparam'].=' style="text-align: right"';
    }
    function init_real($field){
        @$this->columns[$field]['thparam'].=' style="text-align: right"';
    }
    function init_fullwidth($field){
        @$this->columns[$field]['thparam'].=' style="width: 100%"';
    }
    function format_fullwidth($field){}
    function format_money($field){
        $m=(float)$this->current_row[$field];
        $this->current_row[$field]=number_format($m,2);
        $this->setTDParam($field,'style/color',$m<0?'red':null);
        $this->setTDParam($field,'align','right');
    }
    function format_totals_number($field){
        return $this->format_number($field);
    }
    function format_totals_money($field){
        return $this->format_money($field);
    }
    function format_totals_real($field){
        return $this->format_real($field);
    }
    function format_totals_text($field){
        // This method is mainly for totals title displaying
        if($field==$this->totals_title_field){
            $this->setTDParam($field,'style/font-weight','bold');
            //$this->current_row[$field]=$this->totals_title.':';
        }
        else $this->current_row[$field]=$this->totals_value_na;
    }
    function format_time($field){
        $this->current_row[$field]=date($this->api->getConfig('locale/time','H:i:s'),
                strtotime($this->current_row[$field]));
    }
    function format_date($field){
        if(!$this->current_row[$field])$this->current_row[$field]='-'; else
            $this->current_row[$field]=date($this->api->getConfig('locale/date','d/m/Y'),
                    strtotime($this->current_row[$field]));
    }
    function format_datetime($field){
        if(!$this->current_row[$field])$this->current_row[$field]='-'; else
            $this->current_row[$field]=date($this->api->getConfig('locale/datetime','d/m/Y H:i:s'),
                    strtotime($this->current_row[$field]));
    }
    function format_timestamp($field){
        if(!$this->current_row[$field])$this->current_row[$field]='-';
        else{
            $format=$this->api->getConfig('locale/timestamp',$this->api->getConfig('locale/datetime','d/m/Y H:i:s'));
            $this->current_row[$field]=date($format,strtotime($this->current_row[$field]));
        }
    }
    function format_nowrap($field){
        $this->tdparam[$this->getCurrentIndex()][$field]['style']='white-space: nowrap';
    }
    function format_wrap($field){
        $this->tdparam[$this->getCurrentIndex()][$field]['style']='white-space: wrap';
    }
    function format_template($field){
        if(!($t=$this->columns[$field]['template'])){
            throw new BaseException('use setTemplate() for field '.$field);
        }
        $this->current_row_html[$field]=$t
            ->set($this->current_row)
            ->trySet('_value_',$this->current_row[$field])
            ->render();
    }
    function format_widget($field, $widget, $params=array(), $widget_json=null){
        $class=$this->name.'_'.$field.'_expander';
        $params=array(
                'class'=>$class."_".$field." $widget lister_cell"
                )+$params;
        $this->js(true)->_tag('.'.$class.'_'.$field)->_load($widget)->activate($widget_json);
        /*
           $this->api->add('jUI')->addWidget($widget)->activate('.'.$class.'_'.$field,$widget_json);
         */
        $this->tdparam[$this->getCurrentIndex()][$field]=$params;
        if(!$this->current_row[$field]){
            $this->current_row[$field]=$this->columns[$field]['descr'];
        }
    }
    function format_expander_widget($field,$column){
        return $this->format_expander($field,$column);
    }
    function format_expander($field, $column){
        $class=$this->name.'_'.$field.'_expander';
        if(!@$this->current_row[$field]){
            $this->current_row[$field]=$column['descr'];
        }
        // TODO: 
        // reformat this using Button, once we have more advanced system to bypass rendering of
        // sub-elements.
        // $this->current_row[$field]=$this->add('Button',null,false)
        //  ->
        //
        @$this->current_row_html[$field]='<input type="checkbox" class="button_'.$field.' '.$class.'"
            id="'.$this->name.'_'.$field.'_'.$this->prepareIdField($this->model->id).'"
            rel="'.$this->api->url($column['page']?$column['page']:'./'.$field,
            array('expander'=>$field,
                    'cut_page'=>1,
                    'expanded'=>$this->name,

                    // TODO: id is obsolete
                    'id'=>$this->model->id,
                    $this->columns[$field]['refid'].'_id'=>$this->model->id
                )
                ).'"
                /><label for="'.$this->name.'_'.$field.'_'.$this->prepareIdField($this->model->id).'">'.
                $this->current_row[$field].'</label>';
    }
    function prepareIdField($val){
        return $this->api->normalizeName($val);
    }
    function init_expander_widget($field){
        @$this->columns[$field]['thparam'].=' style="width: 40px; text-align: center"';
        return $this->init_expander($field);
    }
    function init_expander($field){
        @$this->columns[$field]['thparam'].=' style="width: 40px; text-align: center"';
        $this->js(true)->find('.button_'.$field)->button();

        if(!isset($this->columns[$field]['refid'])){
            // TODO: test

            /*
            $refid=$this->getController();
            if($refid)$refid=$refid->getModel();
             */
            $refid=$this->model;
            //if($refid)$refid=$refid->entity_code;
            if($refid)$refid=$refid->getEntityCode();//Get Protected property Model::entity_code
            if($refid){
                $this->columns[$field]['refid']=$refid;
            }else{

                if($this->dq)
                    $refid=$this->dq->args['table'];

                if(!$refid)$refid=preg_replace('/.*_/','',$this->api->page);

                $this->columns[$field]['refid']=$refid;
            }
        }


        $class=$this->name.'_'.$field.'_expander';
        $this->js(true)->_selector('.'.$class)->_load('ui.atk4_expander')->atk4_expander();
    }
    function format_inline($field, $idfield='id'){
        /**
         * Formats the InlineEdit: field that on click should substitute the text
         * in the columns of the row by the edit controls
         *
         * The point is to set an Id for each column of the row. To do this, we should
         * set a property showing that id should be added in prerender
         */
    }
    function format_nl2br($field) {
        $this->current_row[$field] = nl2br($this->current_row[$field]);
    }
    function format_order($field, $idfield='id'){
        $n=$this->name.'_'.$field.'_'.$this->current_row[$idfield];
        $this->tdparam[$this->getCurrentIndex()][$field]=array(
                'id'=>$n,
                'style'=>array(
                    'cursor'=>'hand'
                    )
                );
        $this->current_row[$field]=$this->record_order->getCell($this->current_id);
    }
    function init_link($field){
        $this->setTemplate('<a href="<?'.'$_link?'.'>"><?'.'$'.$field.'?'.'></a>');
    }
    function format_password($field){
        $this->current_row[$field]='***';
    }
    function format_link($field){
        $this->current_row['_link']=$this->api->url('./'.$field,array('id'=>$this->current_id));
        return $this->format_template($field);
        /*
           $this->current_row[$field]='<a href="'.$this->api->url($field,
           array('id'=>$this->current_id)).'">'.
           $this->columns[$field]['descr'].'</a>';
         */
    }
    function _performDelete($id){
        if($this->model){
            return $this->model->delete($id);
        }
        $this->dq->where('id',$id)->do_delete();
    }
    function format_delete($field){
        if(!$this->model)throw new BaseException('delete column requires $dq to be set');
        if($id=@$_GET[$this->name.'_'.$field]){
            // this was clicked
            $this->_performDelete($id);
            $this->js()->univ()->successMessage('Deleted Successfully')->getjQuery()->reload()->execute();
        }
        return $this->format_confirm($field);
    }
    function init_button($field){
        @$this->columns[$field]['thparam'].=' style="width: 40px; text-align: center"';
        $this->js(true)->find('.button_'.$field)->button();
    }
    function setButtonClass($class){
        $this->columns[$this->last_column]['button_class']=$class;
    }
    function init_delete($field){
        $this->columns[$field]['button_class']='red';
        $g=$this;
        $this->api->addHook('post-init',array($this,'_move_delete'),array($field));
        /*function() use($g,$field){
          });
         */
        return $this->init_confirm($field);
    }
    function _move_delete($grid,$field){
        if($this->hasColumn($field))$this->addOrder()->move($field,'last')->now();
    }
    function init_confirm($field){
        @$this->columns[$field]['thparam'].=' style="width: 40px; text-align: center"';
        $this->js(true)->find('.button_'.$field)->button();
    }
    function init_prompt($field){
        @$this->columns[$field]['thparam'].=' style="width: 40px; text-align: center"';
        $this->js(true)->find('.button_'.$field)->button();
    }
    function format_button($field){
        $this->current_row_html[$field]='<button type="button" class="'.$this->columns[$field]['button_class'].'button_'.$field.'" '.
            'onclick="$(this).univ().ajaxec(\''.$this->api->url(null,
            array($field=>$this->current_id,$this->name.'_'.$field=>$this->current_id)).'\')">'.
                (isset($this->columns[$field]['icon'])?$this->columns[$field]['icon']:'').
                $this->columns[$field]['descr'].'</button>';
    }
    function format_confirm($field){
        $this->current_row_html[$field]='<button type="button" class="'.$this->columns[$field]['button_class'].' button_'.$field.'" '.
            'onclick="$(this).univ().confirm(\'Are you sure?\').ajaxec(\''.$this->api->url(null,
            array($field=>$this->current_id,$this->name.'_'.$field=>$this->current_id)).'\')">'.
                (isset($this->columns[$field]['icon'])?$this->columns[$field]['icon']:'').
                $this->columns[$field]['descr'].'</button>';
    }
    function format_prompt($field){
        $this->current_row_html[$field]='<button type="button" class="'.$this->columns[$field]['button_class'].'button_'.$field.'" '.
            'onclick="value=prompt(\'Enter value: \');$(this).univ().ajaxec(\''.$this->api->url(null,
            array($field=>$this->current_id,$this->name.'_'.$field=>$this->current_id)).'&value=\'+value)">'.
                (isset($this->columns[$field]['icon'])?$this->columns[$field]['icon']:'').
                $this->columns[$field]['descr'].'</button>';
    }
    function init_boolean($field){
        @$this->columns[$field]['thparam'].=' style="text-align: center"';
    }
    function format_boolean($field){
        if($this->current_row[$field] && $this->current_row[$field]!='N' && $this->current_row[$field]){
            $this->current_row_html[$field]='<div align=center><span class="ui-icon ui-icon-check">yes</span></div>';
        }else $this->current_row_html[$field]='';
    }
    function format_checkbox($field){
        $this->current_row_html[$field] = '<input type="checkbox" id="cb_'.
            $this->current_id.'" name="cb_'.$this->current_id.
            '" value="'.$this->current_id.'"'.
            ($this->current_row['selected']=='Y'?" checked ":" ").//'" onclick="'.
            //$this->onClick($field).
            '/>';
        $this->setTDParam($field,'width','10');
        $this->setTDParam($field,'align','center');
    }
    function format_image($field){
        $this->current_row_html[$field]='<img src="'.$this->current_row[$field].'"/>';
    }
    function staticSortCompare($row1,$row2){
        if($this->sortby[0]=='-'){
            return strcmp($row2[substr($this->sortby,1)],$row1[substr($this->sortby,1)]);
        }
        return strcmp($row1[$this->sortby],$row2[$this->sortby]);
    }
    function applySorting($i,$field,$desc){
        if($i instanceof DB_dsql)$i->order($field,$desc);
        elseif($i instanceof Model_Table)$i->setOrder($field,$desc);
        elseif($i instanceof Model)$i->setOrder($field,$desc);
    }
    function getIterator(){
        $i=parent::getIterator();
        if($this->sortby){
            $desc=false;
            $order=$this->sortby_db;
            if($this->sortby_db[0]=='-'){
                $desc=true;
                $order=substr($order,1);
            }
            $this->applySorting($i,$order,$desc);
        }
        return $i;
    }
    function setTemplate($template){
        // This allows you to use Template
        $this->columns[$this->last_column]['template']=$this->add('SMlite')
            ->loadTemplateFromString($template);
        return $this;
    }
    function getFieldStyle($field,$id){
        /**
         * Returns the structured string with row styles. Used along with getRowContent()
         * in row redrawing
         */
        $style=$this->tdparam[$this->getCurrentIndex()][$field];
        $tdparam=null;
        if(is_array($style)){
            // now we should convert style elements' names to JS compatible
            $tdparam=array();
            foreach($style as $key=>$value){
                switch($key){
                    //case 'background-color':$tdparam[]="$key:$value";break;
                    case 'style': case 'css':
                        // style is a nested array
                        foreach($value as $k=>$v){
                            $tdparam[]="$k::$v";
                        }
                        break;

                    default:
                        $tdparam[]="$key::$value";
                }
            }
        }
        return (is_array($tdparam)?join($tdparam,'<s>'):'');
    }
    function getRowTitle(){
        $title=' ';
        foreach($this->title_col as $col){
            $title.=$this->current_row[$col];
        }
        if($title==' '){
            return "Row #".$this->current_id;
        }
        return substr($title,1);
    }
    function applyTDParams($field,$totals=false){
        // setting cell parameters (tdparam)
        $tdparam=@$this->tdparam[$this->getCurrentIndex()][$field];
        $tdparam_str='';
        if(is_array($tdparam)){
            // wrap is replaced by style property
            unset($tdparam['wrap']);
            if(is_array($tdparam['style'])){
                $tdparam_str.='style="';
                foreach($tdparam['style'] as $key=>$value)$tdparam_str.=$key.':'.$value.';';
                $tdparam_str.='" ';
                unset($tdparam['style']);
            }
            //walking and combining string
            foreach($tdparam as $id=>$value)$tdparam_str.=$id.'="'.$value.'" ';
            if($totals)$this->totals_t->set("tdparam_$field",trim($tdparam_str));
            else $this->row_t->trySetHTML("tdparam_$field",trim($tdparam_str));
        }
    }
    function setTotalsTitle($field,$title="Total:"){
        $this->totals_title_field=$field;
        $this->totals_title=$title;
        return $this;
    }
    function formatTotalsRow(){
        foreach($this->columns as $tmp=>$column){
            $formatters = explode(',',$column['type']);
            $all_failed=true;
            foreach($formatters as $formatter){
                if(method_exists($this,$m="format_totals_".$formatter)){
                    $all_failed=false;
                    $this->$m($tmp);
                }
            }
            // setting cell parameters (tdparam)
            $this->applyTDParams($tmp,true);
            if($all_failed)$this->current_row[$tmp]=$this->totals_value_na;
        }
    }
    function updateTotals(){
        parent::updateTotals();
        // set title
        foreach($this->current_row as $key=>$val){
            if (!empty($this->totals_title_field) && $key==$this->totals_title_field) {
                $this->totals[$key]=$this->totals_title;
            }
        }
    }
    /**
     * Adds paginator to the grid
     * @param $ipp row count per page
     */
    function addPaginator($ipp=25){
        // adding ajax paginator
        if ($this->paginator) {
            return $this->paginator;
        }
        $this->paginator=$this->add('Paginator');
        $this->paginator->ipp($ipp);
        return $this;
    }

    public function setWidth( $width ){
        $this->template->set('container_style', 'margin: 0 auto; width:'.$width.((!is_numeric($width))?'':'px'));
        return $this;
    }
    public function setNoRecords($message){
        $this->no_records_message=$message;
        return $this;
    }
    public function getCurrentIndex($idfield='id'){
        // TODO: think more to optimize this method
        if(is_array($this->data))return array_search(current($this->data),$this->data);
        // else it is dsql dataset...
        return $this->current_row[$idfield];
    }
    public function setTDParam($field,$path,$value){
        // adds a parameter. nested ones can be specified like 'style/color'
        $path=explode('/',$path);
        $current_position=&$this->tdparam[$this->getCurrentIndex()][$field];
        if(!is_array($current_position))$current_position=array();
        foreach($path as $part){
            if(array_key_exists($part,$current_position)){
                $current_position=&$current_position[$part];
            }else{
                $current_position[$part]=array();
                $current_position=&$current_position[$part];
            }
        }
        $current_position=$value;
    }
    /**
     * Adds column ordering object. With it you can reorder your columns.
     */
    function addOrder(){
        return $this->add('Order','columns')
            ->useArray($this->columns)
            ;
    }
    /**
     * Adds column with checkboxes on the basis of Model definition
     * @field - should be Form_Field object or jQuery selector of 1 field
     * When passing it as jQuery selector don't forget hash sign like "#myfield"
     */
    function addSelectable($field){
        $this->js_widget=null;
        $this->js(true)
            ->_load('ui.atk4_checkboxes')
            ->atk4_checkboxes(array('dst_field'=>$field));
        $this->addColumn('checkbox','selected');

        $this->addOrder()
            ->useArray($this->columns)
            ->move('selected','first')
            ->now();
    }
    /* to reuse td params */
    function getAllTDParams(){
        return $this->tdparam;
    }
}
