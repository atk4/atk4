<?php
/**
 * Implementation of abstract form's field.
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   See file COPYING
 *
 * @version     $Id$
 */
abstract class Form_Field extends AbstractView
{
    /**
     * Description of the field shown next to it on the form.
     */
    public $error_template;      // template used to put errors on the field line
    public $mandatory_template;  // template used to mark mandatory fields
    public $caption;
    protected $value = null;       // use $this->get(), ->set().
    public $short_name = null;
    public $attr = array();
    public $no_save = null;

    public $comment = '&nbsp;';
    protected $disabled = false;
    protected $mandatory = false;
    public $default_value = null;

    // Field customization
    private $separator = ':';
    public $show_input_only;
    public $form = null;

    public $button_prepend = null;
    public $button_append = null;

    public function init()
    {
        parent::init();
        if (@$_GET[$this->owner->name.'_cut_field'] == $this->name) {
            $this->app->addHook('pre-render', array($this, '_cutField'));
        }

        /* TODO: finish refactoring
        // find the form
        $obj=$this->owner;
        while(!$obj instanceof Form){
            if($obj === $this->app)throw $this->exception('You must add fields only inside Form');

            $obj = $obj->owner;
        }
        $this->setForm($obj);
         */
    }
    public function setForm($form)
    {
        $form->addHook('loadPOST', $this);
        $form->addHook('validate', [$this,'performValidation']);
        $this->form = $form;
        $this->form->data[$this->short_name] = ($this->value !== null ? $this->value : $this->default_value);
        $this->value = &$this->form->data[$this->short_name];

        return $this;
    }

    public function _cutField()
    {
        // this method is used by ui.atk4_form, when doing reloadField();
        unset($_GET['cut_object']);
        $this->recursiveRender();
        if ($this->app->jquery) {
            $this->app->jquery->getJS($this);
        }
        throw new Exception_StopRender(
            $this->template->renderRegion($this->template->tags['before_field']).
            $this->getInput().
            $this->template->renderRegion($this->template->tags['after_field'])
        );
    }
    public function setMandatory($mandatory = true)
    {
        $this->mandatory = $mandatory;

        return $this;
    }
    public function setReadonly($readonly = true)
    {
        $this->readonly = $readonly;

        return $this;
    }
    public function isMandatory()
    {
        return $this->mandatory;
    }
    public function setCaption($_caption)
    {
        $this->caption = $this->app->_($_caption);
        if ($this->show_input_only || !$this->template->hasTag('field_caption')) {
            $this->setAttr('placeholder', $this->caption);
        }

        return $this;
    }
    public function displayFieldError($msg = null)
    {
        if (!isset($msg)) {
            $msg = 'Error in field "'.$this->caption.'"';
        }

        $this->form->js(true)
            ->atk4_form('fieldError', $this->short_name, $msg)
            ->execute();

        $this->form->errors[$this->short_name] = $msg;
    }
    public function setNoSave()
    {
        // Field value will not be saved into defined source (such as database)
        $this->no_save = true;

        return $this;
    }
    public function disable()
    {
        // sets 'disabled' property and setNoSave()
        $this->setAttr('disabled');
        $this->setNoSave();
        $this->disabled = true;

        return $this;
    }
    public function isDisabled()
    {
        return $this->disabled;
    }
    public function set($value)
    {
        // Use this function when you want to assign $this->value.
        // If you use this function, your field will operate in AJAX mode.
        $this->value = $value;

        return $this;
    }
    /** Position can be either 'before' or 'after' */
    public function addButton($label, $options = array())
    {
        $position = 'after';
        if (is_string($options)) {
            $position = $options;
        } else {
            if (isset($options['position'])) {
                $position = $options['position'];
            }
        }
        if ($position == 'after') {
            $button = $this->afterField()->add('Button', $options)->set($label);
        } else {
            $button = $this->beforeField()->add('Button', $options)->set($label);
        }
        $this->js('change', $button->js()->data('val', $this->js()->val()));

        return $button;
    }

