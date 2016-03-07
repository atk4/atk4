<?php
/**
 * This is a Basic Grid implementation, which produces fully
 * functional HTML grid capable of filtering, sorting, paginating
 * and using multiple column formatters.
 *
 * Use:
 *  $grid=$this->add('Grid');
 *  $grid->setModel('User');
 */
class Grid_Advanced extends Grid_Basic
{
    /** Sorting */
    public $sortby = '0';
    public $sortby_db = null;

    /** For totals */
    private $totals_title_field = null;
    private $totals_title = '';



    /** @var array Static data source? */
    public $data = null;

    /**
     * Paginator object.
     *
     * @see addPaginator()
     * @var Paginator
     */
    public $paginator = null;

    /**
     * Paginator class name.
     *
     * @see enablePaginator()
     * @var string
     * */
    public $paginator_class = 'Paginator';

    /**
     * QuickSearch object.
     *
     * @see addQuickSearch()
     * @var QuickSearch
     */
    public $quick_search = null;

    /**
     * QuickSearch class name.
     *
     * @see enableQuickSearch()
     * @var string
     * */
    public $quick_search_class = 'QuickSearch';

    /**
     * $tdparam property is an array with cell parameters specified in td tag.
     * This should be a hash: 'param_name'=>'param_value'
     * Following parameters treated and processed in a special way:
     * 1) 'style': nested array, style parameter.
     *             items of this nested array converted to a form of style:
     *             style="param_name: param_value; param_name: param_value".
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
     *
     * @var array
     */
    protected $tdparam = array();

    // JavaScript widget
    public $js_widget = 'ui.atk4_grid';
    public $js_widget_arguments = array();

    /** @private used in button formatters to share URL between methods */
    public $_url = array();

    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();

