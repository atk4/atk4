<?php
/**
 * This class implements mysql cluster compatibility for DBlite layer.
 *
 * @author		daru <daru@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
define('STATUS','./mysql_cluster_status');
define('CONFIG','./mysql_cluster_config');
define('SPOOLDIR','./');

if (!defined('DEBUG'))
 {
 define('DEBUG',false);
 }

class DBlite_cluster extends DBlite
 {
 var $cursor;
 var $mhandle; // master handle
 var $shandle; // slave handle
 var $last_query;
 var $master = '';
 var $slaves = array();
 var $status;
 var $userstatus = array(array(),array()); // 0 stands for status1; 1 - for status2

function real_connect($dsn)
 {
// Read status file
 $fp = @fopen(STATUS,'r');
 if ($fp === false)
  {
  DEBUG and print "Failed to open status file\n";
  return false;
  }

 $this->status = (int) @fgetc($fp); // empty file or gibberish results in OK status
 @fclose($fp);
 if ($this->status == 2)
  {
  DEBUG and print "Received status: 2\n";
  return false;
  }
 else if ($this->status > 2)
  {
  DEBUG and print "Received unknown status: $this->status\n";
  return false;
  }

 DEBUG and print "Global status is set to $this->status\n";

// no matter what the status is (0 or 1), read and parse the config
 $Config = @file(CONFIG);
 if ($Config === false)
  {
  DEBUG and print "Failed to open config file\n";
  return false;
  }

 while (list($k,$v) = each($Config))
  {
  if (($v{0} != '#') and ($s = trim($v))) // not a comment; not a spaced line
   {
   list($key,$val) = split('=',$s);
   $key = trim($key);
   $val = trim($val);

   switch ($key)
	{
	case 'master' : $this->master = $val; break;
	case 'slave'  : $this->slaves[] = $val; break;
	case 'status1': if ($val)
					 {
					 DEBUG and print "User $val will get status 1\n";
					 $this->userstatus[0][] = $val;
					 }
					else
					 {
					 DEBUG and print "Status 1 is set for all users\n";
					 $this->status = 1;
					 }
					break;
	case 'status2': if ($val)
					 {
					 DEBUG and print "User $val will get status 2\n";
					 $this->userstatus[1][] = $val;
					 }
					else
					 {
					 DEBUG and print "Status 2 is set for all users\n";
					 return false;
					 }
					break;
	default       : DEBUG and print "Unknown setting $key was ignored\n";
	}
   }
  }
// now let's check what we've got
 if ($this->master == '')
  {
  DEBUG and print "Master was not set\n";
  return false;
  }

 if (sizeof($this->slaves) == 0)
  {
  DEBUG and print "Slaves not found, setting to master\n";
  $this->slaves[] = $this->master;
  }

// now we can really connect!
// analyze settings
 $this->settings=$this->parseDSN($dsn);
// print_r($this->settings);

// and connect
 if ($this->status == 0) // can connect to master
  {
  $this->mhandle = @mysql_pconnect($this->master,$this->settings['username'],$this->settings['password']);
  if (!$this->mhandle)
   {
   DEBUG and print "Connection to master failed\n";
   return mysql_error();
   }

  if (!@mysql_select_db($this->settings['database'],$this->mhandle))
   {
   DEBUG and print "Failed to select database at master\n";
   return mysql_error();
   }
  }

// continue with connect to slave
 srand((float) microtime() * 10000000);
 $slavehost = $this->slaves[array_rand($this->slaves)];

 $this->shandle = @mysql_pconnect($slavehost,$this->settings['username'],$this->settings['password']);
 if (!$this->shandle)
  {
  DEBUG and print "Connection to slave failed\n";
  return mysql_error();
  }

 if (!@mysql_select_db($this->settings['database'],$this->shandle))
  {
  DEBUG and print "Failed to select database at slave\n";
  return mysql_error();
  }

 return true;
 } // real_connect

function parseParamType($type, $name, $value)
 {
 switch ($type)
  {
  case 'date': if ((!is_null($value)) and ($value==''))
				{
				return $this->error('Value not set for DATE parameter "'.$name.'"', $this->last_query);
				}

			   if (is_numeric($value))
				{
				$value = date('Y-m-d H:i:s',$value);
				}
			   return (is_null($value)) ? 'null' : "'".$value."'";
	  default: return parent::parseParamType($type, $name, $value);
  }
 } // parseParamType

function query($query)
 {
 $this->last_query = array('caller'=>$this->getCaller());

 if (!$_query = $this->parseParamQuery($query))
  {
  return false;
  }

 $this->last_query['query_str']=$_query;

 if ($this->cursor) @mysql_free_result($this->cursor);

 if (preg_match('|^\s*select|i',$_query))
  { // select - go to slave
  if (!($this->cursor = mysql_query($_query,$this->shandle)))
   {
   DEBUG and print "SELECT query failed";
   return $this->query_error("Could not execute query",$this->shandle);
   }

  if ($this->calc_found_rows)
   {
   $this->calc_found_rows = false;
   $tmp_cursor = mysql_query("select found_rows()",$this->shandle);
   $tmp_row = mysql_fetch_row($tmp_cursor);
   mysql_free_result($tmp_cursor);
   $this->found_rows=$tmp_row[0];
   }
  }
 else // not a select - run on master
  {
  if ($this->status == 1)
   {
   $fp = @fopen(SPOOLDIR.$this->settings['database'],'a');

   if ($fp === false)
	{
	DEBUG and print "Error opening spool file ".SPOOLDIR.$this->settings['database']."\n";
	return $this->error("Could not open spool file",$this->last_query);
	}

   $_query = str_replace("\n",' ',$_query)."\n"; // one query per line
   @fwrite($fp,$_query);
   @fclose($fp);
   }
  else // status should be 0 here
   {
   if (!($this->cursor = mysql_query($_query,$this->mhandle)))
	{
	DEBUG and print "Master query failed";
	return $this->query_error("Could not execute query",$this->mhandle);
	}
   }
  }
 return $this;
 } // query

function fetchRow()
 {
 if (!$row = mysql_fetch_row($this->cursor)) // slave only
  {
  DEBUG and print "Could not fetch row\n";
  return false;
  }
 return $row;
 }

function fetchHash()
 {
 if (!$row = mysql_fetch_array($this->cursor,MYSQL_ASSOC))
  {
  DEBUG and print "Could not fetch array\n";
  return false;
  }
 return $row;
 }

function numRows()
 {
// Rows after select statement
 return mysql_num_rows($this->cursor);
 }

function numCols()
 {
// Columns after select statement
 return mysql_num_fields($this->cursor);
 }

function affectedRows()
 {
// Rows affected by update/delete
 if ($this->status == 0)
  {
  return mysql_affected_rows($this->mhandle);
  }
 else
  {
  return -1; // what else can we return? Theoretically, we could run a select with the WHERE part but who cares?
  }
 }

function lastID()
 {
// Cluster users are not supposed to use it
 if ($this->status == 0)
  {
  return mysql_insert_id($this->mhandle);
  }
 else
  {
  return -1; // some users will be surprised! ;) We better document this
  }
 }

function query_error($str,$handle)
 {
 $this->last_query['error'] = mysql_error($handle);
 $this->error($str,$this->last_query);
 }
 } //class
?>
