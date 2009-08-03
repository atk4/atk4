<?php
/**
 * Text input with Javascript Date picker
 * It draws date in locale format (taken from $config['locale']['date'] setting) and stores it in
 * MySQL acceptable date format (YYYY-MM-DD)
 */
class Form_Field_DatePicker extends Form_Field {
	function init(){
		parent::init();
        $this->js(true)->datepicker(array(
					'duration'=>0,
					//'showOn'=>'button',
					//'buttonImage'=>'images/calendar.gif', 
					//'buttonImageOnly'=> true,
					'changeMonth'=>true,
					'changeYear'=>true,
					'dateFormat'=>$this->api->getConfig('locale/date_js','dd/mm/yy')
					));
	}
	function getInput($attr=array()){
		// $this->value contains date in MySQL format
		// we need it in locale format
		return parent::getInput(array_merge(
			array(
				'value'=>$this->value?(date($this->api->getConfig('locale/date'),strtotime($this->value))):'',
			),$attr
		));
	}
	function set($value){
		// value can be valid date format, as in config['locale']['date']
		if(!$value)return parent::set($value);
		list($d,$m,$y)=explode('/',$value);
		if($y)$value=join('/',array($m,$d,$y));
        elseif($m)$value=join('/',array($m,$d));
		$value=date('Y-m-d',strtotime($value));
		return parent::set($value);
	}
}
