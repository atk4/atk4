<?php
class Lister extends View {
	public $dq=null;

	public $data=null;

	public $safe_html_output=true;  // do htmlspecialchars by default when formatting rows

	public $current_row=array();    // this is data of a current row
	function setSource($table,$db_fields=null,$db = null){
		if((!$this->api->db) && (!$db))throw new BaseException('DB must be initialized if you want to use Lister / setSource');
		$this->dq = $db ? $db->dsql() : $this->api->db->dsql();
		$this->api->addHook('pre-render',array($this,'execQuery'));

		$this->dq->table($table);
		if(isset($db_fields)){
			$this->dq->field($db_fields);
		}else{
			$this->dq->field('*');
			$this->dq->field($table.'.id id');
		}
		return $this;
	}
	function setStaticSource($data){
		$this->data=$data;
		return $this;
	}
	function handlePartialRender(){

		// If Partial render was requested though GET, then limit our output to those values only

		if($ids=$_GET[$this->name.'_reload_row']){
			// we were requseted to only show one row through AJAX request
			if(is_array($this->data)){
				$idsarr=explode(',',$ids);
				$this->data=array_filter($this->data,function($var){
					return in_array($var['id'],$idsarr);
				});
			}
			if(isset($this->dq)){
				$this->dq->where($this->dq->args['table'].'.id in',$ids);
			}
		}
	}
	function execQuery(){
		$this->handlePartialRender();
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
		if(!isset($this->dq))throw new BaseException($this->name.": dq must be set here");
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
