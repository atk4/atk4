<?php
/**
 * Displays submit button.
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   See file COPYING
 *
 * @version     $Id$
 */
class Form_Submit extends Button
{
    public $no_save = null;

    public $form;

    public function init()
    {
        parent::init();
        $this->template->trySet('type', 'submit');
        if ($this->form->js_widget) {
            $this->js('click', array(
                        $this->form->js()->find('input[name=ajax_submit]')->val($this->short_name),
                        $this->form->js()->submit(),
                        ));
        }
    }
    public function setNoSave()
    {
        // Field value will not be saved into defined source (such as database)
        $this->no_save = true;

        return $this;
    }
    public function disable()
    {
        $this->js(true)->attr('disabled', 'disabled');

        return $this;
    }
}
