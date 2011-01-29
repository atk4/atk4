<?php
/*
   Typically controller classes are empty. MVC system is due to be rewritten in
   Agile Toolkit 4, and classes will be implemented differently.

   Therefore you should preferably put your business logic into model. Class is needed
   if you plan to use setController(), however

   Controller calls are transparent, so if you use $c->myMethod() and it's not defined
   in here, it will be automatically called inside model's class.
*/


class Controller_User extends Controller {
	public $model_name='Model_User';
}
