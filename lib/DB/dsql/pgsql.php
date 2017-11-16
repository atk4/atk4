<?php
/**
 * Undocumented.
 */
class DB_dsql_pgsql extends DB_dsql
{
    public $bt = '"';

    /** Use extended postgresql syntax for fetching ID */
    public function SQLTemplate($mode)
    {
        $template = parent::SQLTemplate($mode);
        switch ($mode) {
            case 'insert':
                return $template.' returning id';
        }

        return $template;
    }
    public function render_limit()
    {
        if ($this->args['limit']) {
            return 'limit '.
                (int) $this->args['limit']['cnt'].
                ' offset '.
                (int) $this->args['limit']['shift'];
        }
    }
}
