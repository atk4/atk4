<?php
if (!isset($_SERVER['PHP_AUTH_USER']))
 {
 header("WWW-Authenticate: Basic realm=\"MySQL cluster\"");
 header("HTTP/1.0 401 Unauthorized");
 print('Get the login/password first. ;)');
 exit;
 }

if ($_SERVER['PHP_AUTH_USER'] != 'Granter' or $_SERVER['PHP_AUTH_PW'] != 'clust3r')
 {
 die('Get the login/password first. ;)');
 }

define('CONFIG','./nsfw/amodules3/lib/DBlite/mysql_cluster_config');
require('./nsfw/amodules3/lib/DBlite.php');

// parse config
 $Config = @file(CONFIG);

 if ($Config === false)
  {
  print("<p>Failed to open config file</p>");
  }

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

print('<html><body><h3 align=center>Cluster user management</h3>');

if (isset($_GET['name']))
 {
 $name = mysql_escape_string($_GET['name']);
 $pass = mysql_escape_string($_GET['pass']);
 $db = DBlite::connect('mysql://granter:f3l0cv]@'.$master.'/mysql');
 $sql = "create database $name";
 $db->query($sql);

 $sql = "insert into db values('%','$name','$name','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y')";
 $db->query($sql);
 $sql = "insert into user (Host,User,Password) values('%','$name',password('$pass'))";
 $db->query($sql);
 $sql = "flush privileges";
 $db->query($sql);

 while (list($k,$v)=each($slaves))
  {
  $db = DBlite::connect('mysql://granter:f3l0cv]@'.$v.'/mysql');
  $sql = "insert into db  values('%','$name','$name','Y','N','N','N','N','N','N','N','N','N','N','N')";
  $db->query($sql);
  $sql = "insert into user (Host,User,Password) values('%','$name',password('$pass'))";
  $db->query($sql);
  $sql = "flush privileges";
  $db->query($sql);
  }
 }

if (isset($_GET['revoke']))
 {
 array_push($slaves,$master);
 $slaves = array_unique($slaves);

 while (list($k,$v)=each($slaves))
  {
  $db = DBlite::connect('mysql://granter:f3l0cv]@'.$v.'/mysql');
  $sql = "delete from user where User='".$_GET['revoke']."'";
  $db->query($sql);
  $sql = "delete from db where User='".$_GET['revoke']."'";
  $db->query($sql);
  $sql = "flush privileges";
  $db->query($sql);
  }
 print('<p>Don\'t forget to DROP DATABASE '.$_GET['revoke'].' on master host!</p>');
 }

print('<form action="'.$_SERVER['PHP_SELF'].'" method=GET>
User name: <input type=text name=name size=15><br>
Password : <input type=text name=pass size=15><br>
<input type=submit value="Add user">');
print('<hr><p>Revoke one of the following users\' rights:<br><br>');
$db = DBlite::connect('mysql://granter:f3l0cv]@'.$master.'/mysql');
$sql = "select User from user where user!='root' and user!='replclient' and user!='' and user!='granter'";
$db->query($sql);

while ($Data = $db->fetchHash())
 {
 print('<a href="'.$_SERVER['PHP_SELF'].'?revoke='.$Data['User'].'">'.$Data['User'].'</a><br>');
 }

print('</body></html>');
?>
