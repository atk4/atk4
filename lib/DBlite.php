<?php
/*
 * This class is lightweight replacement of popular PEAR::DB class. This class
 * Excludes number of useless stuff (in my opinion), which PEAR::DB is rich.
 * It does not enforce you to use particular API (PEAR), but gives you flexible
 * way to integrate into any.
 *
 * However additionally to the base functionality this class provides dynamic
 * query functions. I believe the way how dynamic queries work is much more
 * easier to use in a generic libraries therefore I encourage everyone to use
 * them.
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   LGPL. See http://www.gnu.org/copyleft/lesser.html
 * @version     2.0
 *
 * History
 *  0.9         First version.
 *  1.0         This version was adopted into AModules2 and SLib projects.
 *  1.1         Added support for mysqli, parametric arguments (mvs@adevel.com).
 *  1.2         Several tuneups and changes, used in AModules3.
 *  1.3         Released a standalone version with code cleanup.
 *  2.0         Many significant changes, reorganized structure, might break compatibility with DBlite 1.X
 */

define('DB_FETCHMODE_DEFAULT', null);   // use mode specified at connect. By default it's ORDERED
define('DB_FETCHMODE_ORDERED', 0);      // every row is returned as array of fields
define('DB_FETCHMODE_ASSOC', 1);        // every row is returned as hash of fields
define('DB_FETCHMODE_IDROW', 2);        // first field is id, which is a key in multirow query

if(version_compare(phpversion(),'5.0')<0){
	// PHP4 does not support 2nd argument to class_exist
	if(!class_exists('DBlite_Abstract')){
		class DBlite_Abstract {}            // define this class if you want DBlite to inherit from
	}
}else{
	if(!class_exists('DBlite_Abstract',false)){
		class DBlite_Abstract {}            // define this class if you want DBlite to inherit from
		// your class tree.
	}
}

class DBlite extends DBlite_Abstract {
	var $version = "2.0";

	var $settings;

	var $error_handler = null;

	// dynamic sql
	var $ds;
	var $table;
	var $debug=0;

	var $default_assoc;

	var $calc_found_rows=false;
	var $found_rows=false;        // will be initialized to value of found_rows(), see mysql manual (mysql 4.0+)
	var $echo_errors = true;        // TODO: kill this?
	var $last_query;


	var $owner=null;         // link to your API class. Mainly for fatal()
	var $api=null;			// alias for owner, to be AModules3 consistent
	///////////////////// Core functions //////////////////////
	function &connect($dsn, $default_assoc = DB_FETCHMODE_ORDERED){
		/**
		 * Connect to database through DSN. This method will return either
		 * object or string containing error. Error handling will not be used,
		 * because error_handler can't be initialized at this point. If not successful,
		 * will return string.
		 *
		 * You can either pass string DSN or parametric (array) DSN.
		 */
		$result = DBlite::tryConnect($dsn,$default_assoc,false);
		if(is_string($result)){
			DBlite::fatal("Database connection failed",true);
			exit;
		}
		return $result;
	}
	function &tryConnect($dsn, $default_assoc = DB_FETCHMODE_DEFAULT,$error_reporting=true) {
		/**
		 * Connect to database using DSN. If wasn't successful,
		 * will display error and halt. Use tryConnect if you want to handle error
		 * situations
		 */
		$f="No DSN specified";
		if(!$dsn)return $f;

		if(is_array($dsn)){
			// pre-parsed DSN
			$type=$dsn['type'];
		}else{
			list($type, $rest)=explode('://', $dsn, 2);
		}

		// We are going to include file only if the class does not exist. This way
		// you can include DBlite_* classes at the bottom of main file.
		$class=DBlite::loadDriver($type);

		if(is_object($class)){
			$obj=$class;
		}else{
			$obj=new $class;
		}
		$obj->default_assoc=$default_assoc;
		$result=$obj->realConnect($dsn,$error_reporting);

		if(is_string($result))return $result;
		return $obj;
	}
	function loadDriver($type){
		$class="DBlite_$type";
		if(!class_exists($class)){
			if(
					(!include("DBlite/$type.php")) &&
					(!include(dirname(__FILE__)."/DBlite/$type.php"))
			   ) {
				$this->fatal("Unable to include DBlite/$type.php. This driver is missing. Check your DSN");
			}
		}
		return $class;
	}


	///////////////////// Support functions //////////////////////
	function parseDSN($dsn) {
		/*
		 * This is flexible replacement for parseDSN as seen in PEAR::DB. This
		 * one will only parse out parts which are actually known by plugin.
		 * This way you don't have to make it complex in global class, and can
		 * customize in child classes.
		 *
		 * This function should be used and extended if necessary in child
		 * classes.
		 */

		if(is_array($dsn))return $dsn;  // allow pre-parsed DSNs

		$matches = null;
		preg_match('/^(\w+):\/\/(.*)\/([\w*]+)$/', $dsn, $matches);

		list(, $dsn_a['type'], $dsn_a['body'], $dsn_a['database']) = $matches;

		// Using greedy matching for username and password which can contain
		// delimiters in them

		if ( preg_match('/^([^:]*)?(:[^:]*)?@(\w*\+)?([^:]*)(:\w*)?/', $dsn_a['body'], $matches) ) {
			$matches[] = null;
			$matches[] = null;
			$matches[] = null;
			$matches[] = null;
			@list(, $dsn_a['username'], $dsn_a['password'], $dsn_a['transport'], $dsn_a['hostspec'], $dsn_a['port']) = $matches;
		} else {
			// username was not defined
			preg_match('/^([^:]*)(:\w*)?/', $dsn_a['body'], $matches);
			list(, $dsn_a['hostspec'], $dsn_a['port']) = $matches;
		}

		// cut down junk characters
		if($dsn_a['transport'])$dsn_a['transport']=substr($dsn_a['transport'], 0, -1);   // cut +
		if($dsn_a['password'])$dsn_a['password']=substr($dsn_a['password'], 1);   // cut :
		if($dsn_a['port'])$dsn_a['port']=substr($dsn_a['port'], 1);   // cut :

		return $dsn_a;
	}

