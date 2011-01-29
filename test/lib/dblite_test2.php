<?php
include '../lib/DBlite.php';

$db = new DBlite;

$q = $db->dsql();

$result = $q->table('user')->field('name')->update();

echo "<pre>";
var_dump($result);
