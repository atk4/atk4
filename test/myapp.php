<?
// Testing form displaying capability
require_once '../lib/ApiBase.php';

$api = new ApiBase('MyApi');

$menu = $api -> add('Menu');

$menu -> add("Page_Welcome"); 
$menu -> add("Form_Preferences"); 
$wizard = $menu -> add("Wizard");

$wizard -> add("Wizard_Tutorial_Introduction");
$wizard -> add("Wizard_Tutorial_WhatsNew");
$wizard -> add("Wizard_Tutorial_MoreInfo");


$api ->execute();
exit;


$form = $api -> add('Form');
$form -> addField('line','hello','Hello World');
$form -> addSubmit('ok');

if($form->execute()){
    echo "Thank you for submiting my form :)";
    exit;
}

