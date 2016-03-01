<?php
/**
 * CompleteLister is very similar to regular Lister, but will use
 * <?rows?><?row?>blah<?/?><?/?> structure inside template.
 * Also adds support for calculating totals.
 *
 * Use:
 *  $list = $this->add('CompleteLister');
 *  $list->setModel('User');
 *  $list->addTotals();
 *
 * Template (view/users.html):
 *  <h3>Users</h3>
 *  <?rows?>
 *   <?row?>
 *    <h4><?$name?></h4>
 *    <p><?$desc?></p>
 *   <?/row?>
 *   <h4>Joe Blogs</h4>
 *   <p>Sample template. Will be ignored</p>
 *  <?/rows?>
 *  <?totals?>
 *    <?$row_count?> user<?$plural_s?>.
 *  <?/?>
 */
class CompleteLister extends Lister
{
    /**
     * @var string Template tag for item
     */
    protected $item_tag = 'row';

    /**
     * @var string Template tag for container
     */
    protected $container_tag = 'rows';

    /**
     * @var Template Item ($item_tag) template
     */
    public $row_t;

    /**
     * @var array Separator
     */
    public $sep_html;

    /**
     * Will contain accumulated totals for all fields
     *
     * @var array|boolean
     */
    public $totals = false;

    /**
     * After rendering will contain data row count
     *
     * @var int
     */
    public $total_rows;

    /**
     * Will be initialized to "totals" template when _addTotals() is called
     *
     * @var Template
     */
    public $totals_t;

    /**
     * @var string Used CSS class for odd rows
     */
    public $odd_css_class = 'odd';

    /**
     * @var string Used CSS class for even rows
     */
    public $even_css_class = 'even';

    /**
     * @var string Is current odd or even row?
     */
    protected $odd_even = null;

    /**
     * Type of totals calculation:
     * null      - no totals calculation
     * onRender  - calculate totals only for rendered rows on rendering phase
     * onRequest - grand totals, works for SQL_Many models only, creates 1
     *             additional DB request.
     * Should be changed using addTotals and addGrandTotals methods only
     *
     * @var string
     */
    protected $totals_type = null;

    // {{{ Initialization

    /**
     * Initialization.
     *
     * @retun void
     */
    public function init()
    {
        parent::init();
        if (!$this->template->hasTag($this->item_tag)) {
            if (@$this->app->compat_42 and $this instanceof Menu_Basic) {
                // look for MenuItem

                $default = $this->item_tag;

                $this->item_tag = 'MenuItem';
                $this->container_tag = 'Item';
                if (!$this->template->hasTag($this->item_tag)) {
                    throw $this->template->exception('Template must have "'.$default.'" tag')
                        ->addMoreInfo('compat', 'Also tried for compatibility reason "'.$this->item_tag.'" tag');
                }
            } else {
                throw $this->template->exception('Template must have "'.$this->item_tag.'" tag');
            }
        }

        $this->row_t = $this->template->cloneRegion($this->item_tag);

        if ($this->template->hasTag('sep')) {
            $this->sep_html = $this->template->get('sep');
        }
    }

    /**
     * Default template.
     *
     * @return array
     */
    public function defaultTemplate()
    {
        return array('view/completelister');
    }

    /**
     * Enable totals calculation for specified array of fields.
     *
     * If particular fields not specified, then all field totals are calculated.
     * If you only need to count records, then pass null and no fields will be calculated.
     *
     * Be aware that if you use Paginator, then only records of current page
     * will be calculated. If you need grand totals for all records, then use
     * method addGrandTotals() instead.
     *
     * @param array $fields optional array of fieldnames
     *
     * @return $this
     */
    public function addTotals($fields = UNDEFINED)
    {
        return $this->_addTotals($fields, 'onRender');
    }

    /**
     * Enable totals calculation for specified array of fields.
     *
     * If particular fields not specified, then all field totals are calculated.
     * If you only need to count records, then pass null and no fields will be calculated.
     *
     * Be aware that this method works ONLY for SQL models set as data source
     * because this calculates grand totals using DSQL.
     *
     * @param array $fields optional array of fieldnames
     *
     * @return $this
     */
    public function addGrandTotals($fields = UNDEFINED)
    {
        if (!$this->getIterator() instanceof SQL_Model) {
            throw $this->exception('Grand Totals can be used only with SQL_Model data source');
        }

        return $this->_addTotals($fields, 'onRequest');
    }

    /**
     * Disable totals calculation.
     *
     * @return $this
     */
    public function removeTotals()
    {
        return $this->addTotals();
    }

    /**
     * Private method to enable / disable totals calculation for specified array
     * of fields.
     *
     * If particular fields not specified, then all field totals are calculated.
     * If you only need to count records, then pass null and no fields will be calculated.
     *
     * @param array  $fields optional array of fieldnames
     * @param string $type   type of totals calculation (null|onRender|onRequest)
     *
     * @return $this
     */
    protected function _addTotals($fields = UNDEFINED, $type = null)
    {
        // set type
        $this->totals_type = $type;

        // clone template chunk for totals
        if ($this->template->hasTag('totals')) {
            $this->totals_t = $this->template->cloneRegion('totals');
        }

        // if no fields defined then get available fields from model
        $iter = $this->getIterator();
        if ($fields === UNDEFINED && $iter->hasMethod('getActualFields')) {
            $fields = $iter->getActualFields();
        }

        // reset field totals
        if ($this->totals === false) {
            $this->totals = array();
        }
        if (is_array($fields)) {
            foreach ($fields as $field) {
                $this->totals[$field] = 0;
            }
        }

        return $this;
    }

