<?
class Lister extends AbstractView {
    public $dq=null;

    public $data=null;

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
        if($this->safe_html_output){
            foreach($this->current_row as $x=>$y){
                $this->current_row[$x]=htmlspecialchars(stripslashes($y));
                if(!isset($this->current_row[$x]) || is_null($this->current_row[$x]) || $this->current_row[$x]=='')$this->current_row[$x]='&nbsp;';
            }
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
}
