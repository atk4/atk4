<?php
/**
 * Quicksearch represents one-field filter which works perfectly with a grid
 */
class QuickSearch extends Filter
{
    /** @var string Submit button icon */
    public $submit_icon = 'ui-icon-search';

    /** @var string Cancel button icon */
    public $cancel_icon = 'ui-icon-cancel';

    /** @var Form_Field */
    public $search_field;

    /** @var array */
    public $fields;

    /** @var string Button set class name */
    public $bset_class = 'ButtonSet';

    /** @var string Button set positioning */
    public $bset_position = 'after'; // after|before

    /** @var ButtonSet object iteself */
    protected $bset;

    /** @var bool Shoud we add Cancel button or not */
    public $show_cancel = true;

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

        /** @type HtmlElement $b Search button */
        $b = $this->add('HtmlElement', null, 'form_buttons');
        $b->setElement('A')
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
     *
     * @return Model|void
     */
    public function postInit()
    {
        parent::postInit();

        if (!($v = trim($this->get('q')))) {
            return;
        }

        $m = $this->view->model;

        // if model has method addConditionLike
        if ($m->hasMethod('addConditionLike')) {
            return $m->addConditionLike($v, $this->fields);
        }

        // if it is Agile Data model
        if ($m instanceof \atk4\data\Model) {

            if (!$m->hasMethod('expr')) {
                return $m;
            }

            $expr = [];
            foreach ($this->fields as $k=>$field) {
                // get rid of never_persist fields
                $f = $m->hasElement($field);
                if (!$f || $f->never_persist) {
                    unset($this->fields[$k]);
                    continue;
                }

                $expr[] = 'lower({' . $field . '}) like lower([])';
            }
            $expr = '('.implode(' or ', $expr).')';
            $expr = $m->expr($expr, array_fill(0, count($this->fields), '%'.$v.'%'));

            return $m->addCondition($expr); // @todo should use having instead
        }

        // if it is ATK 4.3 model or any other data source
        if ($m instanceof SQL_Model) {
            $q = $m->_dsql();
        } else {
            $q = $this->view->dq;
        }

        $or = $q->orExpr();
        foreach ($this->fields as $field) {
            $or->where($field, 'like', '%'.$v.'%');
        }
        $q->having($or);
    }

    /**
     * Default template
     *
     * @return array|string
     */
    public function defaultTemplate()
    {
        return array('form/quicksearch');
    }
}
