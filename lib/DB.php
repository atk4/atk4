<?php
/***********************************************************
  Implementation of PDO support in Agile Toolkit

  Reference:
  http://agiletoolkit.org/doc/pdo

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
class DB extends AbstractController {

    public $dbh=null;

    public $table_prefix=null;

    /* when queries are executed, their statements are cached for later re-use */
    public $query_cache=array();

    /* Backticks will be added around all fields. Set this to '' if you prefer cleaner queries */
    public $bt='`';
    function bt($s){
        if(is_array($s)){
            $out=array();
            foreach($s as $ss){
                $out[]=$this->bt($ss);
            }
            return $out;
        }

        if(!$this->bt
        || is_object($s)
        || $s=='*'
        || strpos($s,'(')!==false
        || strpos($s,$this->bt)!==false
        )return $s;

        if(strpos($s,'.')!==false){
            $s=explode('.',$s);
            return implode('.',$this->bt($s));
        }
        return $this->bt.$s.$this->bt;
    }
    /* Initialization and connection to the database. Reads config from file. */
    function init(){
        parent::init();

        $this->dbh=new PDO($this->api->getConfig('pdo/dsn'),
                $this->api->getConfig('pdo/user','root'),
                $this->api->getConfig('pdo/password','root'),
                $this->api->getConfig('pdo/options',array(PDO::ATTR_PERSISTENT => true))
                );
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->table_prefix=$this->api->getConfig('pdo/table_prefix','');
        
    }
    /* Returns Dynamic Query object compatible with this database driver (PDO) */
    function dsql(){
        return $this->add('DB_dsql');
    }
    /* Query database with SQL statement */
    function query($query, $params=array()){

        if(is_object($query))$query=(string)$query;

        // Look in query cache
        if(isset($this->query_cache[$query])){
            $statement = $this->query_cache[$query];
        }else{
            $statement = $this->dbh->prepare($query);
            $this->query_cache[$query]=$statement;
        }
        $statement -> execute($params);
        return $statement;
    }
    function getOne($query, $params=array()){
        $res=$this->query($query,$params)->fetch();
        return $res[0];
    }
    /* Returns last ID after insert. Driver-dependant. Redefine if needed. */
    function lastID($statement,$table){
        // TODO: add support for postgreSQL and other databases
        return $this->dbh->lastInsertId();
    }
}
