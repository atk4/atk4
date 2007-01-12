<?php
/**
 * This class implements mysql compatibility for DBlite layer.
 *
 * @author      Romans <romans@adevel.com>
 * @copyright   See file COPYING
 * @version     DBlite/2.0, driver revision 1
 */
class DBlite_mysql extends DBlite {
    /**
     * Value of current query
     */
    var $cursor;
    var $handle;
    /**
     * Copy of last executed query
     */

    function realConnect($dsn){
        // First analyze settings

        $this->settings=$this->parseDSN($dsn);

        // Then let's do real connect
        $this->handle=mysql_connect(
                                     $this->settings['hostspec'],
                                     $this->settings['username'],
                                     $this->settings['password']
                                    );
        if(!$this->handle)return('Could not connect to mysql');
        if(!mysql_select_db($this->settings['database'],$this->handle))
            return('Could not select db');
        mysql_query("/*!40101 SET NAMES utf8 */",$this->handle);
        return true;
    }
    function useDB($db){
        if(!mysql_select_db($db,$this->handle))
            return('Could not switch db to '.$db);
    }
    function realQuery($query, $params=null, $allow_extra=null){
        $this->last_query=$query;
        if(!$this->cursor = mysql_query($query,$this->handle)){
            $this->fatal('Could not execute query: '."\n".$query."\n");
        }
        if($this->calc_found_rows){
            $this->calc_found_rows=false;
            $tmp_cursor = @mysql_query("select found_rows()",$this->handle);
            $tmp_row = @mysql_fetch_row($tmp_cursor);
            @mysql_free_result($tmp_cursor);
            $this->found_rows=$tmp_row[0];
        }
        
        return $this;
    }
    function fetchArray(){
        if((!is_resource($this->cursor)) or (!$row = mysql_fetch_row($this->cursor))){
            return array();
        }
        return $row;
    }
    function fetchHash(){
    	if (!is_resource($this->cursor)) return array();
        if(!$row = mysql_fetch_array($this->cursor,MYSQL_ASSOC)){
        	if (mysql_error($this->handle)) 
            	return $this->fatal("Unable to fetch row");
            else 
            	return array();
            
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
        return mysql_affected_rows($this->handle);
    }
    function lastID(){
        /**
         * Rows after select statement
         */
        return mysql_insert_id($this->handle);
    }

    /**
     * <b>[DEPRECATED]</b> please use strtotime() analogue.
     * convert datetime in mysql string format, return timestamp
     */
    function dts2tms($dts) {
        return strtotime($dts);
    }     

	function dump($tables=null){
		/**
		 * Dumps db to a fileto a file
		 * @param array $tables
		 */
		//getting all the tables
		if(!$tables){
			$rs = mysql_list_tables($this->dbname);
			while ($row = mysql_fetch_array($rs)){
				$tables[] = $row[0];
			}
		} 
		//saving records from tables
		$script = "";
		foreach($tables as $table_name){
			$rs = $this->query("SELECT * FROM $table_name");
			while($row = mysql_fetch_assoc($rs)){
				$fields = "";
				$values = "";
				foreach($row as $field => $value){
					if ($fields != ""){
						$fields .= ', ';
						$values .= ', ';
					}
					$fields .= $field;
					$values .= "'".$this->normalize($value)."'";
				}
				$script .= "INSERT INTO $table_name ($fields) VALUES ($values);\n";
			}
		}
		//saving script
		$file_name = date("Y_m_d_H_i") . ".sql";
		_file_put_contents ($file_name, $script);
	}

}
