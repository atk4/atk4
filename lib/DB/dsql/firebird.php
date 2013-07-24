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
 * This is a Firebird/Interbase driver for Dynamic SQL.
 * To be able to use it in your proyects make sure you have the Firebird PDO driver installed for PHP.
 * For more info see PHP manual: http://php.net/manual/en/ref.pdo-firebird.php
 * Howto for compiling/installing on Linux: http://mapopa.blogspot.com/2009/04/php5-and-firebird-pdo-on-ubuntu-hardy.html
 *
 */
class DB_dsql_firebird extends DB_dsql {
     public $bt='';
    function init(){
        parent::init();
        $this->sql_templates['update']="update [table] set [set] [where]";
        $this->sql_templates['select']="select [limit] [options] [field] [from] [table] [join] [where] [group] [having] [order]";
    }
    
    
    function SQLTemplate($mode){
        $template=parent::SQLTemplate($mode);
        switch($mode){
            case 'insert':
                if (empty($this->$id_field)!==FALSE) return $template." returning ". $this->$id_field ;
        }
        return $template;
    }
    
    function execute()
    {
       if (empty($this->args['fields'])) $this->args['fields']=array('*');
       return parent::execute();
    }
    
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
    

    function calcFoundRows(){
        return $this->foundRows();
    }

    function foundRows()
    {
        $c=clone $this;
        $c->del('limit');
        $c->del('order');
        $c->del('group');
        return $c->fieldQuery('count(*)')->getOne();
    }


      function render_limit(){
	          if($this->args['limit']){
		                  return 'FIRST '.
			                (int)$this->args['limit']['cnt'].
			                ' SKIP '.
			                (int)$this->args['limit']['shift'];
		  }
      }

}
