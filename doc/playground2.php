<?php
class page_playground2 extends Page {
	function init(){
		parent::init();

		$this->add('H1')->set('Fullscreen Forms 960gs');

		$t=$this->add('Tabs');

		foreach(array(
					'basic',
					'inline',
					'vertical',
					'horizontal',
					'basic-2col'
					) as $form_class){

			$t1=$t->addTab('atk-form-'.$form_class);
			$t1->add('H3')->set('$form->setFormClass(\''.$form_class.'\');');
			$t1->add('SampleForm')->setFormClass($form_class);
		}

	}
}

class SampleForm extends Form {
  function setFormClass($class){
	  $this->template->trySet('form_class',$class);
  }

  function init(){
    parent::init();
    $f=$this;

    $f->addField('line','email')
      ->validateNotNull()
      ->validateField('filter_var($this->get(), FILTER_VALIDATE_EMAIL)')
      ;

    $f->addField('password','password')->validateNotNull()
      ->setProperty('max-length',30)
      ->add('Text',null,'after_field')->set('<ins>30 char max</ins>');

    $f->addField('password','password2')
       ->validateField('$this->get()==$this->owner->getElement("password")->get()',
       'Passwords do not match');


    $f->addField('line','name')->validateNotNull();

    $f->addField('radio','sex')
      ->setValueList(array('m'=>'Male','f'=>'Female'))
      ;  // automatically validated to be one of value list


    $f->addSeparator(' ');

    $f->addField('DatePicker','date_birth','Birthdate');

    $f->addField('dropdown','age')
      ->setValueList(array('','11 - 20', '21 - 30', '31 - 40'));

    $f->addField('text','about')
      ->setProperty('cols',45)->setProperty('rows','5')
      ->validateField('5000>=strlen($this->get())','Too long');

    $f->addField('checkbox','agreeRules',
      $f->js()->univ()->dialogURL('Rules',
        $this->api->getDestinationURL(null,array('showTerms'=>true,'cut_object'=>'rules')))
       ->getLink('I Agree to Rules and Terms')
    )->validateNotNull('You must agree to the rules');

    $f->addSubmit('Register');
}

}
