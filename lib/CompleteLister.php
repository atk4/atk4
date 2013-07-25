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
 *    <?row?>
 *      <h4><?$name?></h4>
 *      <p><?$desc?></p>
 *    <?/row?>
 *    <h4>Joe Blogs</h4>
 *    <p>Sample template. Will be ignored</p>
 *    <?totals?>
 *      <?$row_count?> user<?$plural_s?>.
 *    <?/?>
 *    <?grand_totals?>
 *      <?$row_count?> user<?$plural_s?>.
 *    <?/?>
 *  <?/rows?>
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

    /** Will contain accumulated totals for fields */
    public $totals = false;
    public $grand_totals = false;

    /** After rendering will contain data row count */
    public $total_rows = false;
    public $grand_total_rows = false;

    /** Will be initialized to "totals" template when addTotals() is called */
    public $totals_t = false;

    /** Will be initialized to "grand_totals" template when addGrandTotals() is called */
    public $grand_totals_t = false;

    /** @private Is current odd or even row? */
    protected $odd_even = null;


    /**
     * Initialization
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
     * Enable totals calculation for specified array of fields.
     * If particular fields not specified, then all field totals are calculated.
     *
     * Be aware that if you use Paginator, then only records of current page
     * will be calculated. If you need grand totals for all records, then use
     * method addGrandTotals() instead.
     *
     * @param array $fields Optional array of fieldnames
     *
     * @return $this
     */
    function addTotals($fields = null)
    {
        if ($this->template->is_set('totals')) {
            $this->totals_t = $this->template->cloneRegion('totals');
        }

        if ($fields) {
            foreach ($fields as $field) {
                $this->totals[$field] = 0;
            }
        } elseif ($this->totals === false) {
            $this->totals = array();
        }
        
        return $this;
    }

    /**
     * Enable totals calculation for specified array of fields.
     * If particular fields not specified, then all field totals are calculated.
     *
     * Be aware that this method works ONLY for SQL models set as data source
     * because this calculates grand totals using DSQL.
     *
     * @param array $fields Optional array of fieldnames
     *
     * @return $this
     */
    function addGrandTotals($fields = null)
    {
        if (!$this->getIterator() instanceof SQL_Model) {
            throw $this->exception("Grand Totals can be used only with SQL_Model data source");
        }

        if ($this->template->is_set('grand_totals')) {
            $this->grand_totals_t = $this->template->cloneRegion('grand_totals');
        }

        if (!$fields && $this->grand_totals === false) {
            $fields = $this->getIterator()->getActualFields();
        }

        if ($fields) {
            foreach ($fields as $field) {
                $this->grand_totals[$field] = 0;
            }
        }

        return $this;
    }

     /**
     * Format lister row
     *
     * @return void
     */
    function formatRow()
    {
        parent::formatRow();
        $this->odd_even = $this->odd_even == 'odd' ? 'even' : 'odd';
        $this->current_row['odd_even'] = $this->odd_even;
    }

   /**
     * Additional formatting for Totals row.
     *
     * @return void
     */
    function formatTotalsRow()
    {
        $this->formatRow();
        $this->hook('formatTotalsRow');

        $this->current_row['plural_s'] = $this->current_row['row_count']>1 ? 's' : '';
        if ($this->current_row['row_count'] == 0) {
            $this->current_row['row_count'] = 'no';
            $this->current_row['plural_s'] = 's';
        }
    }

    /**
     * Additional formatting for Grand Totals row.
     * 
     * Be aware, that this method can only be used on SQL_Model data source
     *
     * @return void
     */
    function formatGrandTotalsRow()
    {
        $this->formatRow();
        $this->hook('formatGrandTotalsRow');

        $this->current_row['plural_s'] = $this->current_row['row_count']>1 ? 's' : '';
        if ($this->current_row['row_count'] == 0) {
            $this->current_row['row_count'] = 'no';
            $this->current_row['plural_s'] = 's';
        }
    }

    /**
     * Update totals of rows.
     * Called before each formatRow() call.
     *
     * @return void
     */
    function updateTotals()
    {
        foreach ($this->totals as $key=>$val) {
            $this->totals[$key] = $val + $this->current_row[$key];
        }
    }

    /**
     * Update grand totals of rows.
     * Called one time on rendering phase - before renderRows() call.
     * 
     * @return void
     */
    function updateGrandTotals()
    {
        // get model
        $m = $this->getIterator();

        // create DSQL query for sum and count request
        $fields = array_keys($this->grand_totals);
        $q = $m->sum($fields);
        $q->field($q->count(), 'grand_total_cnt');

        // execute DSQL
        $data = $q->getHash();

        // parse results
        $this->grand_total_rows = $data['grand_total_cnt'];
        unset($data['grand_total_cnt']);
        $this->grand_totals = $data;
    }

    /**
     * Render lister
     *
     * @return void
     */
    function render()
    {
        // render rows and output
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
        // initialize
        $this->odd_even = '';
        $this->template->del($this->container_tag);
        $this->total_rows = 0;

        // calculate grand totals if needed
        if ($this->grand_totals !== false) {
            $this->updateGrandTotals();
        }

        // render data rows
        foreach ($this->getIterator() as $this->current_id=>$this->current_row) {
            
            // if totals enabled, but specific fields are not specified with
            // addTotals, then calculate totals for all available fields
            if ($this->totals === array()) {
                foreach ($this->current_row as $k=>$v) {
                    $this->totals[$k] = 0;
                }
            }
            
            // calculate rows so far
            $this->total_rows++;

            // if totals enabled, then execute
            if ($this->totals !== false) {
                $this->updateTotals();
            }
            
            // render data row
            $this->renderDataRow();
        }
        
        // render totals row
        $this->renderTotalsRow();

        // render grand totals row
        $this->renderGrandTotalsRow();
    }

    /**
     * Render data row
     *
     * @return void
     */
    function renderDataRow()
    {
        $this->formatRow();
        $this->template->appendHTML($this->container_tag, $this->rowRender($this->row_t));
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
            $this->totals['row_count'] = $this->total_rows;
            $this->current_row = $this->totals;

            $this->formatTotalsRow();
            $this->template->appendHTML($this->container_tag, $this->rowRender($this->totals_t));
        }
    }
    
    /**
     * Render Grand Totals row
     *
     * @return void
     */
    function renderGrandTotalsRow()
    {
        $this->current_row = $this->current_row_html = array();
        if ($this->grand_totals !== false && $this->grand_totals_t) {
            $this->grand_totals['row_count'] = $this->grand_total_rows;
            $this->current_row = $this->grand_totals;

            $this->formatGrandTotalsRow();
            $this->template->appendHTML($this->container_tag, $this->rowRender($this->grand_totals_t));
        }
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
}
