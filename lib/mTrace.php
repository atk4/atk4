<?php

/*
	Error and others Tracing
	Version 1.3 12/29/2005
	by mvs@adevel.com
*/

class mTrace {
	var $filename;

	var $err_message;

	var $_current_ip;

	var $_prev_exec_time;

	function mTrace($filename = null) {
		if (is_null($filename))
			$filename = dirname(__FILE__).DIRECTORY_SEPARATOR.'trace.log';

		$this->filename = $filename;

		if (isset($_SERVER['REMOTE_ADDR'])) {
			$this->_current_ip = (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? array_shift(explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"])) : $_SERVER["REMOTE_ADDR"]);
		}
	}

	function _sec2time($sec) {
		$res = '';
		if ($sec<0) {
			$sec = -$sec;
			$res = '-'.$res;
		}

		if ($sec!=floor($sec)) {
			$msec = round(($sec - floor($sec))*1000);

			$msec = '.'.str_pad($msec,3,'0', STR_PAD_LEFT);
			$sec = floor($sec);
		}

		$hours = floor($sec/3600);
		$min = floor(($sec - $hours*3600)/60);
		$sec  = $sec - $hours*3600 - $min*60;

		if ($hours > 0)
			$res .= str_pad($hours,2,'0', STR_PAD_LEFT).':';

		if (($hours > 0) or ($min > 0))
			$res .= str_pad($min,2,'0', STR_PAD_LEFT).':';

		$res .= str_pad($sec,2,'0', STR_PAD_LEFT).$msec;

		return $res;
	}

	function _microtime_float() {
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}

	// print
	function p($message, $file = null, $line = null) {
		$res = true;

		$time_diff_str = '';
		if (!empty($this->_prev_exec_time)) {
			$time_diff = $this->_microtime_float() - $this->_prev_exec_time;
			if ($time_diff < 1) $time_diff_str =  $this->_sec2time($time_diff);
		}

		$details = ((empty($this->_current_ip))?'':$this->_current_ip.' - ').
				   ((!empty($file))?basename($file).' (line '.$line.')':'');

		if (!empty($details)) $details = ' ***** '.$details.' *****';

		$message = '['.date('d-M-Y H:i:s') . '] '.$time_diff_str.$details.
							  "\n\n". $message . "\n\n";

		$new_file = (file_exists($this->filename))?false:true;
		$fh = @fopen($this->filename,'a');

		if (($fh !== false) and (is_resource($fh))) {
			@flock($fh, LOCK_EX);

			if (!@fwrite($fh, $message)) {
				$this->err_message = "Cannot write to file ($this->filename)";
				error_log($this->err_message.' in '.__FILE__.' on line '.__LINE__,0);
				$res = false;
			}
			@flock($fh, LOCK_UN);
			@fclose($fh);

			if ($new_file) chmod($this->filename,0777);
		}
		else {
			 $this->err_message = 'Cannot open file ('.$this->filename.')'.
				' in '.__FILE__.' on line '.__LINE__.' for save message: '."\n".$message;
			 error_log($this->err_message,0);
			 $res = false;
		}

		$this->_prev_exec_time = $this->_microtime_float();

		return $res;
	}
}
?>