    /** Layout changes in response to adding more elements before / after */
    public $_icon = null;
    /** Wraps input field into a <span> to align icon correctly */
    public function addIcon($icon, $link = null)
    {
        if (!$this->_icon) {
            $this->_icon = $this->add('Icon', null, 'icon');
        }
        $this->_icon->set($icon);
        if ($link) {
            $this->_icon->setElement('a')
                ->setAttr('href', $link);
        }

        $this->template->trySetHTML('before_input', '<span class="atk-input-icon atk-jackscrew">');
        $this->template->trySetHTML('after_input', '</span>');

        return $this->_icon;
    }

    /** Will enable field wrappin inside a atk-cells/atk-cell block */
    public $_use_cells = false;
    /**
     * @return View
     */
    public function beforeField()
    {
        $this->_use_cells = true;
        /** @type View $v */
        $v = $this->add('View', null, 'before_field');

        return $v->addClass('atk-cell');
    }
    /**
     * @return View
     */
    public function afterField()
    {
        $this->_use_cells = true;
        /** @type View $v */
        $v = $this->add('View', null, 'after_field');

        return $v->addClass('atk-cell');
    }
    /**
     * @return View
     */
    public function aboveField()
    {
        return $this->add('View', null, 'above_field');
    }
    /**
     * @return View
     */
    public function belowField()
    {
        return $this->add('View', null, 'below_field');
    }
    public function setComment($text = '')
    {
        $this->belowField()->setElement('ins')->set($text);

        return $this;
    }
    public function addComment($text = '')
    {
        return $this->belowField()->setElement('ins')->set($text);
    }
    public function get()
    {
        return $this->value;
    }
    public function setClass($class)
    {
        $this->attr['class'] = $class;

        return $this;
    }
    public function addClass($class)
    {
        $this->attr['class'] .= ($this->attr['class'] ? ' ' : '').$class;

        return $this;
    }
    public function setAttr($attr, $value = UNDEFINED)
    {
        if (is_array($attr) && $value === UNDEFINED) {
            foreach ($attr as $k => $v) {
                $this->setAttr($k, $v);
            }

            return $this;
        }
        if ($attr) {
            $this->attr[$attr] = $value === UNDEFINED ? 'true' : $value;
        }

        return $this;
    }
    /** synonym, setAttr is preferred */
    public function setProperty($property, $value)
    {
        return $this->setAttr($property, $value);
    }
    public function setFieldHint($var_args = null)
    {
        /* Adds a hint after this field. Thes will call Field_Hint->set()
           with same arguments you called this funciton.
         */
        if (!$this->template->hasTag('after_field')) {
            return $this;
        }
        $hint = $this->add('Form_Hint', null, 'after_field');
        call_user_func_array(array($hint, 'set'), func_get_args());

        return $this;
    }
    public function loadPOST()
    {
        if (isset($_POST[$this->name])) {
            $this->set($_POST[$this->name]);
        } else {
            $this->set($this->default_value);
        }
        $this->normalize();
    }
    public function normalize()
    {
        /* Normalization will make sure that entry conforms to the field type.
           Possible trimming, rounding or length enforcements may happen. */
        $this->hook('normalize');
    }

    /**
     * This method has been refactored to integrate with Controller_Validator.
     */
    public function validate($rule = null)
    {
        if (is_null($rule)) {
            throw $this->exception('Incorrect usage of field validation');
        }

        if (is_string($rule)) {
            // If string is passed, prefix with the field name
            $rule = $this->short_name.'|'.$rule;
        } elseif (is_array($rule)) {
            // if array is passed, prepend with the rule
            array_unshift($rule, $this->short_name);
            $rule = array($rule);
        } elseif (is_callable($rule)) {
            // callable or something else is passed. Wrap into array.
            $rule = array(array($this->short_name, $rule));
        }

        $this->form->validate($rule);

        return $this;
    }

    /**
     * Used to be called validate(), this method is called when
     * field should do some of it's basic validation done.
     */
    public function performValidation()
    {
    }

