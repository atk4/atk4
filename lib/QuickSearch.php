<?php
/**
 * Quicksearch represents one-field filter which works perfectly with a grid
 */
class QuickSearch extends Filter
{
    // icons
    public $submit_icon = 'ui-icon-search';
    public $cancel_icon = 'ui-icon-cancel';

    // field
    public $search_field;

    // buttonset
    public $bset_class = 'ButtonSet';
    public $bset_position = 'after'; // after|before
    protected $bset;

    // cancel button
    public $show_cancel = true; // show cancel button? (true|false)

    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();

        // template fixes
        $this->addClass('atk-form atk-form-stacked atk-form-compact atk-move-right');
        $this->template->trySet('fieldset', 'atk-row');
        $this->template->tryDel('button_row');

        $this->addClass('atk-col-3');

        // add field
        $this->search_field = $this->addField('Line', 'q', '')->setAttr('placeholder', 'Search')->setNoSave();

        // cancel button
        if ($this->show_cancel && $this->recall($this->search_field->short_name)) {
            $this->add('View', null, 'cancel_button')
                ->setClass('atk-cell')
                ->add('HtmlElement')
                ->setElement('A')
                ->setAttr('href', 'javascript:void(0)')
                ->setClass('atk-button')
                ->setHtml('<span class="icon-cancel atk-swatch-red"></span>')
                ->js('click', array(
                    $this->search_field->js()->val(null),
                    $this->js()->submit(),
                ));
        }

        // search button
        $this->add('HtmlElement', null, 'form_buttons')
            ->setElement('A')
            ->setAttr('href', 'javascript:void(0)')
            ->setClass('atk-button')
            ->setHtml('<span class="icon-search"></span>')
            ->js('click', $this->js()->submit());
    }

    /**
     * Set fields on which filtering will be done.
     *
     * @param string|array $fields
     *
     * @return QuickSearch $this
     */
    public function useFields($fields)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        $this->fields = $fields;

        return $this;
    }

    /**
     * Process received filtering parameters after init phase.
     */
    public function postInit()
    {
        parent::postInit();
        if (!($v = trim($this->get('q')))) {
            return;
        }

        if ($this->view->model->hasMethod('addConditionLike')) {
            return $this->view->model->addConditionLike($v, $this->fields);
        }
        if ($this->view->model) {
            $q = $this->view->model->_dsql();
        } else {
            $q = $this->view->dq;
        }
        $or = $q->orExpr();
        foreach ($this->fields as $field) {
            $or->where($field, 'like', '%'.$v.'%');
        }
        $q->having($or);
    }
    public function defaultTemplate()
    {
        return array('form/quicksearch');
    }
}
