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

    public $comment='&nbsp;';
    protected $disabled=false;
    protected $mandatory=false;
    public $default_value=null;

    // Field customization
    private $separator=':';
    public $show_input_only;
    public $form=null;

    public $button_prepend=null;
    public $button_append=null;

    function init(){
        parent::init();
        if(@$_GET[$this->owner->name.'_cut_field']==$this->name){
            $this->api->addHook('pre-render',array($this,'_cutField'));
        }

        /** TODO: finish refactoring
        // find the form
        $obj=$this->owner;
        while(!$obj instanceof Form){
            if($obj === $this->app)throw $this->exception('You must add fields only inside Form');

            $obj = $obj->owner;
        }
        $this->setForm($obj);
         */

    }
    function setForm($form){
        $form->addHook('loadPOST',$this);
        $form->addHook('validate',$this);
        $this->form=$form;
        $this->form->data[$this->short_name] = ($this->value!==null ? $this->value : $this->default_value);
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
        if ($this->show_input_only || !$this->template->hasTag('field_caption')) {
            $this->setAttr('placeholder',$this->caption);
        }
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
            $button = $this->afterField()->add('Button',$options)->set($label);
        }else{
            $button = $this->beforeField()->add('Button',$options)->set($label);
        }
        $this->js('change', $button->js()->data('val', $this->js()->val()) );
        return $button;
    }

    /** Layout changes in response to adding more elements before / after */

    public $_icon=null;
    /** Wraps input field into a <span> to align icon correctly */
    function addIcon($icon,$link=null)
    {
        if(!$this->_icon){
            $this->_icon=$this->add('Icon',null,'icon');
        }
        $this->_icon->set($icon);
        if($link){
            $this->_icon->setElement('a')
                ->setAttr('href',$link);
        }

        $this->template->trySetHTML('before_input','<span class="atk-input-icon atk-jackscrew">');
        $this->template->trySetHTML('after_input','</span>');

        return $this->_icon;
    }

    /** Will enable field wrappin inside a atk-cells/atk-cell block */
    public $_use_cells=false;
    function beforeField(){
        $this->_use_cells=true;
        return $this->add('View',null,'before_field')->addClass('atk-cell');
    }
    function afterField(){
        $this->_use_cells=true;
        return $this->add('View',null,'after_field')->addClass('atk-cell');
    }
    function aboveField(){
        return $this->add('View',null,'above_field');
    }
    function belowField(){
        return $this->add('View',null,'below_field');
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

    /**
      * This method has been refactored to integrate with Controller_Validator
      */
    function validate($rule = null){
        if(is_null($rule)){
            throw $this->exception('Incorrect usage of field validation');
        }
        if(is_string($rule))$rule = $this->short_name.'|'.$rule;
        if(is_array($rule))array_unsift($rule,$this->short_name);

        $this->form->validate($rule);
        return $this;
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
            $this->addHook('validate','if(!('.$condition.'))$this->displayFieldError("'.
                ($msg?:'Error in '.$this->caption).'");');
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

        if(!$this->template){
            throw $this->exception('Field template was not properly loaded')
                ->addMoreInfo('name',$this->name);
        }
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
        if($this->_use_cells){
            $this->template->trySetHTML('cells_start','<div class="atk-cells atk-input-combo">');
            $this->template->trySetHTML('cells_stop','</div>');
            $this->template->trySetHTML('input_cell_start','<div class="atk-cell atk-jackscrew">');
            $this->template->trySetHTML('input_cell_stop','</div>');
        }
        $this->template->trySetHTML('field_input',$this->getInput());
        $this->template->trySetHTML('field_error',
                isset($this->form->errors[$this->short_name])?
                $this->error_template->set('field_error_str',$this->form->errors[$this->short_name])->render()
                :''
                );
        if (is_object($this->mandatory_template)) {
            $this->template->trySetHTML('field_mandatory',$this->isMandatory()?$this->mandatory_template->render():'');
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
            else $tmp[] = "$key=\"".$this->api->encodeHtmlChars($val)."\"";
        }
        return "<$tag ".join(' ',$tmp).$postfix.">".($value?$value."</$tag>":"");
    }

    function destroy(){
        parent::destroy();
        if ($this->form != $this->owner) {
            $this->form->_removeElement($this->short_name);
        }
    }

    function defaultTemplate(){
        return array('form_field');
    }
}
