<?php
include '../lib/DBlite.php';

$db = DBlite::connect('mysql://root@localhost/tb_05');

$q = $db->dsql();

$result = $q->table('user')->field('name')->do_select()->getOne();

echo "<pre>";
var_dump($result);
