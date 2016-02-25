<?php
/**
 * This class implements generic form, which you can actually use without
 * redeclaring it. Just add fields, buttons and use execute method.
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   See file COPYING
 *
 * @version     $Id$
 */
class Form_Basic extends View implements ArrayAccess
{
    public $layout = null; // template of layout if form has one

    // Here we will have a list of errors occured in the form, when we tried to
    // submit it. field_name => error
    public $errors = array();

    // Those templates will be used when rendering form and fields
    public $template_chunks = array();

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
    protected $loaded_from_db = false;  // true - update() will try updating existing row. false - it would insert new
    protected $ajax_submits = array();    // contains AJAX instances assigned to buttons
    protected $get_field = null;          // if condition was passed to a form through GET, contains a GET field name
    protected $conditions = array();

    public $js_widget = 'ui.atk4_form';
    public $js_widget_arguments = array();

    public $default_exception = 'Exception_ValidityCheck';
    public $default_controller = 'Controller_MVCForm';

    public $validator = null;

    /**
     * Normally form fields are inserted using a form template. If you.
     *
     * @var [type]
     */
    public $search_for_field_spots;

    public $dq = null;
    public function init()
    {
        /*
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
        $this->app->addHook('pre-exec', array($this, 'loadData'));
        $this->app->addHook('pre-render-output', array($this, 'lateSubmit'));
        $this->app->addHook('submitted', $this);

        $this->addHook('afterAdd', $this);
    }
    public function afterAdd($p, $c)
    {
        if ($c instanceof AbstractView) {
            $c->addHook('afterAdd', $this);
            $c->addMethod('addField', $this);
            $c->addMethod('addSubmit', $this);
        }
    }

    protected function getChunks()
    {
        // commonly replaceable chunks
        $this->grabTemplateChunk('form_comment');
        $this->grabTemplateChunk('form_separator');
        $this->grabTemplateChunk('form_line'); // form line template,must contain field_caption,field_input,field_error
        if ($this->template->is_set('hidden_form_line')) {
            $this->grabTemplateChunk('hidden_form_line');
        }
        $this->grabTemplateChunk('field_error');    // template for error code, must contain field_error_str
        $this->grabTemplateChunk('field_mandatory');// template for marking mandatory fields

        // other grabbing will be done by field themselves as you will add them
        // to the form. They will try to look into this template, and if you
        // don't have apropriate templates for them, they will use default ones.
        $this->template_chunks['form'] = $this->template;
        $this->template_chunks['form']->del('Content');
        $this->template_chunks['form']->del('form_buttons');
        $this->template_chunks['form']->trySet('form_name', $this->name.'_form');
        $this->template_chunks['form']
            ->set('form_action', $this->app->url(null, array('submit' => $this->name)));

        return $this;
    }

    public function defaultTemplate($template = null, $tag = null)
    {
        return array('form');
    }
    public function grabTemplateChunk($name)
    {
        if ($this->template->is_set($name)) {
            $this->template_chunks[$name] = $this->template->cloneRegion($name);
        } else {
            unset($this->template_chunks[$name]);
            //return $this->fatal('missing form tag: '.$name);
            // hmm.. i wonder what ? :)
        }
    }
    /**
     * Should show error in field. Override this method to change form default alert.
     *
     * @param object $field Field instance that caused error
     * @param string $msg   message to show
     */
    public function showAjaxError($field, $msg)
    {
        // Avoid deprecated function use in reference field, line 246
        return $this->displayError($field, $msg);
    }

    public function displayError($field = null, $msg = null)
    {
        if (!$field) {
            // Field is not defined
            // TODO: add support for error in template
            $this->js()->univ()->alert($msg ?: 'Error in form')->execute();
        }
        if (!is_object($field)) {
            $field = $this->getElement($field);
        }

        $fn = $this->js_widget ? str_replace('ui.', '', $this->js_widget) : 'atk4_form';
        $this->js()->$fn('fieldError', $field->short_name, $msg)->execute();
    }
    public function error($field, $text = null)
    {
        $this->getElement($field)->displayFieldError($text);
    }
    public function addField($type, $options = null, $caption = null, $attr = null)
    {
        $insert_into = $this->layout ?: $this;

        if (is_object($type) && $type instanceof AbstractView && !($type instanceof Form_Field)) {

            // using callback on a sub-view
            $insert_into = $type;
            list(, $type, $options, $caption, $attr) = func_get_args();
        }

        if ($options === null) {
            $options = $type;
            $type = 'Line';
        }

        if (is_array($options)) {
            $name = isset($options['name']) ? $options['name'] : null;
        } else {
            $name = $options; // backward compatibility
        }
        $name = preg_replace('|[^a-z0-9-_]|i', '_', $name);

        if ($caption === null) {
            $caption = ucwords(str_replace('_', ' ', $name));
        }

        /* normalzie name and put name back in options array */
        $name = $this->app->normalizeName($name);
        if (is_array($options)) {
            $options['name'] = $name;
        } else {
            $options = array('name' => $name);
        }

        $map = array(
            'dropdown' => 'DropDown',
            'checkboxlist' => 'CheckboxList',
            'hidden' => 'Hidden',
            'text' => 'Text',
            'line' => 'Line',
            'upload' => 'Upload',
            'radio' => 'Radio',
            'checkbox' => 'Checkbox',
            'password' => 'Password',
            'timepicker' => 'TimePicker',
            );
        $key = strtolower($type);
        $class = array_key_exists($key, $map) ? $map[$key] : $type;

        $class = $this->app->normalizeClassName($class, 'Form_Field');

        if ($insert_into === $this) {
            $template = $this->template->cloneRegion('form_line');
            $field = $this->add($class, $options, null, $template);
        } else {
            if ($insert_into->template->hasTag($name)) {
                $template = $this->template->cloneRegion('field_input');
                $options['show_input_only'] = true;
                $field = $insert_into->add($class, $options, $name);
            } else {
                $template = $this->template->cloneRegion('form_line');
                $field = $insert_into->add($class, $options, null, $template);
            }

            // Keep Reference, for $form->getElement().
            $this->elements[$options['name']] = $field;
        }

        $field->setCaption($caption);
        $field->setForm($this);
        $field->template->trySet('field_type', strtolower($type));

        if ($attr) {
            if ($this->app->compat) {
                $field->setAttr($attr);
            } else {
                throw $this->exception('4th argument to addField is obsolete');
            }
        }

        return $field;
    }
    public function importFields($model, $fields = undefined)
    {
        $this->add($this->default_controller)->importFields($model, $fields);
    }
    public function addSeparator($class = '', $attr = array())
    {
        if (!isset($this->template_chunks['form_separator'])) {
            return $this->add('View')->addClass($class);
        }
        $c = clone $this->template_chunks['form_separator'];
        $c->trySet('fieldset_class', 'atk-cell '.$class);
        $this->template->trySet('fieldset_class', 'atk-cell');
        $this->template->trySet('form_class', 'atk-cells atk-cells-gutter-large');

        if (is_array($attr) && !empty($attr)) {
            foreach ($attr as $k => $v) {
                $c->appendHTML('fieldset_attributes', ' '.$k.'="'.$v.'"');
            }
        }

        return $this->add('Html')->set($c->render());
    }

    // Operating with field values
    //
    // {{{ ArrayAccess support
    public function offsetExists($name)
    {
        return $f = $this->hasElement($name) && $f instanceof Form_Field;
    }
    public function offsetGet($name)
    {
        return $this->get($name);
    }
    public function offsetSet($name, $val)
    {
        $this->set($name, $val);
    }
    public function offsetUnset($name)
    {
        $this->set($name, null);
    }
    // }}}
    //
    public function get($field = null)
    {
        if (!$field) {
            return $this->data;
        }

        return $this->data[$field];
    }
    /*
     * temporarily disabled. TODO: will be implemented with abstract datatype
    function setSource($table,$db_fields=null){
        if(is_null($db_fields)){
            $db_fields=array();
            foreach($this->elements as $key=>$el){
                if(!($el instanceof Form_Field))continue;
                if($el->no_save)continue;
                $db_fields[]=$key;
            }
        }
        $this->dq = $this->app->db->dsql()
            ->table($table)
            ->field('*',$table)
            ->limit(1);
        return $this;
    }
     */
    public function set($field_or_array, $value = undefined)
    {
        // We use undefined, because 2nd argument of "null" is meaningfull
        if (is_array($field_or_array)) {
            foreach ($field_or_array as $key => $val) {
                if (isset($this->elements[$key]) && ($this->elements[$key] instanceof Form_Field)) {
                    $this->set($key, $val);
                }
            }

            return $this;
        }

        if (!isset($this->elements[$field_or_array])) {
            foreach ($this->elements as $key => $val) {
                echo "$key<br />";
            }
            throw new BaseException("Trying to set value for non-existant field $field_or_array");
        }
        if ($this->elements[$field_or_array] instanceof Form_Field) {
            $this->elements[$field_or_array]->set($value);
        } else {
            //throw new BaseException("Form fields must inherit from Form_Field ($field_or_array)");
        }

        return $this;
    }
    public function getAllFields()
    {
        return $this->get();
    }
    public function addSubmit($label = 'Save', $name = null)
    {
        if (is_object($label) && $label instanceof AbstractView && !($label instanceof Form_Field)) {
            // using callback on a sub-view
            $insert_into = $label;
            list(, $label, $name) = func_get_args();
            $submit = $insert_into->add('Form_Submit', array('name' => $name, 'form' => $this));
        } else {
            if ($this->layout && $this->layout->template->hasTag('FormButtons')) {
                $submit = $this->layout->add('Form_Submit', array('name' => $name, 'form' => $this), 'FormButtons');
            } else {
                $submit = $this->add('Form_Submit', array('name' => $name, 'form' => $this), 'form_buttons');
            }
        }

        $submit
            //->setIcon('ok') - removed as per dmity's request
            ->set($label)
            ->setNoSave();

        return $submit;
    }
    public function addButton($label = 'Button', $name = null)
    {
        if ($this->layout && $this->layout->template->hasTag('FormButtons')) {
            $button = $this->layout->add('Button', $name, 'FormButtons');
        } else {
            $button = $this->add('Button', $name, 'form_buttons');
        }
        $button->setLabel($label);

        return $button;
    }

    public function loadData()
    {
        /**
         * This call will be sent to fields, and they will initialize their values from $this->data.
         */
        if (!is_null($this->bail_out)) {
            return;
        }
        $this->hook('post-loadData');
    }

    public function isLoadedFromDB()
    {
        return $this->loaded_from_db;
    }
    /* obsolete in 4.3 - use save() */
    public function update()
    {
        return $this->save();
    }
    public function save()
    {
        // TODO: start transaction here
        try {
            if ($this->hook('update')) {
                return $this;
            }

            if (!($m = $this->getModel())) {
                throw new BaseException("Can't save, model not specified");
            }
            if (!is_null($this->get_field)) {
                $this->app->stickyForget($this->get_field);
            }
            foreach ($this->elements as $short_name => $element) {
                if ($element instanceof Form_Field) {
                    if (!$element->no_save) {
                        //if(is_null($element->get()))
                        $m->set($short_name, $element->get());
                    }
                }
            }
            $m->save();
        } catch (BaseException $e) {
            if ($e instanceof Exception_ValidityCheck) {
                $f = $e->getField();
                if ($f && is_string($f) && $fld = $this->hasElement($f)) {
                    $fld->displayFieldError($e->getMessage());
                } else {
                    $this->js()->univ()->alert($e->getMessage())->execute();
                }
            }
            if ($e instanceof Exception_ForUser) {
                $this->js()->univ()->alert($e->getMessage())->execute();
            }
            throw $e;
        }
    }
    public function submitted()
    {
        /*
         * Default down-call submitted will automatically call this method if form was submitted
         */
        // We want to give flexibility to our controls and grant them a chance
        // to hook to those spots here.
        // On Windows platform mod_rewrite is lowercasing all the urls.
        if ($_GET['submit'] != $this->name) {
            return;
        }
        if (!is_null($this->bail_out)) {
            return $this->bail_out;
        }

        $this->hook('loadPOST');
        try {
            $this->hook('validate');
            $this->hook('post-validate');

            if (!empty($this->errors)) {
                return false;
            }

            if (($output = $this->hook('submit', array($this)))) {
                /* checking if anything usefull in output */
                if (is_array($output)) {
                    $has_output = false;
                    foreach ($output as $row) {
                        if ($row) {
                            $has_output = true;
                            $output = $row;
                            break;
                        }
                    }
                    if (!$has_output) {
                        return true;
                    }
                }
                /* TODO: need logic re-check here + test scripts */
                //if(!is_array($output))$output=array($output);
                // already array
                if ($has_output) {
                    if ($output instanceof jQuery_Chain) {
                        $this->js(null, $output)->execute();
                    } elseif (is_string($output)) {
                        $this->js(null, $this->js()->reload())->univ()->successMessage($output)->execute();
                    }
                }
            }
        } catch (BaseException $e) {
            if ($e instanceof Exception_ValidityCheck) {
                $f = $e->getField();
                if ($f && is_string($f) && $fld = $this->hasElement($f)) {
                    $fld->displayFieldError($e->getMessage());
                } else {
                    $this->js()->univ()->alert($e->getMessage())->execute();
                }
            }
            if ($e instanceof Exception_ForUser) {
                $this->js()->univ()->alert($e->getMessage())->execute();
            }
            throw $e;
        }

        return true;
    }
    public function lateSubmit()
    {
        if (@$_GET['submit'] != $this->name) {
            return;
        }

        if ($this->bail_out === null || $this->isSubmitted()) {
            $this->js()->univ()
                ->consoleError('Form '.$this->name.' submission is not handled.'.
                    ' See: http://agiletoolkit.org/doc/form/submit')
                ->execute();
        }
    }
    public function isSubmitted()
    {
        // This is alternative way for form submission. After form is initialized
        // you can call this method. It will hurry up all the steps, but you will
        // have ready-to-use form right away and can make submission handlers
        // easier
        if ($this->bail_out !== null) {
            return $this->bail_out;
        }

        $this->loadData();
        $result = $_POST && $this->submitted();
        $this->bail_out = $result;

        return $result;
    }
    public function onSubmit($callback)
    {
        $this->addHook('submit', $callback);
        $this->isSubmitted();
    }
    public function setLayout($template)
    {
        if (!$template instanceof AbstractView) {
            if (is_string($template)) {
                $template = $this->add('View', null, null, array($template));
            } else {
                $template = $this->add('View', null, null, $template);
            }
        }

        $this->layout = $template;

        return $this;
    }
    public function render()
    {
        // Assuming, that child fields already inserted their HTML code into 'form'/Content using 'form_line'
        // Assuming, that child buttons already inserted their HTML code into 'form'/form_buttons

        if ($this->js_widget) {
            $fn = str_replace('ui.', '', $this->js_widget);
            $this->js(true)->_load($this->js_widget)->$fn($this->js_widget_arguments);
        }

        return parent::render();
    }
    /**
     * OBSOLETE: use getElement().
     *
     * @param [type] $name [description]
     *
     * @return bool [description]
     */
    public function hasField($name)
    {
        if (!@$this->app->compat) {
            throw $this->exception('Use $form->hasElement instead', '_Obsolete');
        }

        return isset($this->elements[$name]) ? $this->elements[$name] : false;
    }
    public function isClicked($name)
    {
        if (is_object($name)) {
            $name = $name->short_name;
        }

        return $_POST['ajax_submit'] == $name || isset($_POST[$this->name.'_'.$name]);
    }
    /* external error management */
    public function setFieldError($field, $name)
    {
        if (!$this->app->compat) {
            throw $this->exception('4.3', '_Obsolete');
        }
        $this->errors[$field] = (isset($this->errors[$field]) ? $this->errors[$field] : '').$name;
    }

    public function validate($rule)
    {
        if (!$this->validator) {
            $this->validator = $this->add('Controller_Validator');
            $this->validator->on('post-validate');
        }
        $this->validator->is($rule);
    }

    /**
     * Compatibility. TODO remove in 4.4.
     *
     * @param [type] $class [description]
     */
    public function addClass($class)
    {
        if ($class == 'stacked' || $class == 'atk-form-stacked') {
            // there are no longer stacked forms, instead a separat etemplate must be used
            $this->template->loadTemplate('form/stacked');
            $this->getChunks();
            $this->template->trySet('_name', $this->getJSID());

            return $this;
        } else {
            return parent::addClass($class);
        }
    }
}
