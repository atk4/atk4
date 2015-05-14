<?php

/**
 * If you extend class from AbstractController, then Agile Tookit
 * will link it with the application.
 */
class ATK_Test_Object extends AbstractController {

  function test_hello() {
    return isset($this->app->name);
  }
  public $proper_responses=array(
            "Test_hello"=>'1'
  );
}
