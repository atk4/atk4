<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
// Field bundle
//if(!class_exists('Form_Field',false))include_once'Form/Field.php';
/**
 * This class implements generic form, which you can actually use without
 * redeclaring it. Just add fields, buttons and use execute method.
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   See file COPYING
 * @version     $Id$
 */
class Form_Basic extends View {
    protected $form_template = null;
    protected $form_tag = null;
    
    // Here we will have a list of errors occured in the form, when we tried to
    // submit it. field_name => error
    public $errors=array();

    // Those templates will be used when rendering form and fields
    public $template_chunks=array();

    // This array holds list of values prepared for fields before their
    // initialization. When fields are initialized they will look into this
    // array to see if there are default value for them.
    // Afterwards fields will link to $this->data, so changing
    // $this->data['fld_name'] would actually affect field's value.
    // You should use $this->set() and $this->get() to read/write individual
    // field values. You should use $this->setStaticSource() to load values from
    // hash, BUT - AAAAAAAAAA: this array is no more!!!
    public $data = array();

    public $bail_out = null;   // if this is true, we won't load data or submit or validate anything.
    protected $loaded_from_db = false;  // if true, update() will try updating existing row. if false - it would insert new
    public $onsubmit = null;
    public $onload = null;
    protected $ajax_submits=array();    // contains AJAX instances assigned to buttons
    protected $get_field=null;          // if condition was passed to a form through GET, contains a GET field name
    protected $conditions=array();

    public $js_widget='ui.atk4_form';
    public $js_widget_arguments=array();

    public $default_exception='Exception_ValidityCheck';
    public $default_controller='Controller_MVCForm';

    public $dq = null;
    function init(){
        /**
         * During form initialization it will go through it's own template and
         * search for lots of small template chunks it will be using. If those
         * chunk won't be in template, it will fall back to default values.
         * This way you can re-define how form will look, but only what you need
         * in particular case. If you don't specify template at all, form will
         * work with default look.
         */
        parent::init();

        $this->getChunks();

        // After init method have been executed, it's safe for you to add
        // controls on the form. BTW, if you want to have default values such as
        // loaded from the table, then intialize $this->data array to default
        // values of those fields.
        $this->api->addHook('pre-exec',array($this,'loadData'));
        $this->api->addHook('pre-render-output',array($this,'lateSubmit'));
        $this->api->addHook('submitted',$this);

        $this->template_chunks['form']
            ->set('form_action',$this->api->url(null,array('submit'=>$this->name)));

    }
    protected function getChunks(){
        // commonly replaceable chunks
        $this->grabTemplateChunk('form_comment');
        $this->grabTemplateChunk('form_separator');
        $this->grabTemplateChunk('form_line');      // template for form line, must contain field_caption,field_input,field_error
        if($this->template->is_set('hidden_form_line'))
            $this->grabTemplateChunk('hidden_form_line');
        $this->grabTemplateChunk('field_error');    // template for error code, must contain field_error_str
        $this->grabTemplateChunk('field_mandatory');// template for marking mandatory fields

        // other grabbing will be done by field themselves as you will add them
        // to the form. They will try to look into this template, and if you
        // don't have apropriate templates for them, they will use default ones.
        $this->template_chunks['form']=$this->template;
        $this->template_chunks['form']->del('Content');
        $this->template_chunks['form']->del('form_buttons');
        $this->template_chunks['form']->set('form_name',$this->name.'_form');

        return $this;
    }

    function initializeTemplate($tag, $template){
        $template = $this->form_template?:$template;
        $tag = $this->form_tag?:$tag;
        return parent::initializeTemplate($tag, $template);
    }
    function defaultTemplate($template = null, $tag = null){
        if ($template){
            $this->form_template = $template;
        }
        if ($tag){
            $this->form_tag = $tag;
        }
        return array($this->form_template?:"form", $this->form_tag?:"form");
    }
    function grabTemplateChunk($name){
        if($this->template->is_set($name)){
            $this->template_chunks[$name] = $this->template->cloneRegion($name);
        }else{
            //return $this->fatal('missing form tag: '.$name);
            // hmm.. i wonder what ? :)
        }
    }
    /**
     * Should show error in field. Override this method to change form default alert
     * @param object $field Field instance that caused error
     * @param string $msg message to show
     */
    function showAjaxError($field,$msg){
        // Avoid deprecated function use in reference field, line 246
        return $this->displayError($field,$msg);
    }

