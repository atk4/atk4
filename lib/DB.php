<?php // vim:ts=4:sw=4:et:fdm=marker
/**
   This file is part of Agile Toolkit 4 http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
*/
/**
 * Implementation of SQL Query Abstraction Layer for Agile Toolkit
 *
 * @link http://agiletoolkit.org/doc/dsql
 */
class DB extends AbstractController
{

    /** Link to PDO database handle */
    public $dbh=null;

    /** Contains name of the PDO driver used such as "mysql", "sqlite" etc */
    public $type=null;

    /** This is initialized to a class name which will be returned by dsql()
     * by default. If you want to specify a different one, add this parameter
     * to your PDO:  'class'=>'DB_dsql_my'.
     */
    public $dsql_class=null;

    // {{{ Connectivity

    /**
     * Connect will parse supplied DSN and connect to the database. Please be
     * aware that DSN may contain plaintext password and if you record backtrace
     * it may expose it. To avoid, put your DSN inside a config file under a
     * custom key and use a string e'g:
     *
     * $config['my_dsn']='secret';
     * $db->connect('my_dsn');
     *
     * If the argument $dsn is not specified, it will read it from $config['dsn'].
     *
     * @param string|array $dsn Connection string in PEAR::DB or PDO format
     *
     * @return DB this
     */
    function connect($dsn = null)
    {
        if ($dsn === null) {
            $dsn='dsn';
        }
        if (is_string($dsn)) {
            $new_dsn=$this->api->getConfig($dsn, 'no_config');
            if ($new_dsn != 'no_config') {
                $dsn=$new_dsn;
            }
            if ($dsn=='dsn') {
                $this->api->getConfig($dsn); // throws exception
            }
            if (is_string($dsn)) {
                // Backward-compatible DSN parsing
                preg_match(
                    '|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)'.
                    '(/([0-9a-zA-Z_/\.]*))|',
                    $dsn,
                    $matches
                );

                // Common cause of problem is lack of MySQL module for DSN, but
                // what if we don't want to use MYSQL?
                if (!defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                    throw $this->exception('PDO_MYSQL unavailable');
                }

                // Reconstruct PDO DSN out of old-style DSN
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
                @$dsn[0],
                @$dsn[1],
                @$dsn[2],
                @$dsn[3]?:array(PDO::ATTR_PERSISTENT => true));
        } catch (PDOException $e) {
            throw $this->exception('Database Connection Failed')
                ->addMoreInfo('PDO error', $e->getMessage())
                ->addMoreInfo('DSN', $dsn[0]);
            ;
        }

        // Configure PDO to play nice
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Use global table prefixes
        $this->table_prefix=$dsn['table_prefix'];

        // Based on type, switch dsql class
        list($this->type, $junk) = explode(':', $dsn[0]);
        $this->type=strtolower($this->type);
        $this->dsql_class=isset($dsn['class'])
            ?$dsn['class']
            :'DB_dsql_'.$this->type;
        return $this;
    }
    // }}}

    // {{{ Itegration with Dynamic SQL
    /**
     * Returns Dynamic Query object which would be compatible with current
     * database connection. If you are connected to MySQL, DB_dsql_mysql will
     * be returned.
     *
     * @param sting $class Override class (e.g. DB_dsql_my)
     *
     * @return DB_dsql empty dsql object
     * */
    function dsql($class = null)
    {
        $class=$class?:$this->dsql_class;
        $obj = $this->add($class);
        if (!$obj instanceof DB_dsql) {
            throw $this->exception('Specified class must be descendant of DB_dsql')
                ->addMoreInfo('class', $class);
        }
        return $obj;
    }
    // }}}

