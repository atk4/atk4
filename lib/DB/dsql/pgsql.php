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
class DB_dsql_pgsql extends DB_dsql {
    public $bt='"';

    /** Use extended postgresql syntax for fetching ID */
    function SQLTemplate($mode){
        $template=parent::SQLTemplate($mode);
        switch($mode){
            case 'insert':
                return $template." returning id";
        }
        return $template;
    }
    function render_limit(){
        if($this->args['limit']){
            return 'limit '.
                (int)$this->args['limit']['cnt'].
                ' offset '.
                (int)$this->args['limit']['shift'];
        }
    }
}
