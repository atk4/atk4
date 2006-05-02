<?php
/*
 * Created on 26.03.2006 by *Camper*
 */
class VersionControl extends AbstractController{
    public $api;
    public $owner;
    private $table;
    private $dirname;
    private $db_version;
	
	function init(){
		$this->table = $this->api->getConfig('VersionControl/table');
		$this->dirname = $this->api->getConfig('VersionControl/dirname');
		if(substr($this->dirname, strlen($this->dirname))!=DIRECTORY_SEPARATOR)
			$this->dirname.=DIRECTORY_SEPARATOR;
		$this->getVersion();
		$this->versionUpdate();
	}
	function getVersion(){
		/**
		 * Checking table that contains version info
		 */
		$this->api->db->query("create table if not exists $this->table (db_version varchar(20)) engine=MyISAM");
		if($this->api->db->getOne("select count(*) from $this->table")==0){
			$this->api->db->query("insert into $this->table values(0)");
		}
		$this->db_version = $this->api->db->getOne("select db_version from $this->table");
	}
	function versionUpdate(){
		if($this->db_version != $this->api->apinfo['release']){
			//getting scripts
			if ($handle = opendir($this->dirname)) {
				while (false !== ($file = readdir($handle))) { 
					if($this->neededFile($file)){
						$fileext = strrchr($file, '.');
						if($fileext == '.sql')$this->execSQL($file);
						elseif($fileext == '.php')$this->execPHP($file);
					}
				}
				closedir($handle);
				//updating DB version
				$this->api->db->query("update $this->table set db_version = '".$this->api->apinfo['release']."'");
				if(isset($this->api->logger))
					$this->api->logger->logLine('Version control: DB updated to '.$this->api->apinfo['release']."\n");
			}
		}
	}
	function execSQL($file){
		//executing scripts
		$sql = split(';',file_get_contents($this->dirname.$file));
		foreach($sql as $query)if(trim($query) != ''){
			$this->api->logger->logLine("Version control: SQL executing $query...\n");
			try{
				$this->api->db->query($query);
				$this->api->logger->logLine("Version control: success\n");
			}catch(Exception $e){
				$this->api->logger->logLine("Version control: FAILED!\n");
			}
		}
		if(isset($this->api->logger))$this->api->logger->logLine("Version control: executed $file\n");
	}
	function execPHP($file){
		$this->api->logger->logLine("Version control: PHP including $file...\n");
		try{
			include($this->dirname.$file);
			$this->api->logger->logLine("Version control: success\n");
		}catch(Exception $e){
			$this->api->logger->logLine("Version control: FAILED!\n");
		}
	}
	private function neededFile($file){
		$fileext = strrchr($file, '.');
		if($fileext == '.sql'||$fileext == '.php'){
			//get strlen($file)-9 chars (script number - 3 chars, extension - 3 chars, 2 dots: .001.sql)
			$filever = substr($file, 1, strlen($file)-9);
			return($filever <= $this->api->apinfo['release']&&$filever > $this->db_version);
		}
		return false;
	}
}
?>
