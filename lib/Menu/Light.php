<?php
class Menu_Light extends AbstractView {
	/*
	   Light menu which will inherit paret's template
	   or use the template you specify. It allows you to add tags such as

	   <?current?>highlight<?/?>

	   <?$current_index?>
	   <?$current_team?>
	   <?$current_contact_us?>

	   The tag <?current?> contents will be immediatelly deleted. It's contents will
	   however be inserted into one of the appropriate tags.

	   For example if you are on the page   team/test then the contents of current_team_test tag will be set
	   to "highlight". If current_team_test is not found, then current_team is used instead. If that tag
	   is also not found, thend <?current?> tag is set back to it's original value.

	   */
	function render(){
		$c=$this->template->get('current');
		$this->template->del('current');

		$toppage=explode('_',$this->api->page);
		$toppage=$toppage[0];


		// direct page match
		if($this->template->is_set($tag='current_'.$this->api->page)){
			$this->template->set($tag,$c);
		}elseif($this->template->is_set($tag='current_'.$toppage)){
			$this->template->set($tag,$c);
		}else{
			$this->template->set('current',$c);
		}
		parent::render();
	}
	function defaultTemplate(){
		return $this->spot;
	}
}
