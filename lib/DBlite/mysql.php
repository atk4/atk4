<?
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
        return true;
    }
    function useDB($db){
        if(!mysql_select_db($db,$this->handle))
            return('Could not switch db to '.$db);
    }
    function realQuery($query, $params=null, $allow_extra=null){
    	//TODO Need think about disadvantages with save/restore cursor if delete/update queries
    	if (
    	      (substr_compare('update',$query,0,6,true)==0) 
    	         or 
    	      (substr_compare('delete',$query,0,6,true)==0)   
    	    ) {
    	    	$update_query = true;
    	    	$tmp_cursor_upd = $this->cursor;
    	}
    	else
    		$update_query = false;
    		 
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
            echo caller_lookup(3); // TODO! What is? Debug code???
        }
        
        // restore cursor for updates queries
        if ($update_query == true) $this->cursor = $tmp_cursor_upd;
        
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
        return mysql_affected_rows($this->cursor);
    }
    function lastID(){
        /**
         * Rows after select statement
         */
        return mysql_insert_id($this->handle);
    }
    /**
     * parse datetime in mysql string format, return array
     */
    private function datetime($dts) {
        return array(
                      'DAY'=>substr($dts, 8,2),
                      'MONTH'=>substr($dts,5,2),
                      'YEAR'=> substr($dts,0,4),
                      'HOUR'=>substr($dts,11,2),
                      'MIN'=>substr($dts,14,2),
                      'SEC'=>substr($dts,17,2)
                     );
    }

    /**
     * convert datetime in mysql string format, return timestamp 
     */
    function dts2tms($dts) {
    	// 2004-10-23 10:59:16
        $dtime = $this->datetime($dts);

        return mktime($dtime['HOUR'], $dtime['MIN'], $dtime['SEC'],
                      $dtime['MONTH'], $dtime['DAY'], $dtime['YEAR']);
    }     
}
