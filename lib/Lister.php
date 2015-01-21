<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Lister implements a very simple and fast way to output series
 * of data by applying template formatting
 *
 * @link http://agiletoolkit.org/doc/lister
 *
 * Use:
 *  $list=$this->add('Lister');
 *  $list->setModel('User');
 *
 * Template (view/users.html):
 *  <h4><?$name?></h4>
 *  <p><?$desc?></p>
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
class Lister extends View
{
    /** If lister data is retrieved from the SQL database, this will contain dynamic query. */
    public $dq = null;

    /** For other iterators, this variable will be used */
    public $iter = null;

    /** Points to current row before it's being outputted. Used in formatRow() */
    public $current_row = array();

    /** Similar to $current_row, but will be used for direct HTML output, no escaping. Use with care. */
    public $current_row_html = array();

    /** Contains ID of current record */
    public $current_id = null;

    /**
     * Similar to setModel, however you specify array of data here. setSource is
     * actually implemented around :php:class:`Controller_Data_Array`. actually
     * you can pass anything iterateable to setSource() as long as elements of
     * iterating produce either a string or array.
     */
	function setSource($source, $fields = null)
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
            } elseif ($source instanceof Iterator) {
                $this->iter=$source;
                return $this;
            }

            // Cast non-iterable objects into array
            $source = (array)$source;
        }

        // Set Array as a data source
        if (is_array($source)) {
            $m = $this->setModel('Model', $fields);
            $m->setSource('Array', $source);

            return $this;
        }

        // Set manually
        $this->dq = $this->api->db->dsql();
        $this->dq
            ->table($source)
            ->field($fields ?: '*');

        return $this;
    }

    /**
     * Returns data source iterator
     *
     * @return mixed
     */
    function getIterator()
    {
        if (is_null($i = $this->model ?: $this->dq ?: $this->iter)) {
            throw $this->exception('Please specify data source with setSource or setModel');
        }
        return $i;
    }

    /**
     * Renders everything
     *
     * @return void
     */
    function render()
    {
        $iter = $this->getIterator();
        foreach ($iter as $this->current_id=>$this->current_row) {

            if($this->current_row instanceof Model){
                $this->current_row=$this->current_row->get();
            }

            $this->formatRow();
            $this->output($this->rowRender($this->template));
        }
    }

    /**
     * Renders single row
     *
     * If you use for formatting then interact with template->set() directly
     * prior to calling parent
     *
     * @param SQLite $template template to use for row rendering
     *
     * @return string HTML of rendered template
     */
    function rowRender($template)
    {
        foreach ($this->current_row as $key=>$val) {
            if (isset($this->current_row_html[$key])) {
                continue;
            }
            $template->trySet($key, $val);
        }
        $template->setHTML($this->current_row_html);
        $template->trySet('id', $this->current_id);
        $o=$template->render();
        foreach(array_keys($this->current_row)+array_keys($this->current_row_html) as $k){
            $template->tryDel($k);
        }
        return $o;
    }

    /**
     * Called after iterating and may be redefined to change contents of
     * :php:attr:`Lister::current_row`. Redefine this method to change rendering
     * logic
     *
     * @return void
     */
    function formatRow()
    {
        $this->hook('formatRow');
    }

    /**
     * Sets default template
     *
     * @return array
     */
    function defaultTemplate()
    {
        return array('view/lister');
    }

    // {{{ Obsolete methods
    /** @obsolete set array source */
    function setStaticSource($data)
    {
        return $this->setSource($data);
    }
    // }}}
}
