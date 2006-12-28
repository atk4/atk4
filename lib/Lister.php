<?
class Lister extends AbstractView {
    public $dq=null;

    public $data=null;
    
    private $format=array();

    public $safe_html_output=true;  // do htmlspecialchars by default when formatting rows

    public $current_row=array();    // this is data of a current row
    function setSource($table,$db_fields="*"){
        if(!$this->api->db)throw new BaseException('DB must be initialized if you want to use Lister / setSource');
        $this->dq = $this->api->db->dsql();
        $this->api->addHook('pre-render',array($this,'execQuery'));

        $this->dq->table($table);
        $this->dq->field($db_fields);
        return $this;
    }
    function setStaticSource($data){
        $this->data=$data;
        return $this;
    }
    function execQuery(){
        $this->dq->do_select();
    }
    function formatRow(){
        foreach($this->current_row as $x=>$y){
		    if($this->safe_html_output){
        	    $this->current_row[$x]=htmlspecialchars(stripslashes($y));
		    }
		    // performing an additional formats
		    if($this->format[$x]){
	            $formatters = split(',',$this->format[$x]);
	            foreach($formatters as $formatter){
			    	if(method_exists($this,$m='format_'.$formatter)){
	                    $this->$m($x);
	                }else throw new BaseException("Lister does not have method: ".$m);
	            }
		    }
            if(!isset($this->current_row[$x]) || is_null($this->current_row[$x]) || $this->current_row[$x]=='')$this->current_row[$x]='&nbsp;';
        }
    }
    function fetchRow(){
        if(is_array($this->data)){
            return (bool)($this->current_row=array_shift($this->data));
        }
        return (bool)($this->current_row=$this->dq->do_fetchHash());
    }

    function render(){
        while($this->fetchRow()){
            $this->formatRow();
            $this->template->set($this->current_row);
            $this->output($this->template->render());
        }
    }
    
    function formatValue($col_name,$format_func){
    	/**
    	 * Sets a formatter for the dataset value
    	 */
    	$this->format[$col_name]=$format_func;
    	return $this;
    }
    
    function format_time_str($field){
    	$this->current_row[$field]=format_time_str($this->current_row[$field]);
    }
    
    function format_bold($field){
    	$this->current_row[$field]='<strong>'.$this->current_row[$field].'</strong>';
    }
}
