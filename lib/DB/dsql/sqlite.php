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
class DB_dsql_sqlite extends DB_dsql {
    function concat(){
        $t=clone $this;
        $t->template="([concat])";
        $t->args['concat']=func_get_args();
        return $t;
    }
    function render_concat(){
        $x=array();
        foreach($this->args['concat'] as $arg){
            $x[]=is_object($arg)?
                $this->consume($arg):
                $this->escape($arg);
        }
        return implode(' || ',$x);
    }
    function random(){
        return $this->expr('random()');
    }
    function describe($table){
        return $this->expr('pragma table_info([desc_table])')
            ->setCustom('desc_table',$table);
    }
}
