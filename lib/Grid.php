<?
class Grid extends CompleteLister {
    private $columns;
    private $table;
    private $id;

    public $last_column;
    public $sortby='0';
    public $sortby_db=null;

    public $displayed_rows=0;

    public $totals_t=null;

    function init(){
        parent::init();
        $this->api->addHook('pre-render',array($this,'precacheTemplate'));

        $this->sortby=$this->learn('sortby',$_GET[$this->name.'_sort']);
    }
    function defaultTemplate(){
        return array('grid','_top');
    }
    function addColumn($type,$name,$descr=null){
        if($descr===null)$descr=$name;
        $this->columns[$name]=array(
                'type'=>$type,
                'descr'=>$descr
                );

        $this->last_column=$name;

        return $this;
    }
    function makeSortable($db_sort=null){
        // Sorting
        $reverse=false;
        if(substr($db_sort,0,1)=='-'){
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
    function format_number($field){
    }
    function format_text($field){
    }
    function format_money($field){
        $m=$this->current_row[$field];
        $this->current_row[$field]=number_format($m,2);
        if($m<0){
            $this->current_row[$field]='<font color=red>'.$this->current_row[$field].'</font>';
        }
    }
    function format_totals_number($field){
        return $this->format_number($field);
    }
    function format_totals_money($field){
        return $this->format_money($field);
    }
	function format_time($field){
		$this->current_row[$field]=format_time($this->current_row[$field]);
	}
    function format_expander($field){
        $n=$this->name.'_'.$field.'_'.$this->current_row['id'];
        $this->row_t->set('tdparam_'.$field,'id="'.$n.'" style="cursor: hand" nowrap onclick=\''.
                'expander_flip("'.$this->name.'",'.$this->current_row['id'].',"'.
                    $field.'","'.
                    $this->api->getDestinationURL($this->api->page.'_'.$field,array('expander'=>$field,
                            'cut_object'=>$this->api->page.'_'.$field)).'&id=")\'');
        if($this->current_row[$field]){
            $this->current_row[$field]='<font color="blue">'.$this->current_row[$field].'</font>';
        }else{
            $this->current_row[$field]='<font color="blue">['.$this->columns[$field]['descr'].']</font>';
        }
    }
    function setSource($table,$db_fields="*"){
        parent::setSource($table,$db_fields);
        if($this->sortby){
            $desc=false;
            $order=$this->sortby_db;
            if(substr($this->sortby_db,0,1)=='-'){
                $desc=true;
                $order=substr($order,1);
            }
            $this->dq->order($order,$desc);
        }
        return $this;
    }
    function formatRow(){
        foreach($this->columns as $tmp=>$column){ // $this->cur_column=>$column){
            $formatters = split(',',$column['type']);
            foreach($formatters as $formatter){
                if(method_exists($this,$m="format_".$formatter)){
                    $this->$m($tmp);
                }else throw new BaseException("Grid does not know how to format type: ".$formatter);
            }
        }
    }
    function formatTotalsRow(){
        foreach($this->columns as $tmp=>$column){
            $formatters = split(',',$column['type']);
            $all_failed=true;
            foreach($formatters as $formatter){
                if(method_exists($this,$m="format_totals_".$formatter)){
                    $all_failed=false;
                    $this->$m($tmp);
                }
            }
            if($all_failed)$this->current_row[$tmp]='-';
        }
    }
    function precacheTemplate(){
        // pre-cache our template for row
        $row = $this->row_t;

        $col = $row->cloneRegion('col');
        $header = $this->template->cloneRegion('header');
        $header_col = $header->cloneRegion('col');
        $header_sort = $header_col->cloneRegion('sort');


        if($t_row = $this->totals_t){
            $t_col = $t_row->cloneRegion('col');
            $t_row->del('cols');
        }

        $row->set('grid_name',$this->name);
        $row->set('row_id','<?$id?>');
        $row->set('odd_even','<?$odd_even?>');
        $row->del('cols');
        $header->del('cols');
        foreach($this->columns as $name=>$column){
            $col->del('content');
            $col->set('content','<?$'.$name.'?>');

            if($t_row){
                $t_col->del('content');
                $t_col->set('content','<?$'.$name.'?>');
                $t_row->append('cols',$t_col->render());
            }

            // some types needs control over the td
            $col->set('tdparam','<?tdparam_'.$name.'?>nowrap<?/?>');

            $row->append('cols',$col->render());

            $header_col->set('descr',$column['descr']);
            if(isset($column['sortable'])){
                $s=$column['sortable'];
                // calculate sortlink
                $l = $this->api->getDestinationURL(null,array($this->name.'_sort'=>$s[1]));

                $header_sort->set('order',$column['sortable'][0]);
                $header_sort->set('sortlink',$l);
                $header_col->set('sort',$header_sort->render());
            }else{
                $header_col->del('sort');
            }
            $header->append('cols',$header_col->render());
        }
        $this->row_t = $this->api->add('SMlite');
        $this->row_t->loadTemplateFromString($row->render());

        if($t_row){
            $this->totals_t = $this->api->add('SMlite');
            $this->totals_t->loadTemplateFromString($t_row->render());
        }

        $this->template->set('header',$header->render());
        //var_dump(htmlspecialchars($this->row_t->tmp_template));
    }
    function render(){
        parent::render();
    }
}
