<?php
/*
 * Created on 13.04.2006 by *Camper*
 */
class TGrid extends Grid{
	function format_delete($field){
		$this->current_row[$field] = "<a onclick=\"return confirm('Are sure want to delete it?')\" " .
			"href=".$this->api->getDestinationURL($field, array('id'=>$this->current_row['id'])).">[Delete]</a>";
	}
}