    /** @private - handles field validation callback output */
    public function _validateField($caller, $condition, $msg)
    {
        $ret = call_user_func($condition, $this);

        if ($ret === false) {
            if (is_null($msg)) {
                $msg = $this->app->_('Error in ').$this->caption;
            }
            $this->displayFieldError($msg);
        } elseif (is_string($ret)) {
            $this->displayFieldError($ret);
        }

        return $this;
    }
    /** Executes a callback. If callback returns string, shows it as error message.
     * If callback returns "false" shows either $msg or a standard error message.
     * about field being incorrect */
    public function validateField($condition, $msg = null)
    {
        if (is_callable($condition)) {
            $this->addHook('validate', array($this, '_validateField'), array($condition, $msg));
        } else {
            $this->addHook('validate', 'if(!('.$condition.'))$this->displayFieldError("'.
                ($msg ?: 'Error in '.$this->caption).'");');
        }

        return $this;
    }
    public function _validateNotNull($field)
    {
        if ($field->get() === '' || is_null($field->get())) {
            return false;
        }
    }
    /** Adds "X is a mandatory field" message */
    public function validateNotNULL($msg = null)
    {
        $this->setMandatory();
        if ($msg && $msg !== true) {
            $msg = $this->app->_($msg);
        } else {
            $msg = sprintf($this->app->_('%s is a mandatory field'), $this->caption);
        }
        $this->validateField(array($this, '_validateNotNull'), $msg);

        return $this;
    }
    public function getInput($attr = array())
    {
        // This function returns HTML tag for the input field. Derived classes
        // should inherit this and add new properties if needed
        return $this->getTag('input', array_merge(
            array(
                'name' => $this->name,
                'data-shortname' => $this->short_name,
                'id' => $this->name,
                'value' => $this->value,
                ),
            $attr,
            $this->attr
        ));
    }
    public function setSeparator($separator)
    {
        $this->separator = $separator;

        return $this;
    }
    public function render()
    {
        if (!$this->template) {
            throw $this->exception('Field template was not properly loaded')
                ->addMoreInfo('name', $this->name);
        }
        if ($this->show_input_only) {
            $this->output($this->getInput());

            return;
        }
        if (!$this->error_template) {
            $this->error_template = $this->form->template_chunks['field_error'];
        }
        if ((!property_exists($this, 'mandatory_template')) || (!$this->mandatory_template)) {
            $this->mandatory_template = $this->form->template_chunks['field_mandatory'];
        }
        $this->template->trySet('field_caption', $this->caption ? ($this->caption.$this->separator) : '');
        $this->template->trySet('field_name', $this->name);
        $this->template->trySet('field_comment', $this->comment);
        // some fields may not have field_input tag at all...
        if ($this->_use_cells) {
            $this->template->trySetHTML('cells_start', '<div class="atk-cells atk-input-combo">');
            $this->template->trySetHTML('cells_stop', '</div>');
            $this->template->trySetHTML('input_cell_start', '<div class="atk-cell atk-jackscrew">');
            $this->template->trySetHTML('input_cell_stop', '</div>');
        }
        $this->template->trySetHTML('field_input', $this->getInput());
        $this->template->trySetHTML(
            'field_error',
            isset($this->form->errors[$this->short_name])
                ? $this->error_template->set('field_error_str', $this->form->errors[$this->short_name])->render()
                : ''
        );
        if (is_object($this->mandatory_template)) {
            $this->template->trySetHTML(
                'field_mandatory',
                $this->isMandatory() ? $this->mandatory_template->render() : ''
            );
        }
        $this->output($this->template->render());
    }
    public function getTag($tag, $attr = null, $value = null)
    {
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
        if (is_string($attr)) {
            $value = $attr;
            $attr = null;
        }
        if (!$attr) {
            return "<$tag>".($value ? $value."</$tag>" : '');
        }
        $tmp = array();
        if (substr($tag, -1) == '/') {
            $tag = substr($tag, 0, -1);
            $postfix = '/';
        } else {
            $postfix = '';
        }
        foreach ($attr as $key => $val) {
            if ($val === false) {
                continue;
            }
            if ($val === true) {
                $tmp[] = "$key";
            } elseif ($key === '') {
                $tag = $val;
            } else {
                $tmp[] = "$key=\"".$this->app->encodeHtmlChars($val).'"';
            }
        }

        return "<$tag ".implode(' ', $tmp).$postfix.'>'.($value ? $value."</$tag>" : '');
    }

    public function destroy()
    {
        parent::destroy();
        if ($this->form != $this->owner) {
            $this->form->_removeElement($this->short_name);
        }
    }

    public function defaultTemplate()
    {
        return array('form_field');
    }
}
