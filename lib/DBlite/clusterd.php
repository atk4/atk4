<?php
<?php
// define paths
define('STATUS','/home/daru/public_html/nsfw/amodules3/lib/DBlite/mysql_cluster_status');
// Config for users:
define('CONFIG','/home/daru/public_html/nsfw/amodules3/lib/DBlite/mysql_cluster_config');
// Config for clusterd:
define('DCONFIG','/home/daru/public_html/nsfw/amodules3/lib/DBlite/mysql_clusterd_config');

define('SPOOLDIR','/home/daru/public_html/nsfw/amodules3/lib/DBlite/spool/');
define('SPOOLUSER','spooler');
define('SPOOLPASS','sp00ler');
define('CLUSTERDUSER',SPOOLUSER);
define('CLUSTERDPASS',SPOOLPASS);

function setstatus($to)
 {
 $to = (string) $to;
 $fp = @fopen(STATUS,'w');

 if ($fp)
  {
  @fwrite($fp,$to);
  @fclose($fp);
  }
 else
  {
  print("WARNING: could not open status file for writing!\n");
  }
 }

// read status. If '2', don't even load code...
$fp = @fopen(STATUS,'r');

if ($fp === false)
 {
 die("Could not open status file, quitting.\n");
 }

$status = (int) @fgetc($fp);
@fclose($fp);

if ($status == 2)
 {
 die("Will not work with status 2!\n");
 }
else if ($status > 2)
 {
 die("Will not work with unknown status!\n");
 }

// read and parse own config
$Config = @file(DCONFIG);

if ($Config === false)
 {
 die("Failed to open my own config file.\n");
 }

$slaves = array();

while (list($k,$v) = each($Config))
 {
 if (($v{0} != '#') and ($s = trim($v))) // not a comment; not a spaced line
  {
  list($key,$val) = split('=',$s);
  $key = trim($key);
  $val = trim($val);

  if ($key == 'master')
   {
   $master = $val;
   }
  else if ($key == 'slave')
   {
   $slaves[] = $val;
   }
  }
 }

// connect to master

$cmaster = @mysql_connect($master,CLUSTERDUSER,CLUSTERDPASS);

// if master is not available, set status 1

if (!$cmaster and $status == 0)
 {
 setstatus(1);
 $status = 1;
 }

// create a collection of connections to slaves

$cslaves = array();

while (list($k,$v)=each($slaves))
 {
 if (!$cslaves[] = @mysql_connect($v,CLUSTERDUSER,CLUSTERDPASS))
  {
// comment the line in config if cannot connect
  reset($Config);

  while (list($kk,$vv)=each($Config))
   {
   if (strpos($vv,$slaves[$k]))
	{
	$Config[$kk] = '#'.$vv;
	}
   }
  }
 }

// check master status
if ($cmaster)
 {
 $sql = "show status";

 if (!$res = @mysql_query($sql,$cmaster))
  {
// master was alive, but now, somehow, has died
  setstatus(1);
  $status = 1;
  }
 else
  {
  $Data = @mysql_fetch_array($res,MYSQL_ASSOC);

  if ($Data['Threads_running'] > 0 and $Data['Threads_running'] < 100) // 100 is too many!
   {
 // if master appeared (after status 1), push the dumps and set status to 0
   if ($status == 1)
	{
 // push the dumps
	exec('for dumps in `ls '.SPOOLDIR.'`; do mysql -h '.$master.' -u '.SPOOLUSER.' -p'.SPOOLPASS.' < '.SPOOLDIR.'$dumps; done');
	setstatus(0);
	$status = 0;
	}
   }
  else
   {
   if ($status == 0)
	{
	setstatus(1);
	$status = 1;
	}
   }
  }
 }

// loop check slave status; update config in the end (every time the script runs)
reset($cslaves);

while (list($k,$v) = each($cslaves))
 {
// skip non-working
 if ($v)
  {
  $sql = "show slave status";

  if (!$res = @mysql_query($sql,$v))
   {
   $cslaves[$k] = false;
   }
  else
   {
   $Data = @mysql_fetch_array($res,MYSQL_ASSOC);

   if ($Data['Slave_IO_Running'] != 'Yes')
	{
	$cslaves[$k] = false;
	}
   }
  } // if connection alive

// comment the bad slave lines in config
 if ($cslaves[$k] == false) // we need to check again!
  {
  reset($Config);

  while (list($kk,$vv)=each($Config))
   {
   if (strpos($vv,$slaves[$k]))
	{
	$Config[$kk] = '#'.$vv;
	}
   }
  }
 } // while slaves

// if all slaves have gone away, and status == 1, set status to 2, exit
if ($status == 1 and array_sum($cslaves) == 0)
 {
 setstatus(2);
 die("No live slaves, status set to 2, exiting.");
 }

$fp = @fopen(CONFIG,'w');

if ($fp)
 {
 @fwrite($fp,implode('',$Config));
 @fclose($fp);
 }
else
 {
 print("WARNING: could not open config file for writing!\n");
 }
?>
