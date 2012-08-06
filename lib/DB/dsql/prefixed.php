<?php
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
