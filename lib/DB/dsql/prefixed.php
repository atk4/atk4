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
class DB_dsql_prefixed extends DB_dsql {
    /** Prefix */
    public $prefix=null;

    function prefix($prefix){
        $this->prefix=$prefix;
        return $this;
    }
    function table($table=undefined,$alias=undefined){
        if($this->prefix && $alias==undefined)$alias=$table;
        return parent::table($this->prefix.$table,$alias);
    }
    function join($table,$on,$type='inner'){
        return parent::join($this->prefix.$table,$alias);
    }
}
