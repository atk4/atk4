<?php
/**
 * Implements a DB update and/or php scripts launch
 *
 * This component is to be added to API ($api->add('VersionControl')) in order
 * to perform scheduled DB updates or PHP scripts.
 *
 * If VersionControl is to perform DB updates, you should connect to DB BEFORE adding
 * VersionControl
 *
 * It looks the specified directory forongoing updates, executes them and puts
 * results of executing in files:
 * - {update_name}.ok : if update was successful, empty file
 * - {update_name}.fail : if update failed, contains error message
 *
 * Results of update execution can be reviewed manually in the update directory or
 * in the related application which gives the visual interface for update monitoring
 *
 * Created on 09.08.2007 by *Camper* (camper@adevel.com)
 */
class VersionControl extends AbstractController{
	protected $working_dir=null;
	protected $results=array();
	protected $updates_done=0;
	protected $prefix='';
	protected $db=null;

	function init(){
		parent::init();
		//$this->working_dir=$this->api->getConfig('VersionControl/dirname').DIRECTORY_SEPARATOR;
		$this->working_dir=$this->api->locatePath('dbupdates').DIRECTORY_SEPARATOR;
	}
	function execute(){
		foreach($this->getOngoingUpdates() as $update=>$last_result){
			$this->executeUpdate($update,$last_result);
			$this->updates_done++;
		}
	}
	function getOngoingUpdates(){
		/**
		 * Looks the working dir for the files without 'ok' flag
		 */
		// getting scripts
		$result=array();
		$this->results[]='Using directory: '.$this->working_dir;
		if($handle=opendir($this->working_dir)){
			// getting files into an array
			while(false!==($file=readdir($handle))){
				if($this->neededFile($file)){
					$result[$file]=$this->getLastResultFor($file);
					// previous result will be appended with new lines
				}
			}
			closedir($handle);
			//sorting file array
			ksort($result);
		}
		return $result;
	}
	private function neededFile($file){
		// files needed are those without '.ok' flag
		if($this->prefix!=''&&strpos($file,$this->prefix)===false)return false;
		$fileext=strrchr($file, '.');
		if($fileext=='.sql'||$fileext=='.php'){
			// looking for completion flags
			if($this->getLastResultFor($file)=='ok')return false;
			return true;
		}
		// file is of invalid extension
		return false;
	}
	private function getLastResultFor($file){
		if(file_exists($this->working_dir.$file.'.fail'))return file_get_contents($this->working_dir.$file.'.fail');
		if(file_exists($this->working_dir.$file.'.ok'))return 'ok';
		return '';
	}
	function executeUpdate($filename,$last_result=''){
		/**
		 * Executes an update with file $filename
		 * $filename should be a full path to file
		 * if $is_new==true, file treated as never executed, else it's .fail file is appended
		 * on error
		 */
		// executing file on the basis of it's extension
		$fileext=strrchr($filename, '.');
		// transforming last_result to an array
		$last_result=explode("\n",$last_result);
		if($fileext=='.sql')$this->execSQL($filename,$last_result);
		elseif($fileext=='.php')$this->execPHP($filename,$last_result);
	}
	function execSQL($file,$last_result=array()){
		/**
		 * Executes SQL scripts
		 */
		$limit=600;
		$this->results[]='Starting DB update. Setting script time limit to '.$limit;
		set_time_limit($limit);
		$sql=explode(';',file_get_contents($this->working_dir.$file));
		//$sql=$query=file_get_contents($this->working_dir.$file);
		$error=false;
		foreach($sql as $query)if(trim($query) != ''){
			$this->results[]="[".date('d/m/Y H:i:s')."] Version control: SQL executing $query...";
			try{
				$this->getDb()->query($query);
				$this->results[]="[".date('d/m/Y H:i:s')."] Version control: success";
			}catch(Exception $e){
				$error="[".date('d/m/Y H:i:s')."] Version control: FAILED! ".mysql_error();
				// saving error (previous + current) to flag
				$last_result[]=$error;
				$this->results[]=$error;
			}
		}
		if($error===false){
			// creating ok flag
			file_put_contents($this->working_dir.$file.'.ok','');
			// deleting previous .fail (if any)
			if(file_exists($this->working_dir.$file.'.fail'))unlink($this->working_dir.$file.'.fail');
		}else{
			file_put_contents($this->working_dir.$file.'.fail',join($last_result,"\n"));
		}
		$this->results[]='DB update finished. Setting script time limit to 30';
		set_time_limit(30);
		$this->results[]="[".date('d/m/Y H:i:s')."] Version control: executed $file";
	}
	function execPHP($file,$last_result=array()){
		/**
		 * Includes PHP script
		 */
		$this->results[]="[".date('d/m/Y H:i:s')."] Version control: PHP including $file...";
		try{
			include($this->working_dir.$file);
			$this->results[]="[".date('d/m/Y H:i:s')."] Version control: success";
		}catch(Exception $e){
			$error="[".date('d/m/Y H:i:s')."] Version control: FAILED! ".$e->getMessage();
			// saving error (previous + current) to flag
			$last_result[]=$error;
			$this->results[]=$error;
		}
		if($error===false){
			// creating ok flag
			file_put_contents($this->working_dir.$file.'.ok','');
			// deleting previous .fail (if any)
			if(file_exists($this->working_dir.$file.'.fail'))unlink($this->working_dir.$file.'.fail');
		}else{
			file_put_contents($this->working_dir.$file.'.fail',join($last_result,"\n"));
		}
	}
	function getResults(){
		return $this->results;
	}
	function getUpdatesDone(){
		return $this->updates_done;
	}
	function setPrefix($prefix){
		/**
		 * Sets the prefix of updates to process
		 * This can be used to separate updates by users
		 */
		$this->prefix=$prefix;
		return $this;
	}
	private function getDb(){
		/**
		 * Returns a connection to a DB
		 * This method useful for the DB which are not from API connection,
		 * but from separate one (such as own component's DB connection)
		 */
		return is_null($this->db)?$this->api->db:$this->db;
	}
	function setDb($db){
		/**
		 * Sets a DB connection different from API DB
		 */
		$this->db=$db;
		return $this;
	}
	function getWorkingDir(){
		return $this->working_dir;
	}
}
