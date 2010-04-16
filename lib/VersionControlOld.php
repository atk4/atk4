<?php
/*
 * Created on 26.03.2006 by *Camper*
 */
class VersionControl extends AbstractController{
	private $table;
	private $dirname;
	private $db_version;

	function init(){
		parent::init();
		if(!defined('DTP'))define('DTP','');
		$this->table = DTP.$this->api->getConfig('VersionControl/table','sys_config');
		$this->dirname = $this->api->getConfig('VersionControl/dirname');
		if(substr($this->dirname, strlen($this->dirname))!=DIRECTORY_SEPARATOR)
			// todo - substr($str,-1);
			$this->dirname.=DIRECTORY_SEPARATOR;
		$this->api->addHook('api-defaults',array($this,'versionUpdate'));
	}
	function showInfo(){
		$this->getVersion();
		$this->info("Running database version ".$this->db_version);
		return $this;
	}
	function getVersion(){
		/**
		 * Checking table that contains version info
		 */
		$this->api->db->query("create table if not exists $this->table (db_version varchar(20))");
		if($this->api->db->getOne("select count(*) from $this->table")==0){
			$this->api->db->query("insert into $this->table values(0)");
		}
		$this->db_version = $this->api->db->getOne("select db_version from $this->table");
	}
	function versionUpdate(){
		$this->getVersion();
		if($this->db_version != $this->api->apinfo['release']){
			//getting scripts
			$scripts=array();
			if ($handle = opendir($this->dirname)) {
				//getting files to an array
				while (false !== ($file = readdir($handle))) {
					if($this->neededFile($file)){
						$scripts[sprintf('%06d.%03d.%03d', $this->fileVersion($file),
							$this->fileSubVersion($file), $this->fileSequenceNo($file))]=$file;
					}
				}
				//sorting file array
				ksort($scripts);
				foreach($scripts as $file){
					$fileext = strrchr($file, '.');
					if($fileext == '.sql')$this->execSQL($file);
					elseif($fileext == '.php')$this->execPHP($file);
				}
				closedir($handle);
				//updating DB version
				$this->api->db->query("update $this->table set db_version = '".$this->api->apinfo['release']."'");
				$this->debug('Version control: DB updated to '.$this->api->apinfo['release']."\n");
			}
		}
	}
	function execSQL($file){
		/**
		 * Executes SQL scripts
		 */
		$sql = split(';',file_get_contents($this->dirname.$file));
		foreach($sql as $query)if(trim($query) != ''){
			$this->debug("Version control: SQL executing $query...\n");
			try{
				$this->api->db->query($query);
				$this->debug("Version control: success\n");
			}catch(Exception $e){
				$this->debug("Version control: FAILED! ".mysql_error()."\n");
			}
		}
		$this->debug("Version control: executed $file\n");
	}
	function execPHP($file){
		/**
		 * Includes PHP script
		 */
		$this->debug("Version control: PHP including $file...\n");
		try{
			include($this->dirname.$file);
			$this->debug("Version control: success\n");
		}catch(Exception $e){
			$this->debug("Version control: FAILED!\n");
		}
	}
	private function neededFile($file){
		/**
		 * Checks the version of the scripts in dirname and returns true, if they are to be executed
		 */
		$fileext = strrchr($file, '.');
		if($fileext == '.sql'||$fileext == '.php'){
			$ver_parts=split('[.]', basename($file)); //array(release_number, release_subnumber, script_no, extension)
			$filever = $ver_parts[0].'.'.$ver_parts[1];
			return($filever <= $this->api->apinfo['release']&&$filever > $this->db_version);
		}
		return false;
	}
	private function fileVersion($file){
		$ver_parts=split('[.]', basename($file)); //array(release_number, release_subnumber, script_no, extension)
		$result=$ver_parts[0];
		return $result;
	}
	private function fileSubVersion($file){
		$ver_parts=split('[.]', basename($file)); //array(release_number, release_subnumber, script_no, extension)
		$result=$ver_parts[1];
		return $result;
	}
	private function fileSequenceNo($file){
		$ver_parts=split('[.]', basename($file)); //array(release_number, release_subnumber, script_no, extension)
		$result=$ver_parts[2];
		return $result;
	}
}
