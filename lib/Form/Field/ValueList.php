<?php // vim:ts=4:sw=4:et:fdm=marker
/**
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
/*
 * This is abstract class. Use this as a base for all the controls
 * which operate with predefined values such as dropdowns, checklists
 * etc
 */
abstract class Form_Field_ValueList extends Form_Field
{
    // array of available values
    public $value_list = array();

    // default empty text message
    public $default_empty_text = 'Please, select ...';

    // current empty text message and ID
    public $empty_text = null;
    protected $empty_value = ''; // don't change this value

    // value separator, for internal use
    protected $separator = ',';


    function setForm($form){
        parent::setForm($form);
        $this->form->addHook('validate',[$this,'validateValidItem']);
    }

    /**
     * Sets model of form field
     *
     * @param Model $m
     *
     * @return Model
     */
    function setModel($m)
    {
        $ret = parent::setModel($m);
        $this->setValueList(array());
        return $ret;
    }

    /**
     * Set value list of form field
     *
     * @param array $list
     *
     * @return $this
     */
    function setValueList($list)
    {
        $this->value_list = $list;
        return $this;
    }

    /**
     * Sets default text which is displayed on a null-value option.
     *
     * Set to "Select.." or "Pick one.."
     *
     * @param string $text Pass UNDEFINED to use default text, empty string - disable
     *
     * @return $this
     */
    function setEmptyText($text = UNDEFINED)
    {
        $this->empty_text = $text === null ? $this->default_empty_text : $text;
        return $this;
    }

    /**
     * Validate POSTed field value
     *
     * @return boolean
     */
    function validateValidItem()
    {
        if (!$this->value) return;

        // load allowed values in values_list
        // @todo Imants: Actually we don't need to load all values from Model in
        //       array, just to check couple of posted values.
        //       Probably we should do SELECT * FROM t WHERE id IN ($values) or
        //       something like that to limit array size and time spent on
        //       parsing all DB records in Model.
        $this->getValueList();

        $values = explode($this->separator, $this->value);
        foreach ($values as $v) {
            if (!isset($this->value_list[$v])) {
                $this->displayFieldError("Value $v is not one of the offered values");
                return parent::validate();
            }
        }
    }

    /**
     * Return value list
     *
     * @return array
     */
    function getValueList()
    {
        // add model data rows in value list
        if ($this->model) {
            $id = $this->model->id_field;
            $title = $this->model->getTitleField();

            $this->value_list = array();
            foreach ($this->model as $row) {
                $this->value_list[(string)$row[$id]] = $row[$title];
            }
        }

        // prepend empty text message at the begining of value list if needed
        if ($this->empty_text && !isset($this->value_list[$this->empty_value])) {
            $this->value_list = array($this->empty_value => $this->empty_text) + $this->value_list;
        }

        return $this->value_list;
    }

    /**
     * Normalize POSTed data
     *
     * @return void
     */
    function normalize()
    {
        $data = $this->get();
        if (is_array($data)) {
            $data = join($this->separator, $data);
        }
        $data = trim($data, $this->separator);

        if (get_magic_quotes_gpc()) {
            $this->set(stripslashes($data));
        } else {
            $this->set($data);
        }

        return parent::normalize();
    }
}
