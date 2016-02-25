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
    public function table($table = undefined, $alias = undefined)
    {
        if ($this->prefix && $alias == undefined) {
            $alias = $table;
        }

        return parent::table($this->prefix.$table, $alias);
    }
    public function join($table, $on, $type = 'inner')
    {
        return parent::join($this->prefix.$table, $alias);
    }
}
