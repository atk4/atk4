<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * This is a Basic Grid implementation, which produces fully
 * functional HTML grid capable of filtering, sorting, paginating
 * and using multiple column formatters.
 * 
 * @link http://agiletoolkit.org/doc/grid
 *
 * Use:
 *  $grid=$this->add('Grid');
 *  $grid->setModel('User');
 *
 * @license See http://agiletoolkit.org/about/license
 *//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class Grid_Advanced extends Grid_Basic
{
    /** Pointer to last added grid column */
    public $last_column;

    /** Sorting */
    public $sortby = '0';
    public $sortby_db = null;
    
    /** For totals */
    private $totals_title_field = null;
    private $totals_title = "";
    
    /**
     * Grid buttons
     *
     * @see addButton()
     */
    public $buttonset = null;

    /** Static data source? */
    public $data = null;

    /**
     * Paginator object
     *
     * @see addPaginator()
     */
    protected $paginator = null;

    /**
     * $tdparam property is an array with cell parameters specified in td tag.
     * This should be a hash: 'param_name'=>'param_value'
     * Following parameters treated and processed in a special way:
     * 1) 'style': nested array, style parameter.
     *             items of this nested array converted to a form of style:
     *             style="param_name: param_value; param_name: param_value"
     *
     * All the rest are not checked and simply converted to a form of
     * param_name="param_value"
     *
     * This is a tree-like array with the following structure:
     * array(
     *      [level1] => dataset_row = array(
     *          [level2] => field = array(
     *              [level3] => tdparam_elements = array(
     *                  param_name => param_value
     *              )
     *          )
     *      )
     * )
     */
    protected $tdparam = array();

    // JavaScript widget
    public $js_widget = 'ui.atk4_grid';
    public $js_widget_arguments = array();

    /** @private used in button formatters to share URL between methods */
    public $_url = array();


    /**
     * Initialization
     *
     * @return void
     */
    function init()
    {
        parent::init();

        // sorting support
        $this->sortby =
            isset($_GET[$this->name.'_sort'])
                ? $this->memorize('sortby', $_GET[$this->name.'_sort'])
                : $this->recall('sortby', '0');
    }

    // {{{ Columns

    /**
     * Set column as "last column"
     *
     * @param string $name
     *
     * @return $this
     */
    function getColumn($name)
    {
        $this->last_column = $name;
        return $this;
    }

    /**
     * Check if we have such column
     *
     * @param string $name
     *
     * @return boolean
     */
    function hasColumn($name)
    {
        return isset($this->columns[$name]);
    }

    /**
     * Remove column from grid
     *
     * @param string $name
     *
     * @return $this
     */
    function removeColumn($name)
    {
        unset($this->columns[$name]);
        if ($this->last_column == $name) {
            $this->last_column = null;
        }

        return $this;
    }

    // }}}

    // {{{ Misc features

    /**
     * Adds button
     *
     * @param string $label label of button
     * @param string $class optional name of button class
     *
     * @return Button
     */
    function addButton($label, $class = 'Button')
    {
        if (!$this->buttonset) {
            $this->buttonset = $this->add('ButtonSet', null, 'grid_buttons');
        }
        return $this->buttonset
            ->add($class, 'gbtn'.count($this->elements))
            ->setLabel($label);
    }

    /**
     * Adds QuickSearch
     * 
     * @param array $fields array of fieldnames used in quick search
     * @param string $class optional quick search object class
     * @param array $options optional options array
     *
     * @return QuickSearch
     */
    function addQuickSearch($fields,$class='QuickSearch',$options=null){
        return $this->add($class,$options,'quick_search')
            ->useWith($this)
            ->useFields($fields);
    }

    /**
     * Adds paginator to the grid
     *
     * @param int $ipp row count per page
     * @param array $options
     *
     * @return $this
     */
    function addPaginator($ipp = 25, $options = null)
    {
        // adding ajax paginator
        if ($this->paginator) {
            return $this->paginator;
        }
        $this->paginator = $this->add('Paginator', $options);
        $this->paginator->ipp($ipp);
        return $this;
    }

    /**
     * Adds column ordering object
     *
     * With it you can reorder your columns
     *
     * @return Order
     */
    function addOrder()
    {
        return $this->add('Order', 'columns')
            ->useArray($this->columns)
            ;
    }

    /**
     * Adds column with checkboxes on the basis of Model definition
     * 
     * @param mixed $field should be Form_Field object or jQuery selector of
     *                     1 field. When passing it as jQuery selector don't
     *                     forget to use hash sign like "#myfield"
     */
    function addSelectable($field)
    {
        $this->js_widget = null;
        $this->js(true)
            ->_load('ui.atk4_checkboxes')
            ->atk4_checkboxes(array('dst_field' => $field));
        $this->addColumn('checkbox', 'selected');

        $this->addOrder()
            ->useArray($this->columns)
            ->move('selected', 'first')
            ->now();
    }

    // }}}
    
    // {{{ Sorting

    /**
     * Returns data source iterator
     *
     * @return mixed
     */
    function getIterator()
    {
        $iter = parent::getIterator();

        // sorting support
        if ($this->sortby) {
            $desc = ($this->sortby_db[0] == '-');
            $order = ltrim($this->sortby_db, '-');
            $this->applySorting($iter, $order, $desc);
        }
        
        return $iter;
    }

    /**
     * Make sortable
     *
     * @param string $db_sort
     *
     * @return $this
     */
    function makeSortable($db_sort = null)
    {
        // reverse sorting
        $reverse = false;
        if ($db_sort[0] == '-') {
            $reverse = true;
            $db_sort = substr($db_sort, 1);
        }

        // used db field
        if (!$db_sort) {
            $db_sort = $this->last_column;
        }

        switch ((string)$this->sortby) {
            
            // we are already sorting by this column
            case $this->last_column:
                $info = array('1', $reverse ? 0 : ("-".$this->last_column));
                $this->sortby_db = $db_sort;
                break;

            // We are sorted reverse by this column
            case "-" . $this->last_column:
                $info = array('2', $reverse ? $this->last_column : '0');
                $this->sortby_db = "-" . $db_sort;
                break;
            
            // we are not sorted by this column
            default:
                $info = array('0', $reverse ? ("-" . $this->last_column) : $this->last_column);
        }

        $this->columns[$this->last_column]['sortable'] = $info;

        return $this;
    }

    /**
     * Compare two strings and return:
     *      < 0 if str1 is less than str2;
     *      > 0 if str1 is greater than str2,
     *      and 0 if they are equal.
     *
     * Note that this comparison is case sensitive
     * 
     * @param string $str1
     * @param string $str2
     *
     * @return int
     */
    function staticSortCompare($str1, $str2)
    {
        if ($this->sortby[0] == '-') {
            return strcmp(
                $str2[substr($this->sortby, 1)],
                $str1[substr($this->sortby, 1)]
            );
        }
        return strcmp(
            $str1[$this->sortby],
            $str2[$this->sortby]
        );
    }

    /**
     * Apply sorting on particular field
     *
     * @param Iterator $i
     * @param string $field
     * @param string $desc
     *
     * @return void
     */
    function applySorting($i, $field, $desc)
    {
        if ($i instanceof DB_dsql) {
            $i->order($field, $desc);
        } elseif ($i instanceof SQL_Model) {
            $i->setOrder($field, $desc);
        } elseif ($i instanceof Model) {
            $i->setOrder($field, $desc);
        }
    }

    // }}}


    // {{{ Rendering

    /**
     * Render grid
     *
     * @return void
     */
    function render()
    {
        if ($this->js_widget) {
            $fn = str_replace('ui.', '', $this->js_widget);
            $this->js(true)
                ->_load($this->js_widget)
                ->$fn($this->js_widget_arguments);
        }

        parent::render();
    }
    
    /**
     * Render Totals row
     *
     * @return void
     */
    function renderTotalsRow()
    {
        parent::renderTotalsRow();
    }

    // }}}

    // {{{ Formatting

    /**
     * Additional formatting for Totals row
     *
     * Extends CompleteLister formatTotalsRow method.
     *
     * Note: in this method you should only add *additional* formatting of
     * totals row because standard row formatting will be already applied by
     * calling parent::formatTotalsRow().
     *
     * @return void
     */
    function formatTotalsRow()
    {
        // call CompleteLister formatTotalsRow method
        parent::formatTotalsRow();

        // additional formatting of totals row
        $totals_columns = array_keys($this->totals) ?: array();
        foreach ($this->columns as $field=>$column) {

            // process formatters (additional to default formatters)
            $this->executeFormatters($field, $column, 'format_totals_', true);
 
            // totals title displaying
            if ($field == $this->totals_title_field) {
                $this->setTDParam($field, 'style/font-weight', 'bold');
            }

            // apply TD parameters to all columns
            $this->applyTDParams($field, $this->totals_t);
        }

        // set title
        if ($this->totals_title_field && $this->totals) {
            $this->current_row[$this->totals_title_field] = sprintf(
                $this->totals_title,
                $this->current_row['row_count'],
                $this->current_row['plural_s']
            );
        }
    }

    /**
     * Returns ID of record
     *
     * @param string $idfield ID field name
     *
     * @return mixed
     */
    public function getCurrentIndex($idfield = 'id')
    {
        // TODO: think more to optimize this method
        if (is_array($this->data)) {
            return array_search(current($this->data), $this->data);
        }

        // else it is dsql dataset...
        return $this->current_row[$idfield];
    }

    /**
     * Sets TD params
     *
     * @param string $field
     * @param string $path
     * @param string $value
     *
     * @return void
     */
    public function setTDParam($field, $path, $value)
    {
        // if value is null, then do nothing
        if ($value === null) {
            return;
        }

        // adds a parameter. nested ones can be specified like 'style/color'
        $path = explode('/', $path);
        $current_position = &$this->tdparam[$this->getCurrentIndex()][$field];
        if (!is_array($current_position)) {
            $current_position = array();
        }
        
        foreach ($path as $part) {
            if (array_key_exists($part, $current_position)) {
                $current_position = &$current_position[$part];
            } else {
                $current_position[$part] = array();
                $current_position = &$current_position[$part];
            }
        }
        
        $current_position = $value;
    }

    /**
     * Apply TD parameters to appropriate template
     * 
     * You can pass row template to use too. That's useful to set up totals rows, for example.
     *
     * @param string $field Fieldname
     * @param SQLite $row_template Optional row template
     *
     * @return void
     */
    function applyTDParams($field, &$row_template = null)
    {
        // data row template by default
        if (!$row_template) {
            $row_template = &$this->row_t;
        }

        // setting cell parameters (tdparam)
        $tdparam = @$this->tdparam[$this->getCurrentIndex()][$field];
        $tdparam_str = '';
        if (is_array($tdparam)) {
            if (is_array($tdparam['style'])) {
                $tdparam_str .= 'style="';
                foreach ($tdparam['style'] as $key=>$value) {
                    $tdparam_str .= $key . ':' . $value . ';';
                }
                $tdparam_str .= '" ';
                unset($tdparam['style']);
            }
            
            //walking and combining string
            foreach ($tdparam as $id=>$value) {
                $tdparam_str .= $id . '="' . $value . '" ';
            }
            
            // set TD param to appropriate row template
            $row_template->set("tdparam_$field", trim($tdparam_str));
        }
    }

    // }}}

    // {{{ Totals

    /**
     * Sets totals title field and text
     *
     * @param string $field
     * @param string $title
     *
     * @return $this
     */
    function setTotalsTitle($field, $title = "Total: %s row%s")
    {
        $this->totals_title_field = $field;
        $this->totals_title = $title;

        return $this;
    }

    /**
     * Add current rendered row values to totals
     *
     * Called before each formatRow() call.
     *
     * @return void
     */
    function updateTotals()
    {
        parent::updateTotals();
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
        parent::updateGrandTotals();
    }

    /**
     * Additional formatting of number fields for totals row
     * 
     * @param string $field
     *
     * @return void
     */
    function format_totals_number($field) {}

    /**
     * Additional formatting of money fields for totals row
     * 
     * @param string $field
     *
     * @return void
     */
    function format_totals_money($field) {}

    /**
     * Additional formatting of real number fields for totals row
     * 
     * @param string $field
     *
     * @return void
     */
    function format_totals_real($field) {}

    /**
     * Additional formatting of expander fields for totals row
     *
     * Basically we remove everything from such field
     * 
     * @param string $field field name
     * @param array $column column configuration
     *
     * @return void
     */
    function format_totals_expander($field, $column) {
        @$this->current_row_html[$field] = '';
    }

    /**
     * Additional formatting of custom template fields for totals row
     *
     * Basically we remove everything from such field
     * 
     * @param string $field field name
     * @param array $column column configuration
     *
     * @return void
     */
    function format_totals_template($field, $column) {
        @$this->current_row_html[$field] = '';
    }

    /**
     * Additional formatting of delete button fields for totals row
     *
     * Basically we remove everything from such field
     * 
     * @param string $field field name
     * @param array $column column configuration
     *
     * @return void
     */
    function format_totals_delete($field, $column) {
        @$this->current_row_html[$field] = '';
    }

    // }}}

    // {{{ Expander

    /**
     * Initialize expander
     *
     * @param string $field field name
     *
     * @return void
     */
    function init_expander($field)
    {
        // set column style
        @$this->columns[$field]['thparam'] .= ' style="width:40px; text-align:center"';

        // set column refid - referenced model table for example
        if (!isset($this->columns[$field]['refid'])) {

            if ($this->model) {
                $refid = $this->model->table;
            } elseif ($this->dq) {
                $refid = $this->dq->args['table'];
            } else {
                $refid = preg_replace('/.*_/', '', $this->api->page);
            }

            $this->columns[$field]['refid'] = $refid;
        }

        // initialize button widget on page load
        $class = $this->name.'_'.$field.'_expander';
        $this->js(true)->find('.'.$class)->button();

        // initialize expander
        $this->js(true)
            ->_selector('.'.$class)
            ->_load('ui.atk4_expander')
            ->atk4_expander();
    }

    /**
     * Format expander
     *
     * @param string $field field name
     * @param array $column column configuration
     *
     * @return void
     */
    function format_expander($field, $column)
    {
        if (!@$this->current_row[$field]) {
            $this->current_row[$field] = $column['descr'];
        }

        // TODO: 
        // reformat this using Button, once we have more advanced system to
        // bypass rendering of sub-elements.
        // $this->current_row[$field] = $this->add('Button',null,false)
        $key   = $this->name . '_' . $field . '_';
        $id    = $key . $this->api->normalizeName($this->model->id);
        $class = $key . 'expander';

        @$this->current_row_html[$field] = 
            '<input type="checkbox" '.
                'class="'.$class.'" '.
                'id="'.$id.'" '.
                'rel="'.$this->api->url(
                    $column['page'] ?: './'.$field,
                    array(
                        'expander' => $field,
                        'expanded' => $this->name,
                        'cut_page' => 1,
                        // TODO: id is obsolete
                        //'id' => $this->model->id,
                        $this->columns[$field]['refid'].'_id' => $this->model->id
                    )
                ).'" '.
            '/>'.
            '<label for="'.$id.'">' . $this->current_row[$field] . '</label>';
    }
    // }}}

    // {{{ Formatters

    /**
     * Format field as HTML without encoding. Use with care.
     *
     * @param string $field
     *
     * @return void
     */
    function format_html($field)
    {
        $this->current_row_html[$field] = $this->current_row[$field];
    }

    /**
     * Format field as number
     *
     * @param string $field
     *
     * @return void
     */
    function format_number($field) {}

    /**
     * Initialize column as real number
     *
     * @param string $field
     *
     * @return void
     */
    function init_real($field)
    {
        @$this->columns[$field]['thparam'] .= ' style="text-align: right"';
    }

    /**
     * Format field as real number with 2 digit precision
     *
     * @param string $field
     *
     * @return void
     */
    function format_real($field)
    {
        $precision = 2;
        $m = (float) $this->current_row[$field];
        $this->current_row[$field] =
            is_null($this->current_row[$field])
                ? '-'
                : number_format($m, $precision);
        $this->setTDParam($field, 'align', 'right');
        $this->setTDParam($field, 'style/white-space', 'nowrap');
    }

    /**
     * Initialize column as money
     *
     * @param string $field
     *
     * @return void
     */
    function init_money($field)
    {
        @$this->columns[$field]['thparam'] .= ' style="text-align: right"';
    }

    /**
     * Format field as money with 2 digit precision
     *
     * @param string $field
     *
     * @return void
     */
    function format_money($field)
    {
        // use real number formatter
        $this->format_real($field);
        
        // negative values show in red color
        if ($this->current_row[$field] < 0) {
            $this->setTDParam($field, 'style/color', 'red');
        }
    }
    
    /**
     * Initialize column as boolean
     *
     * @param string $field
     *
     * @return void
     */
    function init_boolean($field)
    {
        @$this->columns[$field]['thparam'] .= ' style="text-align: center"';
    }

    /**
     * Format field as boolean
     *
     * @param string $field
     *
     * @return void
     */
    function format_boolean($field)
    {
        if ($this->current_row[$field] && $this->current_row[$field] !== 'N') {
            $this->current_row_html[$field] =
                '<div align=center>'.
                    '<span class="ui-icon ui-icon-check">yes</span>'.
                '</div>';
        } else {
            $this->current_row_html[$field] = '';
        }
    }

    /**
     * Format field as date
     *
     * @param string $field
     *
     * @return void
     */
    function format_date($field)
    {
        if (!$this->current_row[$field]) {
            $this->current_row[$field] = '-';
        } else {
            $this->current_row[$field] = date(
                $this->api->getConfig('locale/date', 'd/m/Y'),
                strtotime($this->current_row[$field])
            );
        }
    }

    /**
     * Format field as time
     *
     * @param string $field
     *
     * @return void
     */
    function format_time($field)
    {
        $this->current_row[$field] = date(
            $this->api->getConfig('locale/time', 'H:i:s'),
            strtotime($this->current_row[$field])
        );
    }

    /**
     * Format field as datetime
     *
     * @param string $field
     *
     * @return void
     */
    function format_datetime($field)
    {
        $d = $this->current_row[$field];
        if (!$d) {
            $this->current_row[$field] = '-';
        } else {
            if ($d instanceof MongoDate) {
                $this->current_row[$field] = date(
                    $this->api->getConfig('locale/datetime', 'd/m/Y H:i:s'),
                    $d->sec
                );
            } elseif (is_numeric($d)) {
                $this->current_row[$field] = date(
                    $this->api->getConfig('locale/datetime', 'd/m/Y H:i:s'),
                    $d
                );
            } else {
                $d = strtotime($d);
                $this->current_row[$field] = $d
                    ? date(
                        $this->api->getConfig('locale/datetime', 'd/m/Y H:i:s'),
                        $d
                    )
                    : '-';
            }
        }
    }

    /**
     * Format field as timestamp
     *
     * @param string $field
     *
     * @return void
     */
    function format_timestamp($field)
    {
        $this->format_datetime($field);
    }

    /**
     * Initialize column as fullwidth
     *
     * @param string $field
     *
     * @return void
     */
    function init_fullwidth($field)
    {
        @$this->columns[$field]['thparam'] .= ' style="width: 100%"';
    }

    /**
     * Format field as full width field
     *
     * @param string $field
     *
     * @return void
     */
    function format_fullwidth($field){}

    /**
     * Format field as no-wrap field
     *
     * @param string $field
     *
     * @return void
     */
    function format_nowrap($field)
    {
        $this->setTDParam($field, 'style/white-space', 'nowrap');
    }

    /**
     * Format field as wrap field
     *
     * @param string $field
     *
     * @return void
     */
    function format_wrap($field)
    {
        $this->setTDParam($field, 'style/white-space', 'wrap');
    }


    /**
     * Format shorttext field
     *
     * @param string $field
     *
     * @return void
     */
    function format_shorttext($field)
    {
        $text = $this->current_row[$field];
        if (strlen($text) > 60) {
            // Not sure about multi-byte support and execution speed of this
            $a = explode(PHP_EOL, wordwrap($text, 28, PHP_EOL, true), 2);
            $b = explode(PHP_EOL, wordwrap(strrev($text), 28, PHP_EOL, true), 2);
            $text = $a[0] . ' ~~~ ' . strrev($b[0]);
        }

        $this->current_row[$field] = $text;
        
        $this->setTDParam($field, 'title',
            htmlspecialchars($this->current_row[$field.'_original']));
    }

    /**
     * Format password field
     *
     * @param string $field
     *
     * @return void
     */
    function format_password($field)
    {
        $this->current_row[$field] = '***';
    }

    /**
     * Format field as New-Line to BR-eak
     *
     * @param string $field
     *
     * @return void
     */
    function format_nl2br($field)
    {
        $this->current_row[$field] = nl2br($this->current_row[$field]);
    }

    /**
     * Format field as HTML image tag
     *
     * @param string $field
     *
     * @return void
     */
    function format_image($field)
    {
        $this->current_row_html[$field] = '<img src="'.$this->current_row[$field].'" alt="" />';
    }

    /**
     * Format field as checkbox
     *
     * @param string $field
     *
     * @return void
     */
    function format_checkbox($field)
    {
        $this->current_row_html[$field] =
            '<input type="checkbox" '.
                'id="cb_'.$this->current_id.'" '.
                'name="cb_'.$this->current_id.'" '.
                'value="'.$this->current_id.'" '.
                ($this->current_row['selected'] == 'Y'
                    ? "checked "
                    : ""
                ).
            '/>';
        $this->setTDParam($field, 'width', '10');
        $this->setTDParam($field, 'align', 'center');
    }

    /**
     * Initialize buttons column
     *
     * @param string $field
     *
     * @return void
     */
    function init_button($field)
    {
        $this->_url[$field] = $this->api->url();
        @$this->columns[$field]['thparam'] .= ' style="width: 40px; text-align: center"';
        $this->js(true)->find('.button_'.$field)->button();
    }

    /**
     * Initialize confirm buttons column
     *
     * @param string $field
     *
     * @return void
     */
    function init_confirm($field)
    {
        $this->init_button($field);
    }

    /**
     * Initialize prompt buttons column
     *
     * @param string $field
     *
     * @return void
     */
    function init_prompt($field)
    {
        @$this->columns[$field]['thparam'] .= ' style="width: 40px; text-align: center"';
        $this->js(true)->find('.button_'.$field)->button();
    }

    /**
     * Format field as button
     *
     * @param string $field
     *
     * @return void
     */
    function format_button($field)
    {
        $url = clone $this->_url[$field];
        $class = $this->columns[$field]['button_class'].' button_'.$field;
        $icon = isset($this->columns[$field]['icon'])
                    ? $this->columns[$field]['icon']
                    : '';

        $this->current_row_html[$field] =
            '<button type="button" class="'.$class.'" '.
                'onclick="$(this).univ().ajaxec(\'' .
                    $url->set(array(
                        $field => $this->current_id,
                        $this->name.'_'.$field => $this->current_id
                    )) . '\')"'.
            '>'.
                $icon.
                $this->columns[$field]['descr'].
            '</button>';
    }

    /**
     * Format field as confirm button
     *
     * @param string $field
     *
     * @return void
     */
    function format_confirm($field)
    {
        $url = clone $this->_url[$field];
        $class = $this->columns[$field]['button_class'].' button_'.$field;
        $icon = isset($this->columns[$field]['icon'])
                    ? $this->columns[$field]['icon']
                    : '';
        $message = 'Are you sure?';

        $this->current_row_html[$field] =
            '<button type="button" class="'.$class.'" '.
                'onclick="$(this).univ().confirm(\''.$message.'\').ajaxec(\'' .
                    $url->set(array(
                        $field => $this->current_id,
                        $this->name.'_'.$field => $this->current_id
                    )).'\')"'.
            '>'.
                $icon.
                $this->columns[$field]['descr'].
            '</button>';
    }

    /**
     * Format field as prompt button
     *
     * @param string $field
     *
     * @return void
     */
    function format_prompt($field)
    {
        $class = $this->columns[$field]['button_class'].' button_'.$field;
        $icon = isset($this->columns[$field]['icon'])
                    ? $this->columns[$field]['icon']
                    : '';
        $message = 'Enter value: ';

        $this->current_row_html[$field] =
            '<button type="button" class="'.$class.'" '.
                'onclick="value=prompt(\''.$message.'\');$(this).univ().ajaxec(\'' .
                    $this->api->url(null, array(
                        $field => $this->current_id,
                        $this->name.'_'.$field => $this->current_id
                    )) .
                '&value=\'+value)"'.
            '>'.
                $icon.
                $this->columns[$field]['descr'].
            '</button>';
    }

    /**
     * Initialize column with delete buttons
     *
     * @param string $field
     *
     * @return void
     */
    function init_delete($field)
    {
        // set special CSS class for delete buttons to add some styling
        $this->columns[$field]['button_class'] = 'atk-delete-button';

        // if this was clicked, then delete record
        if ($id = @$_GET[$this->name.'_'.$field]) {
            
            // delete record
            $this->_performDelete($id);
            
            // show message
            $this->js()->univ()
                ->successMessage('Deleted Successfully')
                ->getjQuery()
                ->reload()
                ->execute();
        }

        // move button column at the end (to the right)
        $self = $this;
        $this->api->addHook('post-init', function() use($self, $field) {
            if ($self->hasColumn($field)) {
                $self->addOrder()->move($field, 'last')->now();
            }
        });

        // ask for confirmation
        $this->init_confirm($field);
    }

    /**
     * Delete record from DB
     *
     * Formatter init_delete() calls this to delete current record from DB
     *
     * @param string $id ID of record
     *
     * @return void
     */
    function _performDelete($id)
    {
        if ($this->model) {
            $this->model->delete($id);
        } else {
            $this->dq->where('id', $id)->delete();
        }
    }

    /**
     * Format field as delete button
     *
     * @param string $field
     *
     * @return void
     */
    function format_delete($field)
    {
        if (!$this->model) {
            throw new BaseException('delete column requires $model to be set');
        }
        $this->format_confirm($field);
    }

    /**
     * This allows you to use Template
     *
     * @param string $template template as a string
     *
     * @return $this
     */
    function setTemplate($template)
    {
        $this->columns[$this->last_column]['template'] = $this->add('SMlite')
            ->loadTemplateFromString($template);

        return $this;
    }

    /**
     * Format field using template
     *
     * @param string $field
     *
     * @return void
     */
    function format_template($field)
    {
        if (! ($t = $this->columns[$field]['template']) ) {
            throw new BaseException('use setTemplate() for field '.$field);
        }

        $this->current_row_html[$field] = $t
            ->set($this->current_row)
            ->trySet('_value_', $this->current_row[$field])
            ->render();
    }

    /**
     * Initialize column with links using template
     *
     * @param string $field
     *
     * @return void
     */
    function init_link($field)
    {
        $this->setTemplate('<a href="<?$_link?>"><?$'.$field.'?></a>');
    }

    /**
     * Format field as link
     *
     * @param string $field
     *
     * @return void
     */
    function format_link($field)
    {
        $this->current_row['_link'] =
            $this->api->url('./'.$field, array('id' => $this->current_id));

        if (!$this->current_row[$field]) {
            $this->current_row[$field] = $this->columns[$field]['descr'];
        }

        return $this->format_template($field);
    }

    // }}}
}
