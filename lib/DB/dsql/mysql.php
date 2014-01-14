<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * This is a MySQL driver for Dynamic SQL. To make Agile Toolkit support your 
 * PDO-compatible database create your own class DB_dsql_myclass and redefine
 * rendering methods which appear differently on your database.
 */
class DB_dsql_mysql extends DB_dsql {
    function init(){
        parent::init();
        $this->sql_templates['update']="update [table] set [set] [where]";
    }
    function calcFoundRows(){
        return $this->option('SQL_CALC_FOUND_ROWS');
    }
}