    // }}}

    // {{{ Rendering

    /**
     * Render lister.
     */
    public function render()
    {
        $this->renderRows();
        $this->output($this->template->render());
    }

    /**
     * Render lister rows.
     */
    public function renderRows()
    {
        $this->odd_even = null;
        $this->total_rows = 0;
        $this->template->del($this->container_tag);

        // render data rows
        $iter = $this->getIterator();
        foreach ($iter as $this->current_id => $this->current_row) {
            if ($this->current_row instanceof Model) {
                $this->current_row = (array) $this->current_row->get();
            } elseif (!is_array($this->current_row) && !($this->current_row instanceof ArrayAccess)) {
                // Looks like we won't be abel to access current_row as array, so we will
                // copy it's value inside $this->current instead and produce an empty array
                // to be filled out by a custom iterators
                $this->current = $this->current_row;
                $this->current_row = get_object_vars($this->current);
            }

            $this->current_row_html = array();

            if (!empty($this->sep_html) && $this->total_rows) {
                $this->renderSeparator();
            }

            // calculate rows so far
            ++$this->total_rows;

            // if onRender totals enabled, then update totals
            if ($this->totals_type == 'onRender') {
                $this->updateTotals();
            }

            // render data row
            $this->renderDataRow();
        }

        // calculate grand totals if needed
        if ($this->totals_type == 'onRequest') {
            $this->updateGrandTotals();
        }

        // set total row count
        $this->totals['row_count'] = $this->total_rows;

        // render totals row
        $this->renderTotalsRow();
    }

    /**
     * Render data row.
     */
    public function renderDataRow()
    {
        $this->formatRow();

        $this->template->appendHTML(
            $this->container_tag,
            $this->rowRender($this->row_t)
        );
    }

    public function renderSeparator()
    {
        $this->template->appendHTML(
            $this->container_tag,
            $this->sep_html
        );
    }

    /**
     * Render Totals row.
     */
    public function renderTotalsRow()
    {
        $this->current_row = $this->current_row_html = array();
        if ($this->totals !== false && is_array($this->totals) && $this->totals_t) {
            $this->current_row = $this->totals;

            $this->formatTotalsRow();

            $this->template->appendHTML(
                $this->container_tag,
                $this->rowRender($this->totals_t)
            );
        } else {
            $this->template->tryDel('totals');
        }
    }

    // }}}

    // {{{ Formatting

    /**
     * Format lister row.
     */
    public function formatRow()
    {
        parent::formatRow();

        if (is_array($this->current_row) || $this->current_row instanceof ArrayAccess) {
            $this->odd_even =
                $this->odd_even == $this->odd_css_class
                    ? $this->even_css_class
                    : $this->odd_css_class;
            $this->current_row['odd_even'] = $this->odd_even;
        }
    }

    /**
     * Additional formatting for Totals row.
     *
     * @todo This plural_s method suits only English locale !!!
     */
    public function formatTotalsRow()
    {
        $this->formatRow();
        $this->hook('formatTotalsRow');

        // deal with plural - not localizable!
        if ($this->current_row['row_count'] == 0) {
            $this->current_row['row_count'] = 'no';
            $this->current_row['plural_s'] = 's';
        } else {
            $this->current_row['plural_s'] =
                $this->current_row['row_count'] > 1
                    ? 's'
                    : '';
        }
    }

    // }}}

    // {{{ Totals

    /**
     * Add current rendered row values to totals.
     *
     * Called before each formatRow() call.
     */
    public function updateTotals()
    {
        if (is_array($this->totals)) {
            foreach ($this->totals as $key => $val) {
                if (is_object($this->current_row[$key])) {
                    continue;
                }
                $this->totals[$key] = $val + $this->current_row[$key];
            }
        }
    }

    /**
     * Calculate grand totals of all rows.
     *
     * Called one time on rendering phase - before renderRows() call.
     */
    public function updateGrandTotals()
    {
        // get model
        $m = $this->getIterator();

        // create DSQL query for sum and count request
        $fields = array_keys($this->totals);

        // select as sub-query
        $sub_q = $m->dsql()->del('limit')->del('order');

        $q = $this->app->db->dsql();//->debug();
        $q->table($sub_q, 'grandTotals'); // alias is mandatory if you pass table as DSQL
        foreach ($fields as $field) {
            $q->field($q->sum($field), $field);
        }
        $q->field($q->count(), 'total_cnt');

        // execute DSQL
        $data = $q->getHash();

        // parse results
        $this->total_rows = $data['total_cnt'];
        unset($data['total_cnt']);
        $this->totals = $data;
    }

    // }}}
}
