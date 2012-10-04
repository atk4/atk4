<?php
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
