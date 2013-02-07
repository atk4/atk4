<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * Implementation of abstract form's field
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   See file COPYING
 * @version     $Id$
 */
abstract class Form_Field extends AbstractView {
    /**
     * Description of the field shown next to it on the form
     */
    public $error_template;      // template used to put errors on the field line
    public $mandatory_template;  // template used to mark mandatory fields
    public $caption;
    protected $value=null;       // use $this->get(), ->set().
    public $short_name=null;
    public $attr=array();
    public $no_save=null;

    public $field_prepend='';
    public $field_append='';

    public $comment='&nbsp;';
    protected $disabled=false;
    protected $mandatory=false;
    public $default_value=null;

    // Field customization
    private $separator='';
    public $show_input_only;
    public $form=null;

    public $button_prepend=null;
    public $button_append=null;

    function init(){
        parent::init();
        if(@$_GET[$this->owner->name.'_cut_field']==$this->name){
            $this->api->addHook('pre-render',array($this,'_cutField'));
        }
    }
    function setForm($form){
        $form->addHook('loadPOST',$this);
        $form->addHook('validate',$this);
        $this->form=$form;
        $this->form->data[$this->short_name] = $this->value;
        $this->value =& $this->form->data[$this->short_name];
        return $this;
    }

    function _cutField(){
        // this method is used by ui.atk4_form, when doing reloadField();
        unset($_GET['cut_object']);
        $this->recursiveRender();
        if($this->api->jquery)$this->api->jquery->getJS($this);
        throw new Exception_StopRender(
            $this->template->renderRegion($this->template->tags['before_field']).
            $this->getInput().
            $this->template->renderRegion($this->template->tags['after_field'])
        );
    }
    function setMandatory($mandatory=true){
        $this->mandatory=$mandatory;
        return $this;
    }
    function setReadonly($readonly=true){
        $this->readonly=$readonly;
        return $this;
    }
    function isMandatory(){
        return $this->mandatory;
    }
    function setCaption($_caption){
        $this->caption=$this->api->_($_caption);
        return $this;
    }
    function displayFieldError($msg=null){
        if(!isset($msg))$msg='Error in field "'.$this->caption.'"';

        $this->form->js(true)
            ->atk4_form('fieldError',$this->short_name,$msg)
            ->execute();

        $this->form->errors[$this->short_name]=$msg;
    }
    function setNoSave(){
        // Field value will not be saved into defined source (such as database)
        $this->no_save=true;
        return $this;
    }
    function disable(){
        // sets 'disabled' property and setNoSave()
        $this->setAttr('disabled');
        $this->setNoSave();
        $this->disabled=true;
        return $this;
    }
    function isDisabled(){
        return $this->disabled;
    }
    function set($value){
        // Use this function when you want to assign $this->value.
        // If you use this function, your field will operate in AJAX mode.
        $this->value=$value;
        return $this;
    }
    /** Position can be either 'before' or 'after' */
    function addButton($label,$options=array()){
        $position='after';
        if(is_string($options)){
            $position=$options;
        }else{
            if(isset($options['position']))$position=$options['position'];
        }
        if($position=='after'){
            return $this->afterField()->add('Button',$options)->setLabel($label);
        }else{
            return $this->beforeField()->add('Button',$options)->setLabel($label);
        }
    }
    function beforeField(){
        if(!$this->template->hasTag('after_input')){
            $el=$this->owner->add('HtmlElement');
            $this->owner->add('Order')->move($el,'before',$this)->now();
            return $el;
        }
        if(!$this->button_prepend)return $this->button_prepend=$this
            ->add('HtmlElement',null,'before_input')->addClass('input-cell');
        return $this->button_prepend;
    }
    function afterField(){
        if(!$this->template->hasTag('after_input')){
            $el=$this->owner->add('HtmlElement');
            $this->owner->add('Order')->move($el,'after',$this)->now();
            return $el;
        }
        if(!$this->button_append)return $this->button_append=$this
            ->add('HtmlElement',null,'after_input')->addClass('input-cell');
        return $this->button_append;
    }
    function aboveField(){
        return $this->add('HtmlElement',null,'before_field');
    }
    function belowField(){
        return $this->add('HtmlElement',null,'after_field');
    }
    function setComment($text=''){
        $this->belowField()->setElement('ins')->set($text);
        return $this;
    }
    function addComment($text=''){
        return $this->belowField()->setElement('ins')->set($text);
    }
    function get(){
        return $this->value;
    }
    function setClass($class){
        $this->attr['class']=$class;
        return $this;
    }
    function addClass($class){
        $this->attr['class'].=($this->attr['class']?' ':'').$class;
        return $this;
    }
    function setAttr($attr,$value=undefined){
        if(is_array($attr)&&$value===undefined){
            foreach($attr as $k=>$v) $this->setAttr($k,$v);
            return $this;
        }
        if($attr){
            $this->attr[$attr] = $value===undefined?'true':$value;
        }
        return $this;
    }
    function setProperty($property,$value){ // synonym, setAttr is preferred
        return $this->setAttr($property,$value);
    }
    function setFieldHint($var_args=null){
        /* Adds a hint after this field. Thes will call Field_Hint->set()
           with same arguments you called this funciton.
         */
        if(!$this->template->hasTag('after_field'))return $this;
        $hint=$this->add('Form_Hint',null,'after_field');
        call_user_func_array(array($hint,'set'), func_get_args());
        return $this;
    }
    function loadPOST(){
        if(isset($_POST[$this->name]))$this->set($_POST[$this->name]);
        else $this->set($this->default_value);
        $this->normalize();
    }
    function normalize(){
        /* Normalization will make sure that entry conforms to the field type. 
           Possible trimming, rounding or length enforcements may happen. */
        $this->hook('normalize');
    }
    function validate(){
        // NoSave and disabled fields should not be validated
        if($this->disabled || $this->no_save)return true;
        // we define "validate" hook, so actual validators could hook it here
        // and perform their checks
        if(is_bool($result = $this->hook('validate')))return $result;
    }
    /** @private - handles field validation callback output */
    function _validateField($caller,$condition,$msg){
        $ret=call_user_func($condition,$this);

        if($ret===false){
            if(is_null($msg))$msg=$this->api->_('Error in ').$this->caption;
            $this->displayFieldError($msg);
        }elseif(is_string($ret)){
            $this->displayFieldError($ret);
        }
        return $this;
    }
    /** Executes a callback. If callback returns string, shows it as error message.
     * If callback returns "false" shows either $msg or a standard error message
     * about field being incorrect */
    function validateField($condition,$msg=null){
        if(is_callable($condition)){
            $this->addHook('validate',array($this,'_validateField'),array($condition,$msg));
        }else{
            $this->addHook('validate',$s='if(!('.$condition.'))$this->displayFieldError("'.
                ($msg?$msg:'Error in ".$this->caption."').'");');
        }
        return $this;
    }
    function _validateNotNull($field){
        if($field->get()==="" || is_null($field->get()))return false;
    }
    /** Adds "X is a mandatory field" message */
    function validateNotNULL($msg=null){
        $this->setMandatory();
        if($msg && $msg!==true){
            $msg=$this->api->_($msg);
        }else{
            $msg=sprintf($this->api->_('%s is a mandatory field'),$this->caption);
        }
        $this->validateField(array($this,'_validateNotNull'),$msg);
        return $this;
    }
    function getInput($attr=array()){
        // This function returns HTML tag for the input field. Derived classes
        // should inherit this and add new properties if needed
        return $this->getTag('input',
                array_merge(array(
                        'name'=>$this->name,
                        'data-shortname'=>$this->short_name,
                        'id'=>$this->name,
                        'value'=>$this->value,
                        ),$attr,$this->attr)
                );
    }
    function setSeparator($separator){
        $this->separator = $separator;
        return $this;
    }
    function render(){
        if($this->show_input_only){
            $this->output($this->getInput());
            return;
        }
        if(!$this->error_template)$this->error_template = $this->form->template_chunks['field_error'];
        if((!property_exists($this, 'mandatory_template')) || (!$this->mandatory_template))$this->mandatory_template=$this->form->template_chunks['field_mandatory'];
        $this->template->trySet('field_caption',$this->caption?($this->caption.$this->separator):'');
        $this->template->trySet('field_name',$this->name);
        $this->template->trySet('field_comment',$this->comment);
        // some fields may not have field_input tag at all...
        if($this->button_prepend || $this->button_append){
            $this->field_prepend.='<div class="input-cell expanded">';
            $this->field_append='</div>'.$this->field_append;
            $this->template->trySetHTML('input_row_start','<div class="input-row">');
            $this->template->trySetHTML('input_row_stop','</div>');
        }
        $this->template->trySetHTML('field_input',$this->field_prepend.$this->getInput().$this->field_append);
        $this->template->trySetHTML('field_error',
                isset($this->form->errors[$this->short_name])?
                $this->error_template->set('field_error_str',$this->form->errors[$this->short_name])->render()
                :''
                );
        if (is_object($this->mandatory_template)) {
            $this->template->trySet('field_mandatory',$this->isMandatory()?$this->mandatory_template->render():'');
        }
        $this->output($this->template->render());
    }
    function getTag($tag, $attr=null, $value=null){
        /**
         * Draw HTML attribute with supplied attributes.
         *
         * Short description how this getTag may be used:
         *
         * Use get tag to build HTML tag.
         * echo getTag('img',array('src'=>'foo.gif','border'=>0);
         *
         * The unobvius advantage of this function is ability to merge
         * attribute arrays. For example, if you have function, which
         * must display img tag, you may add optional $attr argument
         * to this function.
         *
         * function drawImage($src,$attr=array()){
         *     echo getTag('img',array_merge(array('src'=>$src),$attr));
         * }
         *
         * so calling drawImage('foo.gif') will echo: <img src="foo.gif">
         *
         * The benefit from such a function shows up when you use 2nd argument:
         *
         * 1. adding additional attributes
         * drawImage('foo.gif',array('border'=>0'));
         * --> <img src="foo.gif" border="0">
         * (NOTE: you can even have attr templates!)
         *
         * 2. adding no-value attributes, such as nowrap:
         * getTag('td',arary('nowrap'=>true));
         * --> <td nowrap>
         *
         * 3. disabling some attributes.
         * drawImage('foo.gif',array('src'=>false));
         * --> <img>
         *
         * 4. re-defining attributes
         * drawImage('foo.gif',array('src'=>'123'));
         * --> <img src="123">
         *
         * 5. or you even can re-define tag itself
         * drawImage('foo.gif',array(
         *                      ''=>'input',
         *                      'type'=>'picture'));
         * --> <input type="picture" src="foo.gif">
         *
         * 6. xml-valid tags without closing tag
         * getTag('img/',array('src'=>'foo.gif'));
         * --> <img src=>"foo.gif"/>
         *
         * 7. closing tags
         * getTag('/td');
         * --> </td>
         *
         * 8. using $value will add $value after tag followed by closing tag
         * getTag('a',array('href'=>'foo.html'),'click here');
         * --> <a href="foo.html">click here</a>
         *
         * 9. you may not skip attribute argument.
         * getTag('b','text in bold');
         * --> <b>text in bold</b>
         *
         * 10. nesting
         * getTag('a',array('href'=>'foo.html'),getTag('b','click here'));
         * --> <a href="foo.html"><b>click here</b></a>
         */
        if(is_string($attr)){
            $value=$attr;
            $attr=null;
        }
        if(!$attr){
            return "<$tag>".($value?$value."</$tag>":"");
        }
        $tmp = array();
        if(substr($tag,-1)=='/'){
            $tag = substr($tag,0,-1);
            $postfix = '/';
        } else $postfix = '';
        foreach ($attr as $key => $val) {
            if($val === false) continue;
            if($val === true) $tmp[] = "$key";
            elseif($key === '')$tag=$val;
            else $tmp[] = "$key=\"".htmlspecialchars($val)."\"";
        }
        return "<$tag ".join(' ',$tmp).$postfix.">".($value?$value."</$tag>":"");
    }

    function setSource(){
        return call_user_func_array(array($this->form,'setSource'),func_get_args());
    }
    function addField(){
        return call_user_func_array(array($this->form,'addField'),func_get_args());
        //throw new ObsoleteException('$form->addField() now returns Field object and not Form. Do not chain it.');
    }
}

///////// Because many fields are really simple extenions of the base-line field,
///////// they are defined here.

// Visually different fields