    // {{{ Query execution and data fetching 
    /**
     * Sometimes for whatever reason DSQL is not what you want to do. I really
     * don't understand your circumstances in which you would want to use
     * query() directly but if you really really know what you are doing, then
     * this method executes query with specified params
     *
     * @param string $query  SQL query
     * @param array  $params Parametric arguments
     *
     * @return PDOStatement Newly created statement
     */
    function query($query, $params = array())
    {
        // If user forgot to explicitly connect to database, let's do it for him
        if (!$this->dbh) {
            $this->connect();
        }

        // There are all sorts of objects used by Agile Toolkit, let's make
        // sure we operate with strings here
        if (is_object($query)) {
            $query=(string)$query;
        }

        // For some weird reason SQLite will fail sometimes to execute query
        // for the first time. We will try it again before complaining about
        // the error, but only if error=17.
        $e=null;
        for ($i=0; $i<2; $i++) {
            try {
                $statement = $this->dbh->prepare($query);

                foreach ($params as $key => $val) {
                    if (!$statement->bindValue(
                        $key,
                        $val,
                        is_int($val)?(PDO::PARAM_INT):(PDO::PARAM_STR)
                    )) {
                        throw $this->exception('Could not bind parameter')
                            ->addMoreInfo('key', $key)
                            ->addMoreInfo('val', $val)
                            ->addMoreInfo('query', $query);
                    }
                }

                $statement->execute();
                return $statement;
            } catch (PDOException $e) {
                if ($e->errorInfo[1] === 17 && !$i) {
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Executes query and returns first column of first row. This is a
     * quick and speedy way to get the results of simple queries. Consider
     * using DSQL:
     *
     * echo $this->api->db->dsql()->expr('select now()');
     *
     * @param string $query  SQL Qurey
     * @param arary  $params PDO-params
     *
     * @return string first row, first column
     */
    function getOne($query, $params = array())
    {
        $res=$this->query($query, $params)->fetch();
        return $res[0];
    }

    /**
     * Returns last ID after insert. Driver-dependant. Redefine if needed, 
     * but avoid using.
     *
     * Agile Toolkit models automatically reload themselves with the
     * newly inserted id:
     *
     *     $m = $this->add('Model_Book');
     *     $m->set('title','The Book of Oz');
     *     $m->save();
     *     $id = $m->id;  // Last ID automatically stored here
     *
     * TODO: test with postgreSQL and other databases. I've heard pgsql
     * does not support autoincrements, but we can pull some sequences.
     * Yak, but we would need to know table name and some more info about
     * the statement, right?
     *
     * @param string $statement for unsupported db's
     * @param string $table     for unsupported db's
     *
     * @return int
     */
    function lastID($statement = null, $table = null)
    {
        return $this->dbh->lastInsertId();
    }

    public $transaction_depth=0;
    /**
     * Database driver supports statements for starting and committing transactions.
     * Unfortunatelly they don't allow to nest them and commit gradually. With
     * this method you have some implementation of nested transactions.
     *
     * When you call it for the first time it will begin transaction. If you call
     * it more times, it will do nothing but will increase depth counter. You will
     * need to call commit() for each execution of beginTransactions() and the last
     * commit will actually perform a real commit. 
     *
     * So if you have been working with the database and got unhandled exception
     * in the middle of your code, everything would be rolled back.
     *
     * @return mixed Don't rely on any meaningful return
     */
    public function beginTransaction()
    {
        $this->transaction_depth++;
        // transaction starts only if it was not started before
        if ($this->transaction_depth==1) {
            return $this->dbh->beginTransaction();
        }
        return false;
    }
    /**
     * Each occurance of beginTransaction() must be matched with commit(). Only
     * when same amount of commits are executed, the ACTUAL commit will be issued
     * to the database.
     *
     * @see beginTransaction()
     * @return mixed Don't rely on any meaningful return
     */
    public function commit()
    {
        $this->transaction_depth--;

        // This means we rolled something back and now we lost track of commits
        if ($this->transaction_depth<0) {
            $this->transaction_depth=0;
        }

        if ($this->transaction_depth==0) {
            return $this->dbh->commit();
        }
        return false;
    }

    /**
     * Will return true if currently running inside a transaction. This is
     * useful if you are logging anything into a database. If you are inside
     * a transaction, don't log or it may be rolled back. Perhaps use a hook?
     *
     * @return boolean if in transactionn
     */
    public function inTransaction()
    {
        return $this->transaction_depth>0;
    }

    /**
     * Rollbacks queries since beginTransaction and resets transaction depth
     *
     * @see beginTransaction()
     * @return mixed Don't rely on any meaningful return
     */
    public function rollBack()
    {
        $this->transaction_depth=0;
        return $this->dbh->rollBack();
    }
    // }}}
}
