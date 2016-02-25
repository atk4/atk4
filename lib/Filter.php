<?php
/**
 * Undocumented.
 */
class Filter extends Form
{
    public $view;

    public function init()
    {
        parent::init();

        // set default values on non-yet initialized fields
        $this->app->addHook('post-init', array($this, 'postInit'));
    }

    /**
     * Set view on which conditions will be applied.
     *
     * @param object $view
     *
     * @return Filter $this
     */
    public function useWith($view)
    {
        // Apply our condition on the view
        $this->view = $view;

        return $this;
    }

    /**
     * Remembers values and uses them as condition.
     */
    public function postInit()
    {
        foreach ($this->elements as $x => $field) {
            if ($field instanceof Form_Field) {
                $field->set($this->recall($x));

                if ($field->no_save || !$field->get()) {
                    continue;
                }

                // also apply the condition
                if ($this->view->model && $this->view->model->hasElement($x)) {

                    // take advantage of field normalization
                    $this->view->model->addCondition($x, $field->get());
                }
            }
        }

        // call applyFilter hook if such exist, pass model of associated view as parameter
        $this->hook('applyFilter', array($this->view->model));
    }

    /**
     * Memorize filtering parameters.
     */
    public function memorizeAll()
    {
        // memorize() method doesn't memorize anything if value is null
        foreach ($this->get() as $field => $value) {
            if ((isset($this->reset) && $this->isClicked($this->reset)) || is_null($value)) {
                $this->forget($field);
            } else {
                $this->memorize($field, $value);
            }
        }
    }

    /**
     * Add Save and Reset buttons.
     *
     * @return Filter $this
     */
    public function addButtons()
    {
        $this->save = $this->addSubmit('Save');
        $this->reset = $this->addSubmit('Reset');

        return $this;
    }

    /**
     * On form submit memorize or forget filtering parameters.
     */
    public function submitted()
    {
        if (parent::submitted()) {
            if (isset($this->reset) && $this->isClicked($this->reset)) {
                $this->forget();
                $this->js(null, $this->view->js()->reload())->reload()->execute();
            } else {
                $this->memorizeAll();
            }
            $this->view->js()->reload()->execute();
        }
    }
}
