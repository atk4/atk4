<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Implementation of PDO support in Agile Toolkit
 * 
 * Use: 
 *  $this->api->dbConnect();
 *  $query = $this->api->db->dsql();
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class DB extends AbstractController {

    /** Link to PDO database handle */
    public $dbh=null;

    /** when queries are executed, their statements are cached for later re-use */
    public $query_cache=array();

    /** Contains name of the PDO driver used such as "mysql", "sqlite" etc */
    public $type=null;

    public $dsql_class='DB_dsql';
    
    /** we need to move dsn parsing to init, as other wise db->dsql() is 
     * creating new object of class DB_dsql instead of DB_dsql_$driver, as 
     * $this->dsql_class was set in connect, which is called after db->dsql()
     * */
    /** connection to the database. Reads config from file. $dsn may be array */
    function connect($dsn='pdo'){

        if(is_string($dsn)){
            $new_dsn=$this->api->getConfig($dsn, 'no_config');
            if($new_dsn!='no_config')$dsn=$new_dsn;
            if(is_string($dsn)){
                // possibly old DSN, let's parse. PS: this is for compatibility only, so
                // don't be too worried about properly parsing it
                preg_match('|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)(/([a-zA-Z_/\.]*))|',$dsn,$matches);


                $dsn=array(
                    $matches[1].':host='.$matches[5].';dbname='.$matches[7],
                    $matches[2],
                    $matches[4]
                );
            }
        }

        $this->dbh=new PDO(
            @$dsn[0],    // DSN
            @$dsn[1],
            @$dsn[2],
            @$dsn[3]?:array(PDO::ATTR_PERSISTENT => true));

        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->table_prefix=$dsn['table_prefix'];

        // Based on type, switch dsql class
        list($this->type,$junk)=explode(':',$dsn[0]);
        $this->type=strtolower($this->type);
        $this->dsql_class=isset($dsn['class'])?$dsn['class']:'DB_dsql_'.$this->type;
        return $this;
    }

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
    
    /** Query database with SQL statement */
    function query($query, $params=array()){
        if(!$this->dbh)$this->connect();

        if(is_object($query))$query=(string)$query;

        // Look in query cache
        if(isset($this->query_cache[$query])){
            $statement = $this->query_cache[$query];
        }else{
            $statement = $this->dbh->prepare($query);
            $this->query_cache[$query]=$statement;
        }
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
}
