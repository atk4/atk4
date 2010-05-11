<?
/*
   This class adds some extra tests to your application
*/
class Testing extends AbstractModel {
	function init(){
		parent::init();

		// TODO: bail ot on live environment

		$this->api->addLocation('test',array('page'=>'page'))
			->setParent($this->api->pathfinder->atk_location);
	}
}
