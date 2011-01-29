<?php
define('DEBUG',true);
include('../DBlite.php');

$db = DBlite::connect('cluster://D1:D1@8.2.119.7/D1');

DEBUG and print 'DB::connect returned: '.$db."\n";

$sql = "select * from t1";
$db->query($sql);

while ($Data[] = $db->fetchHash());

array_pop($Data);

while (list($k,$v)=each($Data))
 {
 print("\n\nRow ".$k.":\n");

 while (list($kk,$vv)=each($v))
  {
  print("Field `$kk` = `$vv`\n");
  }
 }

DEBUG and print "END OF SELECT test\n";

$sql = "insert into t1 values('','insert1','insert from test')";
$db->query($sql);

DEBUG and print "END OF INSERT test\n";

$sql = "update t1 set name='insert2' where name='insert1'";
$db->query($sql);
print('Rows affected: '.$db->affectedRows()."\n");

DEBUG and print "END OF UPDATE test\n";
?>
