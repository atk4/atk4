<?
class View_Hint extends View_Box {
	function defaultTemplate(){
		return array('view/hint','_top');
	}
	function setTitle($title){
		$this->template->set('title',$title);
		return $this;
	}
}
