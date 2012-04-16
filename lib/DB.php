<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Implementation of PDO support in Agile Toolkit
 * 
 * Use: 
 *  $this->api->dbConnect();
 *  $query = $this->api->db->dsql();
 *
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2012 Romans Malinovskis <romans@agiletoolkit.org>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class DB extends AbstractController {

    /** Link to PDO database handle */
    public $dbh=null;

    /** Contains name of the PDO driver used such as "mysql", "sqlite" etc */
    public $type=null;

    public $dsql_class='DB_dsql';
    

    // {{{ Connectivity

    /** we need to move dsn parsing to init, as other wise db->dsql() is 
     * creating new object of class DB_dsql instead of DB_dsql_$driver, as 
     * $this->dsql_class was set in connect, which is called after db->dsql()
     * */
    /** connection to the database. Reads config from file. $dsn may be array */
    function connect($dsn=null){
        if(is_null($dsn))$dsn='dsn';

        if(is_string($dsn)){
            $new_dsn=$this->api->getConfig($dsn, 'no_config');
            if($new_dsn!='no_config')$dsn=$new_dsn;
            if($dsn=='dsn')$this->api->getConfig($dsn); // throws exception
            if(is_string($dsn)){
                // possibly old DSN, let's parse. PS: this is for compatibility only, so
                // don't be too worried about properly parsing it
                preg_match('|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)(/([0-9a-zA-Z_/\.]*))|',$dsn,$matches);


                $dsn=array(
                    $matches[1].':host='.$matches[5].';dbname='.$matches[7].
                    ';charset=utf8',
                    $matches[2],
                    $matches[4],
                    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
                );
            }
        }

        try {
            $this->dbh=new PDO(
                @$dsn[0],    // DSN
                @$dsn[1],
                @$dsn[2],
                @$dsn[3]?:array(PDO::ATTR_PERSISTENT => true));
        }catch(PDOException $e){
            throw $this->exception('Database Connection Failed')
                ->addMoreInfo('PDO error',$e->getMessage())
                ->addMoreInfo('DSN',$dsn[0]);
            ;
        }

        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->table_prefix=$dsn['table_prefix'];

        // Based on type, switch dsql class
        list($this->type,$junk)=explode(':',$dsn[0]);
        $this->type=strtolower($this->type);
        $this->dsql_class=isset($dsn['class'])?$dsn['class']:'DB_dsql_'.$this->type;
        return $this;
    }
    // }}} 

    // {{{ Itegration with Dynamic SQL
    /** Returns Dynamic Query object compatible with this database driver (PDO) */
    function dsql($class=null){
        $obj=$this->add($class=$class?:$this->dsql_class);
        if(!$obj instanceof DB_dsql)
            throw $this->exception('Specified class must be descendant of DB_dsql')
            ->addMoreInfo('class',$class);
        // Don't keep reference to the object for garbage collector
        unset($this->elements[$obj->short_name]);
        return $obj;
    }
    // }}}

    // {{{ Query execution and data fetching 
    /** Query database with SQL statement */
    function query($query, $params=array()){
        if(!$this->dbh)$this->connect();

        if(is_object($query))$query=(string)$query;

        $statement = $this->dbh->prepare($query);

        foreach($params as $key=>$val){
            if (!$statement->bindValue($key,$val,is_int($val)?(PDO::PARAM_INT):(PDO::PARAM_STR))){
                throw $this->exception('Could not bind parameter ' . $key)
                    ->addMoreInfo('val',$val)
                    ->addMoreInfo('query',$query);
            }
        }
        $statement->execute();
        return $statement;
    }

    /** Executes query and returns first column of first row */
    function getOne($query, $params=array()){
        $res=$this->query($query,$params)->fetch();
        return $res[0];
    }

    /** Returns last ID after insert. Driver-dependant. Redefine if needed. */
    function lastID($statement=null,$table=null){
        // TODO: add support for postgreSQL and other databases
        return $this->dbh->lastInsertId();
    }

    public $transaction_depth=0;
	public function beginTransaction() {
		$this->transaction_depth++;
		// transaction starts only if it was not started before
		if($this->transaction_depth==1)return $this->dbh->beginTransaction();
		return false;
	}
	public function commit() {
		$this->transaction_depth--;
		if($this->transaction_depth==0)return $this->dbh->commit();
		return false;
	}
	public function inTransaction(){
		return $this->transaction_depth>0;
	}
	public function rollBack($option=null) {
		$this->transaction_depth=0;
		return $this->dbh->rollBack();
	}
    // }}}
}
