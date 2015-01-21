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
class DB_prefixed extends DB {
    /** All queries generated through this driver will have this prefix */
    public $table_prefix=null;

    /** Returns Dynamic Query object compatible with this database driver (PDO). Also sets prefix */
    function dsql($class=null){
        $obj=parent::dsql($class);
        if(!$obj instanceof DB_dsql_prefixed)
            throw $this->exception('Specified class must be descendant of DB_dsql_prefixed')
            ->addMoreInfo('class',$class);
        if($this->table_prefix)$obj->prefix($this->table_prefix);
        return $obj;
    }
}
