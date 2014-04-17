<?php

chdir('..');
include 'loader.php'; 
include 'lib/Frontend.php';
	
$api = new Frontend('myrealm');
$api->main(); 