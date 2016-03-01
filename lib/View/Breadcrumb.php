<?php
/**
 * Undocumented.
 */
class View_Breadcrumb extends CompleteLister
{
    /**
     * How many levels in depth we show in breadcrumbs view.
     * By default - show all.
     *
     * @var int
     */
    public $max_depth;



    /**
     * Format row (overwrite)
     */
    public function formatRow()
    {
        parent::formatRow();

        $page = $this->model['page'];

        if (!$page && $this->max_depth) {
            // by default resort to parent pages
            $tmp = array();
            for ($i = 0; $i < $this->max_depth; ++$i) {
                $tmp[] = '..';
            }
            --$this->max_depth;

            $page = $this->app->url(implode('/', $tmp));
        }

        if ($page) {
            $this->current_row_html['crumb'] = '<a href="'.$this->app->url($page).'">'.
                htmlspecialchars($this->model['name']).
                '</a>';
        } else {
            $this->current_row_html['crumb'] = htmlspecialchars($this->model['name']);
        }
    }

    /**
     * Render object
     */
    public function render()
    {
        $this->max_depth = count(parent::setModel($this->model)) - 1;

        return parent::render();
    }

    /**
     * Default template.
     *
     * @return array|string
     */
    public function defaultTemplate()
    {
        return array('view/breadcrumb');
    }
}
