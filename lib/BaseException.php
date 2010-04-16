<?php
class BaseException extends Exception {
	// Exception defines it's methods as "final", which is complete nonsence
	// and incorrect behavor in my opinion. Therefore I need to re-declare
	// it's class and re-define the methods so I could extend my own methods
	// in my classes.
	private $frame_stop;
	public $my_backtrace;
	public $shift=0;
	public $name;

	public $more_info;
	function __construct($msg,$func=null,$shift=1,$code=0){
		parent::__construct($msg,$code);
	$this->name=get_class($this);
		$this->frame_stop=$func;
		$this->shift=$shift;

		if(is_int($func)){
			$shift=$func;$func=null;
		}

		$tr=debug_backtrace();
		if(!isset($this->frame_stop)){
			$this->my_backtrace=$tr;
			return;
		}

		while($tr[0] && $tr[0]['function']!=$this->frame_stop){
			array_shift($tr);
		}
		if($tr){
			$this->my_backtrace=$tr;
			return;
		}
		$this->my_backtrace = debug_backtrace();
		return;
	}
	function addMoreInfo($key,$value){
		$this->more_info[$key]=$value;
		return $this;
	}
	function getMyTrace(){
		return $this->my_backtrace;
	}
	function getAdditionalMessage(){
		return '';
	}
	function getMyFile(){ return $this->my_backtrace[$this->shift]['file']; }
	function getMyLine(){ return $this->my_backtrace[$this->shift]['line']; }

	function getHTML($message=null){
		$html='';
		$html.= '<h2>'.get_class($this).(isset($message)?': '.$message:'').'</h2>';
		$html.= '<p><font color=red>' . $this->getMessage() . '</font></p>';
		$html.= '<p><font color=blue>' . $this->getMyFile() . ':' . $this->getMyLine() . '</font></p>';
		$html.=$this->getDetailedHTML();
		$html.= backtrace($this->shift+1,$this->getMyTrace());
		return $html;
	}
	function getDetailedHTML(){
		return '';
	}
}
