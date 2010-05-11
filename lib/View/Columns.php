<?
class View_Columns extends View {
	public $cnt=0;
	function init(){
		parent::init();
	}
	function addColumn($width='*'){
		// TODO: implement  width

		$c=$this->add('View',++$this->cnt,'Columns',array('view/columns','Columns'));
		$c->template->trySet('width',$width);
		$this->template->set('cnt',$this->cnt);
		return $c;
	}
	function defaultTemplate(){
		return array('view/columns','_top');
	}
}
?>
