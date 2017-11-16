<?php
/**
 * Paginator needs to have source set (which can be either Model, DSQL or Array).
 * It will render itself into parent and will limit the source to display limited
 * number of records per page with ability to travel back and forth.
 */
class Paginator_Basic extends CompleteLister
{
    /**
     * How many records should we show per page.
     *
     * @var int
     */
    public $ipp = 30;

    /**
     * How many records should we skip. By default don't skip anything.
     *
     * @var int
     */
    public $skip = 0;

    /**
     * How many adjacent pages from current page should we show.
     *
     * @var int
     */
    public $range = 4;

    /**
     * Should we reload parent with AJAX ?
     *
     * @var bool
     */
    public $ajax_reload = true;

    /**
     * Should we remember page when user comes back ?
     *
     * @var bool
     */
    public $memorize = true;

    /**
     * GET argument to use to specify page
     *
     * @var string
     */
    public $skip_var = null;

    /**
     * Data source. Set with setSource().
     *
     * @var mixed
     */
    public $source = null;

    /**
     * lSet this to nicely redefine base page
     *
     * @var string
     */
    public $base_page = null;

    /** @var int */
    public $found_rows;
    /** @var int */
    public $cur_page;
    /** @var int */
    public $total_pages;

    // {{{ Inherited properties

    /** @var View */
    public $owner;

    // }}}

    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();

