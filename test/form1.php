<?
// Testing form displaying capability
require_once '../lib/ApiBase.php';

$api = new ApiBase();

$form = $api -> add('Form');
$form -> addField('line','name','Enter your name')->nn('Enter your name, please');
$form -> addField('line','credit_card','Credit card')->nn('Enter credit card number');
$form -> addField('text','address','Address')->nn('Enter your address');
$form -> addSubmit('ok');

if($form->execute()){
	echo "Thank you for submiting my form, ".$form->get('name')." :)";
	exit;
}
