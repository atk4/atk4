<?php
/**
 * Undocumented.
 */
class DB_prefixed extends DB
{
    /** All queries generated through this driver will have this prefix */
    public $table_prefix = null;

    /** Returns Dynamic Query object compatible with this database driver (PDO). Also sets prefix */
    public function dsql($class = null)
    {
        $obj = parent::dsql($class);
        if (!$obj instanceof DB_dsql_prefixed) {
            throw $this->exception('Specified class must be descendant of DB_dsql_prefixed')
            ->addMoreInfo('class', $class);
        }
        if ($this->table_prefix) {
            $obj->prefix($this->table_prefix);
        }

        return $obj;
    }
}
