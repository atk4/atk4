<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
class DBlite_debug extends DBlite_mysql {
	private $_tracing = false;  // trace all SQL queries
	private $_tracing_file_handle;


	function start_tracing($filename = null) {
		if (is_null($filename))
			$filename = dirname(__FILE__).'/trace.log';
		$this->_tracing_file_handle = fopen($filename,'a');
		if ($this->_tracing_file_handle) {
			$this->_tracing = true;
		}
		else {
			if ($this->echo_errors)
				echo '<b>Cannot open file "'.$filename.'" for write!</b><br />';
		}
	}
	function stop_tracing() {
		if ($this->_tracing and $this->_tracing_file_handle)
			fclose($this->_tracing_file_handle);
		$this->_tracing = false;
	}

	function datetime($dts) {
		return array(
					  'DAY'=>substr($dts, 8,2),
					  'MONTH'=>substr($dts,5,2),
					  'YEAR'=> substr($dts,0,4),
					  'HOUR'=>substr($dts,11,2),
					  'MIN'=>substr($dts,14,2),
					  'SEC'=>substr($dts,17,2)
					 );
	}
	// 2004-10-23 10:59:16
	function dts2tms($dts) {
		$dtime = $this->datetime($dts);
		return mktime($dtime['HOUR'], $dtime['MIN'], $dtime['SEC'],
					  $dtime['MONTH'], $dtime['DAY'], $dtime['YEAR']);
	}

	function _microtime_float($mctime) {
		list($usec, $sec) = explode(" ", $mctime);
		return ((float)$usec + (float)$sec);
	}
	function _trace_query($query,$start_time, $finish_time) {
		if ($this->_tracing and $this->_tracing_file_handle) {
			@fwrite($this->_tracing_file_handle, ' ******** '. date('d.m.Y H:i:s') . " ********\n".
				 "\n". $this->_format_query($query) . "\n" .
				 "\n".$this->_explain_query($query)."\n".
				 'Open cursor time: '.round((($this->_microtime_float($finish_time)-$this->_microtime_float($start_time))*1000)).' ms'.
				 "\n\n");
		}
	}
	function _format_query($query) {
		if ((strpos($query,"\n")===false) and (strlen($query)>80)) {
			$res = str_replace(array(' FROM ',
									 ' from ',
									 ' inner join ',
									 ' INNER JOIN ',
									 ' left outer join ',
									 ' LEFT OUTER JOIN ',
									 ' LEFT JOIN ',
									 ' left join ',
									 ' JOIN ',
									 ' join ',
									 ' where ',
									 ' WHERE ',
									 ' and ',
									 ' AND ',
									 ' or ',
									 ' OR ',
									 ' GROUP BY ',
									 ' group by ',
									 ' ORDER BY ',
									 ' order by ',
									 ' HAVING ',
									 ' having ',
									 ' LIMIT ',
									 ' limit ',
									 'select ',
									 'SELECT '),
							   array("\n;;;;;;;;;;;FROM;",
									 "\n;;;;;;;;;;;from;",
									 "\n;;;;;inner;join;",
									 "\n;;;;;INNER;JOIN;",
									 "\nleft;outer;join;",
									 "\nLEFT;OUTER;JOIN;",
									 "\n;;;;;;LEFT;JOIN;",
									 "\n;;;;;;left;join;",
									 "\n;;;;;;;;;;;JOIN;",
									 "\n;;;;;;;;;;;join;",
									 "\n;;;;;;;;;;where;",
									 "\n;;;;;;;;;;WHERE;",
									 "\n;;;;;;;;;;;;and;",
									 "\n;;;;;;;;;;;;AND;",
									 "\n;;;;;;;;;;;;;or;",
									 "\n;;;;;;;;;;;;;OR;",
									 "\n;;;;;;;GROUP;BY;",
									 "\n;;;;;;;group;by;",
									 "\n;;;;;;;ORDER;BY;",
									 "\n;;;;;;;order;by;",
									 "\n;;;;;;;;;HAVING;",
									 "\n;;;;;;;;;having;",
									 "\n;;;;;;;;;;LIMIT;",
									 "\n;;;;;;;;;;limit;",
									 ";;;;;;;;;select;",
									 ";;;;;;;;;SELECT;",
									 ),
							   $query);
			$res = str_replace(';',' ',$res);
			$lines = explode("\n",$res);
			if ((strpos(strtoupper($lines[0]),'SELECT')!==false) and (strlen($lines[0])>80)) {
				$tmp_str = $lines[0];
				//$init_len = strlen($tmp_str); $new_len = 0;
				$new_str = str_replace('   ','  ',$tmp_str);
				while ($new_str != $tmp_str) {
				  $tmp_str = $new_str;
				  $new_str = str_replace('   ','  ',$tmp_str);
				}

				$fields = explode("\n",str_replace('  ',"\n                ",$new_str));
				$fields[1] = str_replace("                ",'         ',$fields[1]);
				unset($fields[0]);
				if (strlen(trim($fields[count($fields)]))==0) unset($fields[count($fields)]);
				$lines[0] = implode("\n",$fields);
				$res = implode("\n",$lines);
			}
		} else
			$res = $query;
		return $res;
	}
	function _explain_query($query) {
		if ($cursor = mysql_query('explain '.$query,$this->handle)) {
			if (mysql_num_rows($cursor) == 0) {
				return 'explain query not return rows!';
			}

			$rows = array();
			$max_lens = array();
			while ($row = mysql_fetch_assoc($cursor)) {
				$rows[] = $row;
				if (empty($max_lens)) {
					foreach ($row as $name=>$value) {
						$max_lens[$name] = strlen($name);
						$col_names[$name] = $name;
					}
				}
				foreach ($row as $name=>$value) {
					$val = (isset($max_lens[$name])?$max_lens[$name]:0);
					if ($val < strlen($value))
						$max_lens[$name] = strlen($value);
				}
			}
			mysql_free_result($cursor);
			$res  = '';
			if (!empty($rows)) {
				$res .= $this->_plan_line(null,$max_lens);
				$res .= $this->_plan_line($col_names,$max_lens);
				$res .= $this->_plan_line(null,$max_lens);
				foreach ($rows as $row) {
					$res .= $this->_plan_line($row,$max_lens);
				}
				$res .= $this->_plan_line(null,$max_lens);
			}
			return $res;
		}
		else {
			return 'Error explain query: '.mysql_error($this->handle);
		}
	}
	function _plan_line($text_arr,$max_lens) {
		$res = '';
		if (is_null($text_arr)) {
			// print delimiter line
			foreach ($max_lens as $key=>$len)
				$res .= '+'.str_pad('-',$len+2,'-');
			$res .= "+\n";
		}
		else {
			foreach ($text_arr as $name=>$val) {
				switch ($name) {
					case 'key_len':
					case 'rows':
						$res .= '|'.str_pad($val.' ',$max_lens[$name]+2,' ',STR_PAD_LEFT);
						break;
					default:
						$res .= '|'.str_pad(' '.$val,$max_lens[$name]+2);
				}
			}
			$res .= "|\n";
		}
		return $res;
	}

	function realQuery($query){
		$start_time = microtime();
		$result = parent::realQuery();
		$finish_time = microtime();

		// FIXME
		if ($this->_tracing and $this->cursor !==true) // exclude modify queries from explain
			$this->_trace_query($query,$start_time, $finish_time);
	}
}
