<?php
class Dummy {
    function __call($foo,$bar){
    }
    function __set($foo,$bar){
    }
    function __get($foo){
        return null;
    }
}
