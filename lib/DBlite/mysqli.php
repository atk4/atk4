<?php
/**
 * This class implements improved mysql compatibility for DBlite layer.
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class DBlite_mysqli extends DBlite {
	/**
	 * Value of current query
	 */
	var $cursor;
	var $mysqli;

	// TODO - rewrite this class to work with mysqli


	function real_connect($dsn){
		// First analyze settings
		$this->settings=$this->parseDSN($dsn);

		// Then let's do real connect
		$this->handle=mysql_pconnect(
									 $this->settings['hostspec'],
									 $this->settings['username'],
									 $this->settings['password']
									);
		mysql_select_db($this->settings['database'],$this->handle);
	}
	function query($query){
		$this->settings['last_query']=$query;
		if(!$this->cursor = mysql_query($query,$this->handle)){
			return $this->error("Could not execute query");
		}
		return $this;
	}
	function fetchRow(){
		if(!$row =  mysql_fetch_row($this->cursor)){
			return $this->error("Unable to fetch row");
		}
		return $row;
	}
	function fetchHash(){
		if(!$row =  mysql_fetch_array($this->cursor,MYSQL_ASSOC)){
			return $this->error("Unable to fetch row");
		}
		return $row;
	}
	function numRows(){
		/**
		 * Rows after select statement
		 */
		return mysql_num_rows($this->cursor);
	}
	function numCols(){
		/**
		 * Columns after select statement
		 */
		return mysql_num_fields($this->cursor);
	}
	function affectedRows(){
		/**
		 * Rows affected by update/delete
		 */
		return mysql_affected_rows($this->cursor);
	}
	function lastID(){
		/**
		 * Rows after select statement
		 */
		return mysql_insert_id($this->handle);
	}
}
