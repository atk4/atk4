<?php
/**
 * Undocumented.
 */
class DB_dsql_prefixed extends DB_dsql
{
    /** Prefix */
    public $prefix = null;

    public function prefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }
    public function table($table = UNDEFINED, $alias = UNDEFINED)
    {
        if ($this->prefix && $alias == UNDEFINED) {
            $alias = $table;
        }

        return parent::table($this->prefix.$table, $alias);
    }
    public function join($table, $on, $type = 'inner', $alias = UNDEFINED)
    {
        return parent::join($this->prefix.$table, $alias);
    }
}
