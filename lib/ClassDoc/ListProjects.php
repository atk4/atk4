<?php
/*
 * Created on 21.03.2006 by *Camper*
 */
class ListProjects extends TGrid{
	private $types = array(1=>'Library', 2=>'Application');
	
	function init(){
		parent::init();
		$this
			->addColumn('text', 'type', 'Type')->makeSortable()
			->addColumn('expander', 'name', 'Project name')->makeSortable()
			->addColumn('text', 'description', 'Description')
			->addColumn('expander', 'parse', 'Parse')
			->addColumn('expander', 'edit', 'Edit')
			->addColumn('delete', 'delete_project', 'Delete')
			
			->setSource('project')
		;
		$this->add('Paginator', null, 'paginator');
		$this->addButton('Add project')->redirect('AddProject');
	}
}

?>