    function displayError($field=null,$msg=null){
        if(!$field){
            // Field is not defined
            // TODO: add support for error in template
            $this->js()->univ()->alert($msg?:'Error in form')->execute();
        }
        if(!is_object($field))$field=$this->getElement($field);
        $this->js()->atk4_form('fieldError',$field->short_name,$msg)->execute();
    }
    function addField($type,$name,$caption=null,$attr=null){
        if($caption===null)$caption=ucwords(str_replace('_',' ',$name));

        switch(strtolower($type)){
            case'dropdown':$class='DropDown';break;
            case'checkboxlist':$class='CheckboxList';break;
            default:$class=$type;
        }
	$class[0]=strtoupper($class[0]);
        $class=$this->api->normalizeClassName($class,'Form_Field');
        $last_field=$this->add($class,$name,null,'form_line')
            ->setCaption($caption);
        $last_field->setForm($this);
        $last_field->template->trySet('field_type',strtolower($type));
        $last_field->setAttr($attr);

        return $last_field;
    }
    function importFields($model,$fields=undefined){
        $this->add($this->default_controller)->importFields($model,$fields);
    }

    function addComment($comment){
        if(!isset($this->template_chunks['form_comment']))
            throw new BaseException('Form\'s template ('.$this->template->loaded_template.') does not support comments');
        return $this->add('Html')->set(
                $this->template_chunks['form_comment']->set('comment',$comment)->render()
                );
    }
    function addSeparator($fieldset_class=''){
        if(!isset($this->template_chunks['form_separator']))return $this;
        $c=$this->template_chunks['form_separator'];
        $c->trySet('fieldset_class',$fieldset_class);

        return $this->add('Html')->set($c->render());
    }

    // Operating with field values
    function get($field=null){
        if(!$field)return $this->data;
        return $this->data[$field];
    }
    function setSource($table,$db_fields=null){
        if(is_null($db_fields)){
            $db_fields=array();
            foreach($this->elements as $key=>$el){
                if(!($el instanceof Form_Field))continue;
                if($el->no_save)continue;
                $db_fields[]=$key;
            }
        }
        $this->dq = $this->api->db->dsql()
            ->table($table)
            ->field('*',$table)
            ->limit(1);
        return $this;
    }
    function set($field_or_array,$value=undefined){
        // We use undefined, because 2nd argument of "null" is meaningfull
        if($value===undefined){
            if(is_array($field_or_array)){
                foreach($field_or_array as $key=>$val){
                    if(isset($this->elements[$key])&&($this->elements[$key] instanceof Form_Field))
                        $this->set($key,$val);
                }
                return $this;
            }else{
                throw new ObsoleteException('Please specify 2 arguments to $form->set()');
            }
        }

        if(!isset($this->elements[$field_or_array])){
            foreach ($this->elements as $key => $val){
                echo "$key<br />";
            }
            throw new BaseException("Trying to set value for non-existant field $field_or_array");
        }
        if($this->elements[$field_or_array] instanceof Form_Field)
            $this->elements[$field_or_array]->set($value);
        else{
            //throw new BaseException("Form fields must inherit from Form_Field ($field_or_array)");
        }
        return $this;
    }
    function getAllFields(){
        return $this->get();
    }
    function addSubmit($label='Save',$name=null){
        $submit = $this->add('Form_Submit',$name,'form_buttons')
            ->setLabel($label)
            ->setNoSave();

        return $submit;
    }
    function addButton($label='Button',$name=null){
        $button = $this->add('Button',$name,'form_buttons')
            ->setLabel($label);

       return $button;
    }

    function loadData(){
        /**
         * This call will be sent to fields, and they will initialize their values from $this->data
         */
        if(!is_null($this->bail_out))return;
        $this->hook('post-loadData');
    }

