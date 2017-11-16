<?php
/**
 * This is a MySQL driver for Dynamic SQL. To make Agile Toolkit support your
 * PDO-compatible database create your own class DB_dsql_myclass and redefine
 * rendering methods which appear differently on your database.
 */
class DB_dsql_mysql extends DB_dsql
{
    public function init()
    {
        parent::init();
        $this->sql_templates['update'] = 'update [table] set [set] [where]';
    }
    public function calcFoundRows()
    {
        return $this->option('SQL_CALC_FOUND_ROWS');
    }
}
