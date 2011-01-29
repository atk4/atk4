<?php
/*
   Model class defines structure and behavor of your model. You can re-define number of existing
   functions and add your own functions. 

   */
class Model_User extends Model_Table {
	public $entity_code='user';

	function defineFields(){
		parent::defineFields();

		// Each field can have a varietty of properties. Please
		// referr to FieldDefinition.php file for more information

		$this->newField('email')
			->mandatory(true)
			;

		$this->newField('name')
			;

		$this->newField('surname')
			;

		$this->newfield('gender')
			->datatype('list')
			->listData(array('M'=>'Male','F'=>'Female'))
			;

		// You can define related tables through
		// $this->addRelatedEntity()
		// see function comments inside Model/Table


		// You can also add relations between fileds
		$this->newField('manager_id')
			->datatype('reference')
			->refModel('Model_User')
			;
	}
}