    function isLoadedFromDB(){
        return $this->loaded_from_db;
    }
    function update(){
        // TODO: start transaction here
        if($this->hook('update'))return $this;

        if(!($m=$this->getModel()))throw new BaseException("Can't save, model not specified");
        if(!is_null($this->get_field))$this->api->stickyForget($this->get_field);
        foreach($this->elements as $short_name => $element){
            if($element instanceof Form_Field)if(!$element->no_save){
                //if(is_null($element->get()))
                $m->set($short_name, $element->get());
            }
        }
        $m->save();
    }
    function submitted(){
        /**
         * Default down-call submitted will automatically call this method if form was submitted
         */
        // We want to give flexibility to our controls and grant them a chance
        // to hook to those spots here.
        // On Windows platform mod_rewrite is lowercasing all the urls.
        if($_GET['submit']!=$this->name)return;
        if(!is_null($this->bail_out))return $this->bail_out;

        $this->hook('loadPOST');
        try{
            $this->hook('validate');

            if(!empty($this->errors))return false;
            
            if(($output=$this->hook('submit',array($this)))){
                /* checking if anything usefull in output */
                if(is_array($output)){
                    $has_output = false;
                    foreach ($output as $row){
                        if ($row){
                            $has_output = true;
                            break;
                        }
                    }
                    if (!$has_output){
                        return true;
                    }
                }
                /* TODO: need logic re-check here + test scripts */
                //if(!is_array($output))$output=array($output);
                // already array
                if($has_output)$this->js(null,$output)->execute();
            }
        }catch (BaseException $e){
            if($e instanceof Exception_ValidityCheck){
                $f=$e->getField();
                if($f && is_string($f) && $fld=$this->hasElement($f)){
                    $fld->displayFieldError($e->getMessage());
                } else $this->js()->univ()->alert($e->getMessage().' in undefined field')->execute();
            }
            if($e instanceof Exception_ForUser){
                $this->js()->univ()->alert($e->getMessage())->execute();
            }
            throw $e;
        }
        return true;
    }
    function lateSubmit(){
        if(@$_GET['submit']!=$this->name)return;

        if($this->bail_out===null || $this->isSubmitted()){
            $this->js()->univ()
                ->consoleError('Form '.$this->name.' submission is not handled.'.
                    ' See: http://agiletoolkit.org/doc/form/submit')
                ->execute();
        }
    }
    function isSubmitted(){
        // This is alternative way for form submission. After form is initialized
        // you can call this method. It will hurry up all the steps, but you will
        // have ready-to-use form right away and can make submission handlers
        // easier
        if($this->bail_out!==null)return $this->bail_out;

        $this->loadData();
        $result = $_POST && $this->submitted();
        $this->bail_out=$result;
        return $result;
    }
    function onSubmit($callback){
        $this->addHook('submit',$callback);
        $this->isSubmitted();
    }
    function setLayout($template){
        // Instead of building our own Content we will take it from
        // pre-defined template and insert fields into there
        $this->template_chunks['custom_layout']=($template instanceof SMLite)?$template:$this->add('SMLite')->loadTemplate($template);
        $this->template_chunks['custom_layout']->trySet('_name',$this->name);
        $this->template->trySet('form_class_layout',$c='form_'.basename($template));
        return $this;
    }
    function setFormClass($class){
        return $this->setClass($class);
    }
    function render(){
        // Assuming, that child fields already inserted their HTML code into 'form'/Content using 'form_line'
        // Assuming, that child buttons already inserted their HTML code into 'form'/form_buttons

        if($this->js_widget){
            $fn=str_replace('ui.','',$this->js_widget);
            $this->js(true)->_load($this->js_widget)->$fn($this->js_widget_arguments);
        }

        if(isset($this->template_chunks['custom_layout'])){
            foreach($this->elements as $key=>$val){
                if($val instanceof Form_Field){
                    $attr=$this->template_chunks['custom_layout']->get($key);
                    if(is_array($attr))$attr=join(' ',$attr);
                    if($attr)$val->setAttr('style',$attr);
                    
                    if(!$this->template_chunks['custom_layout']->is_set($key)){
                        $this->js(true)->univ()->log('No field in layout: '.$key);
                    }
                    $this->template_chunks['custom_layout']->trySetHTML($key,$val->getInput());

                    if($this->errors[$key]){
                        $this->template_chunks['custom_layout']
                            ->trySet($key.'_error',$val->error_template
                                ->set('field_error_str',$this->errors[$key])->render());
                    }
                }
            }
            $this->template->setHTML('Content',$this->template_chunks['custom_layout']->render());
        }
        $this->owner->template->appendHTML($this->spot,$r=$this->template_chunks['form']->render());
    }
    function hasField($name){
        return isset($this->elements[$name])?$this->elements[$name]:false;
    }
    function isClicked($name){
        if(is_object($name))$name=$name->short_name;
        return $_POST['ajax_submit']==$name || isset($_POST[$this->name . "_" . $name]);
    }
    function error($field,$text){
        $this->getElement($field)->displayFieldError($text);
    }
    /* external error management */
    function setFieldError($field, $name){
        $this->errors[$field] = (isset($this->errors[$field])?$this->errors[$field]:'') . $name;
    }
}
