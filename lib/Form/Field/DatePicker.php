<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
/**
 * Text input with Javascript Date picker
 * It draws date in locale format (taken from $config['locale']['date'] setting) and stores it in
 * MySQL acceptable date format (YYYY-MM-DD)
 */
class Form_Field_DatePicker extends Form_Field_Line {
	public $options=array();
    function init(){
        parent::init();
        $this->addButton('')
            ->setHtml('&nbsp;')
            ->setIcon('calendar')
            ->js('click',$this->js()->datepicker('show'));

    }
	function getInput($attr=array()){
		// $this->value contains date in MySQL format
		// we need it in locale format

		$this->js(true)->datepicker(array_merge(array(
						'duration'=>0,
						'showOn'=>'none',
			//			'buttonImage'=>$this->api->locateURL('images','calendar.gif'),
				//		'buttonImageOnly'=> true,
						'changeMonth'=>true,
						'changeYear'=>true,
						'dateFormat'=>$this->api->getConfig('locale/date_js','dd/mm/yy')
						),$this->options));

		return parent::getInput(array_merge(
					array(
						'value'=>$this->value?(date($this->api->getConfig('locale/date','d/m/Y'),strtotime($this->value))):'',
					     ),$attr
					));
	}
	function set($value){
		// value can be valid date format, as in config['locale']['date']
		if(!$value)return parent::set($value);
		@list($d,$m,$y)=explode('/',$value);
		if($y)$value=join('/',array($m,$d,$y));
		elseif($m)$value=join('/',array($m,$d));
		$value=date('Y-m-d',strtotime($value));
		return parent::set($value);
	}
	function get(){
		$value=parent::get();
		// date cannot be empty string
		if($value=='')return null;
		return $value;
	}
}
