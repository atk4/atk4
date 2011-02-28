<?
class View_Columns extends View {
	public $cnt=0;
	function init(){
		parent::init();
	}
	function addColumn($width='*',$name='column'){
		// TODO: implement  width
		++$this->cnt;
		$c=$this->add('View',$name,'Columns',array('view/columns','Columns'));
		$c->template->trySet('width',$width);
		$this->template->trySet('cnt',$this->cnt);
		return $c;
	}
	function defaultTemplate(){
		return array('view/columns','_top');
	}
}
?>
