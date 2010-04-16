<?php
class DBlite_exception extends BaseException {
	protected $str;
	protected $info;
	function __construct($str,$info=null,$shift=0){
		$this->str=$str;
		if(is_object($info)){
			$this->info['last_query']=$info->last_query;
			$this->info['mysql_error']=mysql_error();
		}else $this->info=$info;
		parent::__construct("DBlite error: ".$this->str,null,$shift);
	}
	function getDetailedHTML(){
		// first, perhaps we can highlight error in a query
		$cause = ereg_replace('.*near \'(.*)\' at line .*','\1',$this->info['mysql_error']);
		if($cause!=$this->info['mysql_error']){
			$this->info['last_query']=str_replace($cause,"<font color=red><b>".$cause."</b></font>",$this->info['last_query']);
		}
		$r='';
		if(is_array($this->info)){
			if($this->info['last_query'])
				$r.="<b>Last query:</b> <div style='border: 1px solid black'>".$this->info['last_query']."</div>";
			if($this->info['mysql_error'])
				$r.="<b>MySQL error:</b> <div style='border: 1px solid black'><font color=red>".$this->info['mysql_error']."</font></div>";
		}elseif(is_string($this->info)){
			$r.="<b>Useful information:</b> <div style='border: 1px solid black'><font color=red>".$this->info."</font></div>";
		}else{
			foreach($this->info as $key=>$val){
				$r.="<b>$key:</b> <div style='border: 1px solid black'><font color=red>".$val."</font></div>";
			}
		}
		return $r;
	}
}
