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

    public $dsql_class='DB_dsql';
    
    /** Backticks will be added around all fields. Set this to '' if you prefer cleaner queries */
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

        return $this->bt.$s.$this->bt;
    }
    
    /** Initialization and connection to the database. Reads config from file. */
    function init(){
        parent::init();

        $cp=$this->di_config['conf_pref']?:'pdo';

        $this->dbh=new PDO($this->api->getConfig($cp.'/dsn'),
                $this->api->getConfig($cp.'/user','root'),
                $this->api->getConfig($cp.'/password','root'),
                $this->api->getConfig($cp.'/options',array(PDO::ATTR_PERSISTENT => true))
                );
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->table_prefix=$this->api->getConfig('pdo/table_prefix','');
        
    }

    /** Returns Dynamic Query object compatible with this database driver (PDO) */
    function dsql($class=null){
        $obj=$this->add($class=$class?:$this->dsql_class);
        if(!$obj instanceof DB_dsql)
            throw $this->exception('Specified class must be descendant of DB_dsql')
            ->addMoreInfo('class',$class);
        return $obj;
    }
    
    /** Query database with SQL statement */
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
    
    /** Executes query and returns first column of first row */
    function getOne($query, $params=array()){
        $res=$this->query($query,$params)->fetch();
        return $res[0];
    }
    
    /** Returns last ID after insert. Driver-dependant. Redefine if needed. */
    function lastID($statement,$table){
        // TODO: add support for postgreSQL and other databases
        return $this->dbh->lastInsertId();
    }
}