        if (!$this->skip_var) {
            $this->skip_var = $this->name.'_skip';
        }
        $this->skip_var = $this->_shorten($this->skip_var);
    }

    /**
     * Set number of items displayed per page.
     *
     * @param int $rows
     *
     * @return $this
     */
    public function setRowsPerPage($rows)
    {
        $this->ipp = $rows;

        return $this;
    }

    /**
     * @deprecated 4.3.2 use setRowsPerPage instead.
     */
    public function ipp($rows)
    {
        return $this->setRowsPerPage($rows);
    }

    /**
     * Set a custom source. Must be an object with foundRows() method.
     *
     * @param mixed $source
     */
    public function setSource($source)
    {
        if ($this->memorize) {
            if (isset($_GET[$this->skip_var])) {
                $this->skip = $this->memorize('skip', (int) $_GET[$this->skip_var]);
            } else {
                $this->skip = (int) $this->recall('skip');
            }
        } else {
            $this->skip = @$_GET[$this->skip_var] + 0;
        }

        // Start iterating early ($source = DSQL of model)
        if ($source instanceof SQL_Model) {
            $source = $source->_preexec();
        }

        if ($source instanceof DB_dsql) {
            $source->limit($this->ipp, $this->skip);
            $source->calcFoundRows();
            $this->source = $source;
        } elseif ($source instanceof \atk4\data\Model) {
            $this->source = $source->setLimit($this->ipp, $this->skip);
        } elseif ($source instanceof Model) {
            $this->source = $source->setLimit($this->ipp, $this->skip);
        } else {
            // NOTE: no limiting enabled for unknown data source
            $this->source = &$source;
        }
    }

    /**
     * Recursively render this view.
     */
    public function recursiveRender()
    {
        // get data source
        if (!$this->source) {

            // force grid sorting implemented in Grid_Advanced
            if ($this->owner instanceof Grid_Advanced) {
                $this->owner->getIterator();
            }

            // set data source for Paginator
            if ($this->owner->model) {
                $this->setSource($this->owner->model);
            } elseif ($this->owner->dq) {
                $this->setSource($this->owner->dq);
            } else {
                throw $this->exception('Unable to find source for Paginator');
            }
        }

        // calculate found rows
        if ($this->source instanceof DB_dsql) {
            $this->source->preexec();
            $this->found_rows = $this->source->foundRows();
        } elseif ($this->source instanceof \atk4\data\Model) {
            $this->found_rows = (int) $this->source->action('count')->getOne();
        } elseif ($this->source instanceof Model) {
            $this->found_rows = (int) $this->source->count();
        } else {
            $this->found_rows = count($this->source);
        }

        // calculate current page and total pages
        $this->cur_page = (int) floor($this->skip / $this->ipp) + 1;
        $this->total_pages = (int) ceil($this->found_rows / $this->ipp);

        if ($this->cur_page > $this->total_pages || ($this->cur_page == 1 && $this->skip != 0)) {
            $this->cur_page = 1;
            if ($this->memorize) {
                $this->memorize('skip', $this->skip = 0);
            }
            if ($this->source instanceof DB_dsql) {
                $this->source->limit($this->ipp, $this->skip);
                $this->source->rewind();                 // re-execute the query
            } elseif ($this->source instanceof \atk4\data\Model) {
                $this->source->setLimit($this->ipp, $this->skip);
            } elseif ($this->source instanceof Model) {
                $this->source->setLimit($this->ipp, $this->skip);
            } else {
                // Imants: not sure if this is correct, but it was like this before
                $this->source->setLimit($this->ipp, $this->skip);
            }
        }

        // no need for paginator if there is only one page
        if ($this->total_pages <= 1) {
            return $this->destroy();
        }

        if ($this->cur_page > 1) {
            /** @type View $v */
            $v = $this->add('View', null, 'prev');
            $v->setElement('a')
                ->setAttr('href', $this->app->url(
                    $this->base_page,
                    $u = array($this->skip_var => $pn = max(0, $this->skip - $this->ipp))
                ))
                ->setAttr('data-skip', $pn)
                ->set('« Prev')
                ;
        } else {
            $this->template->tryDel('prev');
        }

        if ($this->cur_page < $this->total_pages) {
            /** @type View $v */
            $v = $this->add('View', null, 'next');
            $v->setElement('a')
                ->setAttr('href', $this->app->url(
                    $this->base_page,
                    $u = array($this->skip_var => $pn = $this->skip + $this->ipp)
                ))
                ->setAttr('data-skip', $pn)
                ->set('Next »')
                ;
        } else {
            $this->template->tryDel('next');
        }

        // First page
        if ($this->cur_page > $this->range + 1) {
            /** @type View $v */
            $v = $this->add('View', null, 'first');
            $v->setElement('a')
                ->setAttr('href', $this->app->url(
                    $this->base_page,
                    $u = array($this->skip_var => $pn = max(0, 0))
                ))
                ->setAttr('data-skip', $pn)
                ->set('1')
                ;
            if ($this->cur_page > $this->range + 2) {
                /** @type View $v */
                $v = $this->add('View', null, 'points_left');
                $v->setElement('span')
                    ->set('...')
                    ;
            }
        }

        // Last page
        if ($this->cur_page < $this->total_pages - $this->range) {
            /** @type View $v */
            $v = $this->add('View', null, 'last');
            $v->setElement('a')
                ->setAttr('href', $this->app->url(
                    $this->base_page,
                    $u = array($this->skip_var => $pn = max(0, ($this->total_pages - 1) * $this->ipp))
                ))
                ->setAttr('data-skip', $pn)
                ->set($this->total_pages)
                ;
            if ($this->cur_page < $this->total_pages - $this->range - 1) {
                /** @type View $v */
                $v = $this->add('View', null, 'points_right');
                $v->setElement('span')
                    ->set('...')
                    ;
            }
        }

        // generate source for Paginator Lister (pages, links, labels etc.)
        $data = array();

        //setting cur as array seems not working in atk4.3. String is working
        $tplcur = $this->template->get('cur');
        $tplcur = (isset($tplcur[0])) ? $tplcur[0] : '';

        $range = range(
            max(1, $this->cur_page - $this->range),
            min($this->total_pages, $this->cur_page + $this->range)
        );
        foreach ($range as $p) {
            $data[] = array(
                'href' => $this->app->url($this->base_page, array($this->skip_var => $pn = ($p - 1) * $this->ipp)),
                'pn' => $pn,
                'cur' => $p == $this->cur_page ? $tplcur : '',
                'label' => $p,
            );
        }

        if ($this->ajax_reload) {
            $this->js(
                'click',
                $this->owner->js()->reload(
                    array($this->skip_var => $this->js()->_selectorThis()->attr('data-skip'))
                )
            )->_selector('#'.$this->name.' a');
        }

        parent::setSource($data);

        return parent::recursiveRender();
    }

    /**
     * Set default template.
     *
     * @return array|string
     */
    public function defaultTemplate()
    {
        return array('paginator42', 'paginator');
    }

    /**
     * Set default spot.
     *
     * @return string
     */
    public function defaultSpot()
    {
        return 'Paginator';
    }
}
