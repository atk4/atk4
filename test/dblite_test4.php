<?
include '../lib/DBlite.php';

$db = DBlite::connect('mysql://root@localhost/ml_01');

$q = $db->dsql();

$q->table('account');
$q->field('login');
$q->where('id=3');
$q->do_select();
$result = $q->getOne();

echo "<pre>";
var_dump($result);
