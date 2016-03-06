<?php
/**
 * Lister implements a very simple and fast way to output series
 * of data by applying template formatting.
 *
 * Use:
 *  $list=$this->add('Lister');
 *  $list->setModel('User');
 *
 * Template (view/users.html):
 *  <h4><?$name?></h4>
 *  <p><?$desc?></p>
 */
class Lister extends View implements ArrayAccess
{
    /**
     * If lister data is retrieved from the SQL database, this will contain dynamic query.
     *
     * @todo It's also defined in AbstractView class, but looks like deprecated, so we better use
     * some other name of this property here.
     *
     * @var DB_dsql
     */
    public $dq = null;

    /**
     * For other iterators, this variable will be used.
     *
     * @var mixed
     */
    public $iter = null;

    /**
     * Points to the current record returned by iterator. Can be array or object.
     *
     * @var array|object
     */
    public $current = null;

    /**
     * Points to hash for current row before it's being outputted. Used in formatRow().
     *
     * @var array
     */
    public $current_row = array();

    /**
     * Similar to $current_row, but will be used for direct HTML output, no escaping. Use with care.
     *
     * @var array
     */
    public $current_row_html = array();

    /**
     * Contains ID of current record.
     *
     * @var mixed
     */
    public $current_id = null;



    /**
     * Similar to setModel, however you specify array of data here. setSource is
     * actually implemented around :php:class:`Controller_Data_Array`. actually
     * you can pass anything iterateable to setSource() as long as elements of
     * iterating produce either a string or array.
     *
     * @param mixed $source
     * @param array|string|null $fields
     *
     * @return $this
     */
    public function setSource($source, $fields = null)
    {
        // Set DSQL
        if ($source instanceof DB_dsql) {
            $this->dq = $source;

            return $this;
        }

        // SimpleXML and other objects
        if (is_object($source)) {
            if ($source instanceof Model) {
                throw $this->exception('Use setModel() for Models');
            } elseif ($source instanceof Controller) {
                throw $this->exception('Use setController() for Controllers');
            } elseif ($source instanceof Iterator or $source instanceof Closure) {
                $this->iter = $source;

                return $this;
            }

            // Cast non-iterable objects into array
            $source = (array) $source;
        }

        // Set Array as a data source
        if (is_array($source)) {
            $m = $this->setModel('Model', $fields);
            $m->setSource('Array', $source);

            return $this;
        }

        // Set manually
        $this->dq = $this->app->db->dsql();
        $this->dq
            ->table($source)
            ->field($fields ?: '*');

        return $this;
    }

    /**
     * Returns data source iterator.
     *
     * @return mixed
     */
    public function getIterator()
    {
        if (is_null($i = $this->model ?: $this->dq ?: $this->iter)) {
            throw $this->exception('Please specify data source with setSource or setModel');
        }
        if ($i instanceof Closure) {
            $i = call_user_func($i);
        }

        return $i;
    }

    /**
     * Renders everything.
     */
    public function render()
    {
        $iter = $this->getIterator();
        foreach ($iter as $this->current_id => $this->current_row) {
            if ($this->current_row instanceof Model) {
                $this->current_row = $this->current_row->get();
            } elseif (!is_array($this->current_row) && !($this->current_row instanceof ArrayAccess)) {
                // Looks like we won't be able to access current_row as array, so we will
                // copy it's value inside $this->current instead and produce an empty array
                // to be filled out by a custom iterators
                $this->current = $this->current_row;
                $this->current_row = get_object_vars($this->current);
            }

            $this->formatRow();
            $this->output($this->rowRender($this->template));
        }
    }

    /**
     * Renders single row.
     *
     * If you use for formatting then interact with template->set() directly
     * prior to calling parent
     *
     * @param Template $template template to use for row rendering
     *
     * @return string HTML of rendered template
     */
    public function rowRender($template)
    {
        foreach ($this->current_row as $key => $val) {
            if (isset($this->current_row_html[$key])) {
                continue;
            }
            $template->trySet($key, $val);
        }
        $template->setHTML($this->current_row_html);
        $template->trySet('id', $this->current_id);
        $o = $template->render();
        foreach (array_keys($this->current_row) + array_keys($this->current_row_html) as $k) {
            $template->tryDel($k);
        }

        return $o;
    }

    /**
     * Called after iterating and may be redefined to change contents of
     * :php:attr:`Lister::current_row`. Redefine this method to change rendering
     * logic.
     */
    public function formatRow()
    {
        $this->hook('formatRow');
    }

    // {{{ ArrayAccess support
    public function offsetExists($name)
    {
        return isset($this->current_row[$name]);
    }
    public function offsetGet($name)
    {
        return $this->current_row[$name];
    }
    public function offsetSet($name, $val)
    {
        $this->current_row[$name] = $val;
        $this->set($name, $val);
    }
    public function offsetUnset($name)
    {
        unset($current_row[$name]);
    }
    // }}}
    //
    /**
     * Sets default template.
     *
     * @return array
     */
    public function defaultTemplate()
    {
        return array('view/lister');
    }

    // {{{ Obsolete methods
    /** @obsolete set array source */
    public function setStaticSource($data)
    {
        return $this->setSource($data);
    }
    // }}}
}
