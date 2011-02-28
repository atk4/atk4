<?php
require_once '../lib/DBlite.php';
 
$result = DBLite::parseDSN("mytype://protocol+hostspec:123/database");
echo "<pre>";
var_dump($result);
?>