	///////////////////// Basic Query Functions //////////////
	function query($q, $param1=null){
		/*
		 * Perform database query. Usually $q is a string, but it can also be presented
		 * in the following froms:
		 *
		 * if $param1 is set, we'll assume you are using psql form, construct
		 *   query and parse it
		 *
		 * additional call froms may be added by drivers
		 */
		if(is_array($param1)){
			// psql mode
			$this->loadDriver('psql');

			$q = DBlite_psql::execute($q,$param1);
		}
		if(is_object($q)){
			$q = $q->execute($q,$param1);
		}
		return $this->realQuery($q);
	}
	function realQuery($q){
		return $this->fatal('Calling "query" on abstract class');
	}
	function fetchRow($handle=null,$fetchmode=null){
		/*
		 * Receive result from sql server and return row
		 */
		if(is_int($handle)){
			// ops, they swaped arguments
			$tmp=$handle;$handle=$fetchmode;$fetchmode=$tmp;
		}
		if($fetchmode & DB_FETCHMODE_ASSOC){
			return $this->fetchHash($handle);
		}else{
			return $this->fetchArray($handle);
		}
	}
	function fetchHash($handle=null){
		return $this->fatal('call to fetchHash on abstract DB object');
	}
	function fetchArray($handle=null){
		return $this->fatal('call to fetchArray on abstract DB object');
	}

	///////////////////// Instast Query Functions ////////////
	function getOne($q,$param1=null){
		/*
		 * Perform query and return first field from first row
		 *
		 * 1st argument is a query (or template if you use psql)
		 * 2nd argument is parameter array (for psql)
		 */

		$tmp_cursor = $this->cursor; // save sursor for fix problem with reseting

		$this->query($q,$param1);
		$result=$this->fetchRow(0);
		if(is_array($result))$result=$result[0];

		$this->cursor = $tmp_cursor;
		return $result;
	}

	function getRow($q,$param1=null,$param2=null){
		/*
		 * Permorm query and return first row only
		 *
		 * 1st argument is a query (or template if you use psql)
		 * 2nd argument is parameter array (for psql) or fetchmode
		 * 3rd argument is fetchmode (if psql is used)
		 *
		 * 2nd ard 3rd can be flipped
		 */
		$tmp_cursor = $this->cursor; // save sursor for fix problem with reseting

		if ((!is_null($param1)) and (!is_array($param1))) {
			$tmp=$param2;$param2=$param1;$param1=$tmp;
		}
		$this->query($q,$param2);
		$res = $this->fetchRow(null,$param2);
		$this->cursor = $tmp_cursor; // restore saved cursor
		return $res;
	}
	function getHash($q,$param1=null){
		/*
		 * Perform query and return 1st row in assoc mode
		 */
		return $this->getRow($q,$param1,DB_FETCHMODE_ASSOC);
	}

	function getAll($q,$param1=null,$param2=null){
		/*
		 * 1st argument is a query (or template if you use psql)
		 * 2nd argument is parameter array (for psql)
		 * 3rd argument is fetchmode (if psql is used)
		 *
		 * 2nd ard 3rd can be flipped
		 */

		$tmp_cursor = $this->cursor; // save sursor for fix problem with reseting
		if(!is_array($param1) && is_array($param2)){
			$tmp=$param2;$param2=$param1;$param1=$tmp;
		}
		elseif (!is_array($param1) and is_null($param2)) {
			$param2 = $param1; $param1 = null;
		}
		$this->query($q,$param1);
		$data = array();
		while($row=$this->fetchRow($param2)){
			if($param2 & DB_FETCHMODE_IDROW){
				$id = array_shift($row);
				if(count($row)==1){
					$data[$id]=array_shift($row);
				}else{
					$data[$id]=$row;
				}
			}else{
				$data[]=$row;
			}
		}
		$this->cursor = $tmp_cursor; // restore saved cursor
		return $data;
	}
	function getAllHash($q,$param1=null){
		/*
		 * Return array of assoc data
		 */
		return $this->getAll($q,$param1,DB_FETCHMODE_ASSOC);
	}
	function getAssoc($q,$param1=null){
		/*
		 * Return array of idkey / assoc data
		 */
		return $this->getAll($q,$param1,DB_FETCHMODE_ASSOC+DB_FETCHMODE_IDROW);
	}

	//////////////////// Transaction support ///////////////////

	/**
	 * Begin a transaction, turning off autocommit
	 */
	public function beginTransaction() {
	}

	/**
	 * Commit the changes
	 */
	public function commit() {
	}

	/**
	 * Rollback the changes
	 */
	public function rollback() {
	}


	///////////////////// Misc functions ///////////////////////
	function fatal($str,$static_call=false){
		throw new SQLException($this->last_query,$str);
	}

	function dsql($class='dsql') {
		/*
		 * This function initiates a DBlite_dsql objects.
		 */
		$class=$this->loadDriver($class);
		$ds = new $class;
		$ds->db = $this;
		return $ds;
	}

	function escape($s){
		/* Default string escape is addslashes.
		 * Drivers with their own string escape function should override this function.
		 */
		return addslashes($s);
	}
}
