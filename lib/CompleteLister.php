<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * CompleteLister is very similar to regular Lister, but will use
 * <?rows?><?row?>blah<?/?><?/?> structure inside template.
 * Also adds support for calculating totals.
 * 
 * @link http://agiletoolkit.org/doc/lister
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
 *
 * @license See http://agiletoolkit.org/about/license
 *
 *//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class CompleteLister extends Lister
{
    /** Used template tags */
    protected $item_tag = 'row';
    protected $container_tag = 'rows';

    /** Item ($item_tag) template */
    public $row_t;

    /** Will contain accumulated totals for all fields */
    public $totals = false;

    /** After rendering will contain data row count */
    public $total_rows = false;

    /** Will be initialized to "totals" template when _addTotals() is called */
    public $totals_t = false;

    /** Used CSS classes for odd and even rows */
    public $odd_css_class = 'odd';
    public $even_css_class = 'even';

    /**
     * Type of totals calculation:
     * null      - no totals calculation
     * onRender  - calculate totals only for rendered rows on rendering phase
     * onRequest - grand totals, works for SQL_Many models only, creates 1
     *             additional DB request
     * 
     * @private Should be changed using addTotals and addGrandTotals methods only
     */
    protected $totals_type = null;

    /** @private Is current odd or even row? */
    protected $odd_even = null;

    // {{{ Initialization

    /**
     * Initialization
     *
     * @retun void
     */
    function init()
    {
        parent::init();
        if (!$this->template->is_set($this->item_tag)) {
            throw $this->exception('Template must have "'.$this->item_tag.'" tag');
        }

        $this->row_t = $this->template->cloneRegion($this->item_tag);
    }

    /**
     * Default template
     *
     * @return array
     */
    function defaultTemplate()
    {
        return array('view/completelister');
    }

    /**
     * Enable totals calculation for specified array of fields
     *
     * If particular fields not specified, then all field totals are calculated.
     *
     * Be aware that if you use Paginator, then only records of current page
     * will be calculated. If you need grand totals for all records, then use
     * method addGrandTotals() instead.
     *
     * @param array $fields optional array of fieldnames
     *
     * @return $this
     */
    function addTotals($fields = null)
    {
        return $this->_addTotals($fields, 'onRender');
    }

    /**
     * Enable totals calculation for specified array of fields
     *
     * If particular fields not specified, then all field totals are calculated.
     *
     * Be aware that this method works ONLY for SQL models set as data source
     * because this calculates grand totals using DSQL.
     *
     * @param array $fields optional array of fieldnames
     *
     * @return $this
     */
    function addGrandTotals($fields = null)
    {
        if (!$this->getIterator() instanceof SQL_Model) {
            throw $this->exception("Grand Totals can be used only with SQL_Model data source");
        }

        return $this->_addTotals($fields, 'onRequest');
    }

    /**
     * Disable totals calculation
     *
     * @return $this
     */
    function removeTotals()
    {
        return $this->addTotals();
    }

    /**
     * Private method to enable / disable totals calculation for specified array
     * of fields
     *
     * If particular fields not specified, then all field totals are calculated.
     *
     * @param array $fields optional array of fieldnames
     * @param string $type type of totals calculation (null|onRender|onRequest)
     *
     * @return $this
     */
    protected function _addTotals($fields = null, $type = null)
    {
        // set type
        $this->totals_type = $type;

        // clone template chunk for totals
        if ($this->template->is_set('totals')) {
            $this->totals_t = $this->template->cloneRegion('totals');
        }

        // if no fields defined then get available fields from model
        $iter = $this->getIterator();
        if (!$fields && $iter->hasMethod('getActualFields')) {
            $fields = $iter->getActualFields();
        }

        // reset field totals
        if ($this->totals === false) {
            $this->totals = array();
        }
        if ($fields) {
            foreach ($fields as $field) {
                $this->totals[$field] = 0;
            }
        }

        return $this;
    }

    // }}}

    // {{{ Rendering

    /**
     * Render lister
     *
     * @return void
     */
    function render()
    {
        $this->renderRows();
        $this->output($this->template->render());
    }

    /**
     * Render lister rows
     *
     * @return void
     */
    function renderRows()
    {
        $this->odd_even = null;
        $this->total_rows = 0;
        $this->template->del($this->container_tag);

        // render data rows
        $iter = $this->getIterator();
        foreach ($iter as $this->current_id=>$this->current_row) {

            // if totals enabled, but specific fields are not specified with
            // addTotals, then calculate totals for all available fields
            if ($this->totals === array()) {
                foreach ($this->current_row as $k=>$v) {
                    $this->totals[$k] = 0;
                }
            }

            // calculate rows so far
            $this->total_rows++;

            // if onRender totals enabled, then execute
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
     * Render data row
     *
     * @return void
     */
    function renderDataRow()
    {
        $this->formatRow();

        $this->template->appendHTML(
            $this->container_tag,
            $this->rowRender($this->row_t)
        );
    }

    /**
     * Render Totals row
     *
     * @return void
     */
    function renderTotalsRow()
    {
        $this->current_row = $this->current_row_html = array();
        if ($this->totals !== false && $this->totals_t) {
            $this->current_row = $this->totals;

            $this->formatTotalsRow();

            $this->template->appendHTML(
                $this->container_tag,
                $this->rowRender($this->totals_t)
            );
        }
    }

    // }}}

    // {{{ Formatting

    /**
     * Format lister row
     *
     * @return void
     */
    function formatRow()
    {
        parent::formatRow();

        $this->odd_even =
            $this->odd_even == $this->odd_css_class
                ? $this->even_css_class
                : $this->odd_css_class;
        $this->current_row['odd_even'] = $this->odd_even;
    }

    /**
     * Additional formatting for Totals row
     *
     * @todo This plural_s method suits only English locale !!!
     *
     * @return void
     */
    function formatTotalsRow()
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
     * Add current rendered row values to totals
     *
     * Called before each formatRow() call.
     *
     * @return void
     */
    function updateTotals()
    {
        foreach ($this->totals as $key => $val) {
            if(is_object($this->current_row[$key]))continue;
            $this->totals[$key] = $val + $this->current_row[$key];
        }
    }

    /**
     * Calculate grand totals of all rows
     *
     * Called one time on rendering phase - before renderRows() call.
     * 
     * @return void
     */
    function updateGrandTotals()
    {
        // get model
        $m = $this->getIterator();

        // create DSQL query for sum and count request
        $fields = array_keys($this->totals);
        $q = $m->sum($fields)->del('limit');
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
