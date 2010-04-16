<?php
/*
 * This is parametric class implementation mainly developed by mvs@adevel.com. It allows you to exclude
 * actual values from the queries and specify them inside parameter array. Some database drivers
 * may cache queries better when values are passed as parametrs. Also, using parameters gives you an
 * additional security layer, since they are always quoted properly and types are enforced.
 */
class DBlite_psql extends DBlite_sql {
	function parseParamType($type, $name, $value){
		switch ($type) {
			case 'str':
				return (is_null($value))?'null':"'".str_replace("'", "''", $value)."'";
			case 'plain':
			case 'sub':
				return $value;
			case 'int':
				if((!is_null($value)) and ($value==='')){
					return $this->error('NOT set value ("'.$value.'") for numeric parameter "'.$name.'"', $this->last_query);
				}
				return (is_null($value))?'null':$value;
			default:
				return $this->error('PARSE PARAMS ERROR: Unknown parameter type: "'.$type.'" for parameter "'.$name.'"', $this->last_query);
		}
	}
	/*
	function parseParamType($type, $name, $value) {
		switch ($type) {
			case 'str':
				//return (is_null($value))?'null':"'".str_replace("'","''",str_replace("\'","\\\\'",$value))."'";
				return (is_null($value))?'null':"'".mysql_real_escape_string($value)."'";
				break;
			case 'date':
				if ((!is_null($value)) and ($value=='')) {
					return $this->error('NOT set value for DATE parameter "'.$name.'"', $this->last_query);
				}
				if (is_numeric($value)) {
					$value = date('Y-m-d H:i:s',$value);
				}
				return (is_null($value))?'null':"'".$value."'";
				break;
			default:
				return parent::parseParamType($type, $name, $value);
		}
	}
	*/
	function parseParamValues($params) {
		$res = array();
		foreach ($params as $param_name => $param_value) {
			$pn_parts = explode(' ', trim($param_name));
			$pn_type = (count($pn_parts)==1)?'str':$pn_parts[0];
			$pn_name = $pn_parts[count($pn_parts)-1];

			if (($res[$pn_name] = $this->parseParamType($pn_type, $pn_name, $param_value))===false) {
				return false;
			}
		}
		return $res;
	}
	function _charpos($haystack, $needle) {
		// search nearest delimiter
		$res = false;
		$pos_math = array();
		for ($i=0;$i<strlen($needle);$i++) {
			$pos = strpos($haystack, $needle[$i]);
			if ($pos!=false) $pos_math[]= $pos;
		}
		if ($pos_math != array()) {
			$res = $pos_math[0];
			foreach ($pos_math as $it) if ($res > $it) $res = $it;
		}

		return $res;
	}
	function parseParamQuery($query) {
		if (!is_array($query)) {
			return $query;
		} else {

				// check ? - type params
				if (strpos($query['query'], '?')!==false) {
					$params = $query['params'];
					$qry = $query['query'];
					$tmp = array_keys($params);
					if (($tmp[0]===0) and ($tmp[count($tmp)-1]===count($tmp)-1)) {
						$i=0;
						$newpars = array();
						while(($pos = strpos($qry, '?'))!==false) {
						   $qry = substr_replace($qry, ':p'.$i, $pos, 1);
						   $newpars['p'.$i] = $params[$tmp[$i]];
						   $i++;
						}


						$query['query'] = $qry;
						$query['params'] = $newpars;
					}
				}

				//
			if (!$parsedParams = $this->parseParamValues($query['params'])) {
				return false;
			} else {
				$this->last_query['query_str'] = $query['query'];
				$this->last_query['params'] = $query['params'];
				// find parameters in query string
				$string = $query['query'];
				$query_parts = array();
				$delim = ':';
				$pos = DBlite::_charpos($string, $delim);
				while (($pos!=false) and ($string!='')) {
					$query_parts[] = substr($string, 0, $pos);
					$string = substr($string, $pos);
					if ($delim == ':') {
						$delim = " \n\t, )+-";
					}
					else {
						$delim = ':';
					}
					$pos=DBlite::_charpos($string, $delim);
				}
				$query_parts[] = $string;

				// if query['allow_extra'] not empty - allow params not in query
				$allow_extra = !empty($query['allow_extra']);

				// create array with found params
				$params_in_query = array();
				foreach ($query_parts as $key=>$q_part) {
					if (substr($q_part, 0, 1)==':') {
						$par_uname = strtoupper(substr($q_part, 1));
						if (!isset($params_in_query[$par_uname]['q_part'])) {
							$params_in_query[$par_uname]['q_part'] = array();
						}
						array_push($params_in_query[$par_uname]['q_part'], $key);
					}
				}

				// set parameter values and test for complete parameter list
				foreach ($parsedParams as $param_name => $param_value) {
					if (!array_key_exists(strtoupper($param_name), $params_in_query) and !$allow_extra) {

						$p = array();
						foreach (array_keys($params_in_query) as $it) {
							$p[]='"'.$it.'"';
						}
						$this->last_query['params_in_query'] = implode(', ', $p);
						return $this->error('PARSE PARAMS ERROR: Not found parameter "'.$param_name.'" in query.', $this->last_query);
					} else {
						if (isset($params_in_query[strtoupper($param_name)]['value'])) {
							return $this->error('PARSE PARAMS ERROR: Double parameters found "'.$param_name.'"', $this->last_query);
						} else {
							$params_in_query[strtoupper($param_name)]['value'] = $param_value;
						}
					}
				}

				// assing parameters values to part_query array
				foreach ($params_in_query as $par_key=>$par_item) {
					if (isset($par_item['q_part']))
					foreach ($par_item['q_part'] as $q_part_key) {
						if (isset($par_item['value'])) {
							$query_parts[$q_part_key] = $par_item['value'];
						} else {
							$query_parts[$q_part_key] = $par_item['value'];
						}
					}
				}

				// construct query
				return implode('', $query_parts);
			}
		}
	}
}
