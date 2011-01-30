<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
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
