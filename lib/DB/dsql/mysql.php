<?php
/**
 * This is a MySQL driver for Dynamic SQL. To make Agile Toolkit support your 
 * PDO-compatible database create your own class DB_dsql_myclass and redefine
 * rendering methods which appear differently on your database.
 */
class DB_dsql_mysql extends DB_dsql {
    function render_limit(){
        if($this->args['limit']){
            return 'limit '.
                (int)$this->args['limit']['shift'].
                ', '.
                (int)$this->args['limit']['cnt'];
        }
    }
}
