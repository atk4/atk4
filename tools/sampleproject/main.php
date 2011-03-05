<?php
// This is a catch-all file for your project. You can change
// some of the values here, which are going to have affect
// on your project

// AgileProject - change to your own API name.
// agile_project - this is realm. It should be unique per-project
// jui - this is theme. Keep it jui unless you want to make your own theme

include 'atk4/loader.php';
$api=new Frontend('sample_project','jui');
$api->main();
?>