        // sorting support
        $this->sortby =
            isset($_GET[$this->name.'_sort'])
                ? $this->memorize('sortby', $_GET[$this->name.'_sort'])
                : $this->recall('sortby', '0');
    }

    // {{{ Misc features

    /**
     * Add default paginator to the grid.
     *
     * @param int   $rows    row count per page
     * @param array $options optional options array
     *
     * @return $this
     */
    public function enablePaginator($rows = 25, $options = null)
    {
        $this->addPaginator($rows, $options);

        return $this;
    }

    /**
     * Adds paginator to the grid.
     *
     * @param int    $rows    row count per page
     * @param array  $options optional options array
     * @param string $class   optional paginator class name
     *
     * @return Paginator
     *
     * @todo decide, maybe we need to add $spot optional template spot like in addQuickSearch()
     */
    public function addPaginator($rows = 25, $options = null, $class = null)
    {
        // add only once
        // @todo decide, maybe we should destroy and recreate to keep last one
        if ($this->paginator) {
            return $this->paginator;
        }

        $this->paginator = $this->add($class ?: $this->paginator_class, $options);
        /** @var Paginator $this->paginator */
        $this->paginator->setRowsPerPage($rows);

        return $this->paginator;
    }

    /**
     * Adds default QuickSearch to the grid.
     *
     * @param array $fields  array of fieldnames used in quick search
     * @param array $options optional options array
     *
     * @return $this
     */
    public function enableQuickSearch($fields, $options = null)
    {
        $this->addQuickSearch($fields, $options);

        return $this;
    }

    /**
     * Adds QuickSearch to the grid.
     *
     * @param array  $fields  array of fieldnames used in quick search
     * @param array  $options optional options array
     * @param string $class   optional quick search object class
     * @param string $spot    optional template spot
     *
     * @return QuickSearch
     */
    public function addQuickSearch($fields, $options = null, $class = null, $spot = null)
    {
        // add only once
        // @todo decide, maybe we should destroy and recreate to keep last one
        if ($this->quick_search) {
            return $this->quick_search;
        }

        $this->quick_search = $this->add($class ?: $this->quick_search_class, $options, $spot ?: 'quick_search');
        /** @var QuickSearch $this->quick_search */
        $this->quick_search
            ->useWith($this)
            ->useFields($fields);

        return $this->quick_search;
    }

    /**
     * Adds column ordering object.
     *
     * With it you can reorder your columns
     *
     * @return Order
     */
    public function addOrder()
    {
        /** @var Order $o */
        $o = $this->add('Order', 'columns');

        return $o->useArray($this->columns);
    }

    /**
     * Adds column with checkboxes on the basis of Model definition.
     *
     * @param mixed $field should be Form_Field object or jQuery selector of
     *                     1 field. When passing it as jQuery selector don't
     *                     forget to use hash sign like "#myfield"
     */
    public function addSelectable($field)
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
     * Returns data source iterator.
     *
     * @return mixed
     */
    public function getIterator()
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
     * Make sortable.
     *
     * @param string $db_sort
     *
     * @return $this
     */
    public function makeSortable($db_sort = null)
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

        switch ((string) $this->sortby) {

            // we are already sorting by this column
            case $this->last_column:
                $info = array('1', $reverse ? 0 : ('-'.$this->last_column));
                $this->sortby_db = $db_sort;
                break;

            // We are sorted reverse by this column
            case '-'.$this->last_column:
                $info = array('2', $reverse ? $this->last_column : '0');
                $this->sortby_db = '-'.$db_sort;
                break;

            // we are not sorted by this column
            default:
                $info = array('0', $reverse ? ('-'.$this->last_column) : $this->last_column);
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
    public function staticSortCompare($str1, $str2)
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
     * Apply sorting on particular field.
     *
     * @param Iterator    $i
     * @param string      $field
     * @param string|bool $desc
     */
    public function applySorting($i, $field, $desc)
    {
        if (!$field) {
            return;
        }
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
     * Render grid.
     */
    public function render()
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
     * Render Totals row.
     */
    public function renderTotalsRow()
    {
        parent::renderTotalsRow();
    }

    // }}}

    // {{{ Formatting

    /**
     * Additional formatting for Totals row.
     *
     * Extends CompleteLister formatTotalsRow method.
     *
     * Note: in this method you should only add *additional* formatting of
     * totals row because standard row formatting will be already applied by
     * calling parent::formatTotalsRow().
     */
    public function formatTotalsRow()
    {
        // call CompleteLister formatTotalsRow method
        parent::formatTotalsRow();

        // additional formatting of totals row
        $totals_columns = array_keys($this->totals) ?: array();
        foreach ($this->columns as $field => $column) {
            if (in_array($field, $totals_columns)) {
                // process formatters (additional to default formatters)
                $this->executeFormatters($field, $column, 'format_totals_', true);
            } else {
                // show empty cell if totals are not calculated for this column
                $this->current_row_html[$field] = '';
            }

            // totals title displaying
            if ($field == $this->totals_title_field) {
                $this->setTDParam($field, 'style/font-weight', 'bold');
            }

            // apply TD parameters to all columns
            $this->applyTDParams($field, $this->totals_t);
        }

        // set title
        if ($this->totals_title_field && $this->totals) {
            $this->current_row_html[$this->totals_title_field] = sprintf(
                $this->totals_title,
                $this->current_row['row_count'],
                $this->current_row['plural_s']
            );
        }
    }

    /**
     * Returns ID of record.
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
     * Sets TD params.
     *
     * @param string $field
     * @param string $path
     * @param string $value
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
     * Apply TD parameters to appropriate template.
     *
     * You can pass row template to use too. That's useful to set up totals rows, for example.
     *
     * @param string $field        Fieldname
     * @param SQLite $row_template Optional row template
     */
    public function applyTDParams($field, &$row_template = null)
    {
        // data row template by default
        if (!$row_template) {
            $row_template = &$this->row_t;
        }

        // setting cell parameters (tdparam)
        $tdparam = $this->tdparam[$this->getCurrentIndex()][$field];
        $tdparam_str = '';
        if (is_array($tdparam)) {
            if (is_array($tdparam['style'])) {
                $tdparam_str .= 'style="';
                foreach ($tdparam['style'] as $key => $value) {
                    $tdparam_str .= $key.':'.$value.';';
                }
                $tdparam_str .= '" ';
                unset($tdparam['style']);
            }

            //walking and combining string
            foreach ($tdparam as $id => $value) {
                $tdparam_str .= $id.'="'.$value.'" ';
            }

            // set TD param to appropriate row template
            $row_template->set("tdparam_$field", trim($tdparam_str));
        }
    }

    // }}}

    // {{{ Totals

    /**
     * Sets totals title field and text.
     *
     * @param string $field
     * @param string $title
     *
     * @return $this
     */
    public function setTotalsTitle($field, $title = 'Total: %s row%s')
    {
        $this->totals_title_field = $field;
        $this->totals_title = $title;

        return $this;
    }

    /**
     * Add current rendered row values to totals.
     *
     * Called before each formatRow() call.
     */
    public function updateTotals()
    {
        parent::updateTotals();
    }

    /**
     * Calculate grand totals of all rows.
     *
     * Called one time on rendering phase - before renderRows() call.
     */
    public function updateGrandTotals()
    {
        parent::updateGrandTotals();
    }

    /**
     * Additional formatting of number fields for totals row.
     *
     * @param string $field
     */
    public function format_totals_number($field)
    {
    }

    /**
     * Additional formatting of money fields for totals row.
     *
     * @param string $field
     */
    public function format_totals_money($field)
    {
    }

    /**
     * Additional formatting of real number fields for totals row.
     *
     * @param string $field
     */
    public function format_totals_real($field)
    {
    }

    /**
     * Additional formatting of expander fields for totals row.
     *
     * Basically we remove everything from such field
     *
     * @param string $field  field name
     * @param array  $column column configuration
     */
    public function format_totals_expander($field, $column)
    {
        $this->current_row_html[$field] = '';
    }

    /**
     * Additional formatting of custom template fields for totals row.
     *
     * Basically we remove everything from such field
     *
     * @param string $field  field name
     * @param array  $column column configuration
     */
    public function format_totals_template($field, $column)
    {
        $this->current_row_html[$field] = '';
    }

    /**
     * Additional formatting of checkbox fields column for totals row.
     *
     * Basically we remove everything from such field
     *
     * @param string $field  field name
     * @param array  $column column configuration
     */
    public function format_totals_checkbox($field, $column)
    {
        $this->current_row_html[$field] = '';
    }

    /**
     * Additional formatting of delete button fields for totals row.
     *
     * Basically we remove everything from such field
     *
     * @param string $field  field name
     * @param array  $column column configuration
     */
    public function format_totals_delete($field, $column)
    {
        $this->current_row_html[$field] = '';
    }

    // }}}

    // {{{ Expander

    /**
     * Initialize expander.
     *
     * @param string $field field name
     */
    public function init_expander($field)
    {
        // set column style
        $this->columns[$field]['thparam'] .= ' style="width:40px; text-align:center"';

        // set column refid - referenced model table for example
        if (!isset($this->columns[$field]['refid'])) {
            if ($this->model) {
                $refid = $this->model->table;
            } elseif ($this->dq) {
                $refid = $this->dq->args['table'];
            } else {
                $refid = preg_replace('/.*_/', '', $this->app->page);
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
     * Format expander.
     *
     * @param string $field  field name
     * @param array  $column column configuration
     */
    public function format_expander($field, $column)
    {
        if (!$this->current_row[$field]) {
            $this->current_row[$field] = $column['descr'];
        }

        // TODO:
        // reformat this using Button, once we have more advanced system to
        // bypass rendering of sub-elements.
        // $this->current_row[$field] = $this->add('Button',null,false)
        $key = $this->name.'_'.$field.'_';
        $id = $key.$this->app->normalizeName($this->model->id);
        $class = $key.'expander';

        $this->current_row_html[$field] =
            '<input type="checkbox" '.
                'class="'.$class.'" '.
                'id="'.$id.'" '.
                'rel="'.$this->app->url(
                    $column['page'] ?: './'.$field,
                    array(
                        'expander' => $field,
                        'expanded' => $this->name,
                        'cut_page' => 1,
                        // TODO: id is obsolete
                        //'id' => $this->model->id,
                        $this->columns[$field]['refid'].'_'.$this->model->id_field => $this->model->id,
                    )
                ).'" '.
            '/>'.
            '<label for="'.$id.'">'.$this->current_row[$field].'</label>';
    }
    // }}}

    // {{{ Formatters

    /**
     * Format field as HTML without encoding. Use with care.
     *
     * @param string $field
     */
    public function format_html($field)
    {
        $this->current_row_html[$field] = $this->current_row[$field];
    }

    /**
     * Format field as number.
     *
     * @param string $field
     */
    public function format_number($field)
    {
    }

    /**
     * Initialize column as real number.
     *
     * @param string $field
     */
    public function init_float($field)
    {
        $this->columns[$field]['thparam'] .= ' style="text-align: right"';
    }

    /**
     * Format field as real number with 2 digit precision.
     *
     * @param string $field
     */
    public function format_float($field)
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

    public function init_real($field)
    {
        return $this->init_float($field);
    }

    public function format_real($field)
    {
        return $this->format_float($field);
    }

    /**
     * Initialize column as money.
     *
     * @param string $field
     */
    public function init_money($field)
    {
        $this->columns[$field]['thparam'] .= ' style="text-align: right"';
    }

    /**
     * Format field as money with 2 digit precision.
     *
     * @param string $field
     */
    public function format_money($field)
    {
        // use real number formatter
        $this->format_real($field);

        // negative values show in red color
        if ($this->current_row[$field] < 0) {
            $this->setTDParam($field, 'style/color', 'red');
        }
    }

    /**
     * Initialize column as boolean.
     *
     * @param string $field
     */
    public function init_boolean($field)
    {
        $this->columns[$field]['thparam'] .= ' style="text-align: center"';
    }

    /**
     * Format field as boolean.
     *
     * @param string $field
     */
    public function format_boolean($field)
    {
        if ($this->current_row[$field] && $this->current_row[$field] !== 'N') {
            $this->current_row_html[$field] =
                '<div align=center>'.
                    '<i class="icon-check">'.$this->app->_('yes').'</i>'.
                '</div>';
        } else {
            $this->current_row_html[$field] = '';
        }
    }

    /**
     * Format field as object.
     *
     * @param string $field
     */
    public function format_object($field)
    {
        $this->current_row[$field] = (string) $this->current_row[$field];

        return $this->format_shorttext($field);
    }

    /**
     * Format field as date.
     *
     * @param string $field
     */
    public function format_date($field)
    {
        if (!$this->current_row[$field]) {
            $this->current_row[$field] = '-';
        } else {
            $this->current_row[$field] = date(
                $this->app->getConfig('locale/date', 'd/m/Y'),
                strtotime($this->current_row[$field])
            );
        }
    }

    /**
     * Format field as time.
     *
     * @param string $field
     */
    public function format_time($field)
    {
        $this->current_row[$field] = date(
            $this->app->getConfig('locale/time', 'H:i:s'),
            strtotime($this->current_row[$field])
        );
    }

    /**
     * Format field as datetime.
     *
     * @param string $field
     */
    public function format_datetime($field)
    {
        $d = $this->current_row[$field];
        if (!$d) {
            $this->current_row[$field] = '-';
        } else {
            if ($d instanceof MongoDate) {
                $this->current_row[$field] = date(
                    $this->app->getConfig('locale/datetime', 'd/m/Y H:i:s'),
                    $d->sec
                );
            } elseif (is_numeric($d)) {
                $this->current_row[$field] = date(
                    $this->app->getConfig('locale/datetime', 'd/m/Y H:i:s'),
                    $d
                );
            } else {
                $d = strtotime($d);
                $this->current_row[$field] = $d
                    ? date(
                        $this->app->getConfig('locale/datetime', 'd/m/Y H:i:s'),
                        $d
                    )
                    : '-';
            }
        }
    }

    /**
     * Format field as timestamp.
     *
     * @param string $field
     */
    public function format_timestamp($field)
    {
        $this->format_datetime($field);
    }

    /**
     * Initialize column as fullwidth.
     *
     * @param string $field
     */
    public function init_fullwidth($field)
    {
        $this->columns[$field]['thparam'] .= ' style="width: 100%"';
    }

    /**
     * Format field as full width field.
     *
     * @param string $field
     */
    public function format_fullwidth($field)
    {
    }

    /**
     * Format field as no-wrap field.
     *
     * @param string $field
     */
    public function format_nowrap($field)
    {
        $this->setTDParam($field, 'class', 'atk-text-nowrap');
    }

    /**
     * Format field as wrap field.
     *
     * @param string $field
     */
    public function format_wrap($field)
    {
        $this->setTDParam($field, 'class', 'atk-text-wrap');
    }

    /**
     * Format shorttext field.
     *
     * @param string $field
     */
    public function format_shorttext($field)
    {
        $text = $this->current_row[$field];
        if (strlen($text) > 60) {
            // Not sure about multi-byte support and execution speed of this
            $a = explode(PHP_EOL, wordwrap($text, 28, PHP_EOL, true), 2);
            $b = explode(PHP_EOL, wordwrap(strrev($text), 28, PHP_EOL, true), 2);
            $text = $a[0].' ~~~ '.strrev($b[0]);
        }

        $this->current_row[$field] = $text;

        $this->setTDParam(
            $field,
            'title',
            $this->app->encodeHtmlChars(strip_tags($this->current_row[$field.'_original']), ENT_QUOTES)
        );
    }

    /**
     * Format password field.
     *
     * @param string $field
     */
    public function format_password($field)
    {
        $this->current_row[$field] = '***';
    }

    /**
     * Format field as New-Line to BR-eak.
     *
     * @param string $field
     */
    public function format_nl2br($field)
    {
        $this->current_row[$field] = nl2br($this->current_row[$field]);
    }

    /**
     * Format field as HTML image tag.
     *
     * @param string $field
     */
    public function format_image($field)
    {
        $this->current_row_html[$field] = '<img src="'.$this->current_row[$field].'" alt="" />';
    }

    /**
     * Format field as checkbox.
     *
     * @param string $field
     */
    public function format_checkbox($field)
    {
        $this->current_row_html[$field] =
            '<input type="checkbox" '.
                'id="cb_'.$this->current_id.'" '.
                'name="cb_'.$this->current_id.'" '.
                'value="'.$this->current_id.'" '.
                ($this->current_row['selected'] == 'Y'
                    ? 'checked '
                    : ''
                ).
            '/>';
        $this->setTDParam($field, 'width', '10');
        $this->setTDParam($field, 'align', 'center');
    }

    /**
     * Initialize buttons column.
     *
     * @param string $field
     */
    public function init_button($field)
    {
        $this->on('click', '.do-'.$field)->univ()->ajaxec(array(
            $this->app->url(),
            $field => $a = $this->js()->_selectorThis()->data('id'),
            $this->name.'_'.$field => $a,
            ));

        /*
        $this->columns[$field]['thparam'] .= ' style="width: 40px; text-align: center"';
        $this->js(true)->find('.button_'.$field)->button();
        */
    }

    /**
     * Initialize confirm buttons column.
     *
     * @param string $field
     */
    public function init_confirm($field)
    {
        $this->on('click', '.do-'.$field)->univ()->confirm('Are you sure?')->ajaxec(array(
            $this->app->url(),
            $field => $a = $this->js()->_selectorThis()->data('id'),
            $this->name.'_'.$field => $a,
        ));
    }

    /**
     * Initialize prompt buttons column.
     *
     * @param string $field
     */
    public function init_prompt($field)
    {
        $this->columns[$field]['thparam'] .= ' style="width: 40px; text-align: center"';
        //$this->js(true)->find('.button_'.$field)->button();
    }

    /**
     * Format field as button.
     *
     * @param string $field
     */
    public function format_button($field)
    {
        $class = $this->columns[$field]['button_class'];

        $icon = $this->columns[$field]['icon'];
        if ($icon) {
            if ($icon[0] != '<') {
                $icon = '<span class="icon-'.$icon.'"></span>';
            }
            $icon .= '&nbsp;';
        }

        $this->current_row_html[$field] =
            '<button class="atk-button-small do-'.$field.'  '.$class.'" data-id="'.$this->model->id.'">'.
                $icon.$this->columns[$field]['descr'].
            '</button>';
    }

    /**
     * Format field as confirm button.
     *
     * @param string $field
     */
    public function format_confirm($field)
    {
        return $this->format_button($field);

        /* unreachable code
        $url = clone $this->_url[$field];
        $class = $this->columns[$field]['button_class'].' button_'.$field;
        $icon = isset($this->columns[$field]['icon'])
                    ? $this->columns[$field]['icon']
                    : '';
        $message = 'Are you sure?';

        $this->current_row_html[$field] =
            '<button type="button" class="'.$class.'" '.
                'onclick="$(this).univ().confirm(\''.$message.'\').ajaxec(\''.
                    $url->set(array(
                        $field => $this->current_id,
                        $this->name.'_'.$field => $this->current_id,
                    )).'\')"'.
            '>'.
                $icon.
                $this->columns[$field]['descr'].
            '</button>';
        */
    }

    /**
     * Format field as prompt button.
     *
     * @param string $field
     */
    public function format_prompt($field)
    {
        $class = $this->columns[$field]['button_class'].' button_'.$field;
        $icon = isset($this->columns[$field]['icon'])
                    ? $this->columns[$field]['icon']
                    : '';
        $message = 'Enter value: ';

        $this->current_row_html[$field] =
            '<button type="button" class="'.$class.'" '.
                'onclick="value=prompt(\''.$message.'\');$(this).univ().ajaxec(\''.
                    $this->app->url(null, array(
                        $field => $this->current_id,
                        $this->name.'_'.$field => $this->current_id,
                    )).
                '&value=\'+value)"'.
            '>'.
                $icon.
                $this->columns[$field]['descr'].
            '</button>';
    }

    /**
     * Initialize column with delete buttons.
     *
     * @param string $field
     */
    public function init_delete($field)
    {
        // set special CSS class for delete buttons to add some styling
        $this->columns[$field]['button_class'] = 'atk-effect-danger atk-delete-button';
        $this->columns[$field]['icon'] = 'trash';

        // if this was clicked, then delete record
        if ($id = $_GET[$this->name.'_'.$field]) {

            // delete record
            $this->_performDelete($id);

            // show message
            $this->js()->univ()
                ->successMessage('Deleted Successfully')
                ->reload()
                ->execute();
        }

        // move button column at the end (to the right)
        $self = $this;
        $this->app->addHook('post-init', function () use ($self, $field) {
            if ($self->hasColumn($field)) {
                $self->addOrder()->move($field, 'last')->now();
            }
        });

        // ask for confirmation
        $this->init_confirm($field);
    }

    /**
     * Delete record from DB.
     *
     * Formatter init_delete() calls this to delete current record from DB
     *
     * @param string $id ID of record
     */
    public function _performDelete($id)
    {
        if ($this->model) {
            $this->model->delete($id);
        } else {
            $this->dq->where('id', $id)->delete();
        }
    }

    /**
     * Format field as delete button.
     *
     * @param string $field
     */
    public function format_delete($field)
    {
        if (!$this->model) {
            throw new BaseException('delete column requires $model to be set');
        }
        $this->format_confirm($field);
    }

    /**
     * This allows you to use Template.
     *
     * @param string $template template as a string
     *
     * @return $this
     */
    public function setTemplate($template, $field = null)
    {
        if ($field === null) {
            $field = $this->last_column;
        }

        /** @var GiTemplate $gi */
        $gi = $this->add('GiTemplate');
        $this->columns[$field]['template'] = $gi->loadTemplateFromString($template);

        return $this;
    }

    /**
     * Format field using template.
     *
     * @param string $field
     */
    public function format_template($field)
    {
        if (!($t = $this->columns[$field]['template'])) {
            throw new BaseException('use setTemplate() for field '.$field);
        }

        $this->current_row_html[$field] = $t
            ->trySet('id', $this->current_id)
            ->set($this->current_row)
            ->trySet('_value_', $this->current_row[$field])
            ->render();
    }

    /**
     * Initialize column with links using template.
     *
     * @param string $field
     */
    public function init_link($field)
    {
        $this->setTemplate('<a href="{$_link}">{$'.$field.'}</a>', $field);
    }

    /**
     * Format field as link.
     *
     * @param string $field
     */
    public function format_link($field)
    {
        $page = $this->columns[$field]['page'] ?: './'.$field;
        $attr = $this->columns[$field]['id_field'] ?: 'id';
        $this->current_row['_link'] =
            $this->app->url($page, array($attr => $this->columns[$field]['id_value']
                ? ($this->model[$this->columns[$field]['id_value']]
                    ?: $this->current_row[$this->columns[$field]['id_value'].'_original'])
                : $this->current_id, ));

        if (!$this->current_row[$field]) {
            $this->current_row[$field] = $this->columns[$field]['descr'];
        }

        return $this->format_template($field);
    }

    // }}}
}
