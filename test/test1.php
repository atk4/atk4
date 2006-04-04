<?php
 class Setter {
    public $n;
    public $x = array("a" => 1, "b" => 2, "c" => 3);
 
    function __get($nm) {
        print "Getting [$nm]\n";
 
        if (isset($this->x[$nm])) {
            $r = $this->x[$nm];
            print "Returning: $r\n";
            return $r;
        } else {
            print "Nothing!\n";
        }
    }
 
    function __set($nm, $val) {
        print "Setting [$nm] to $val\n";
 
        if (isset($this->x[$nm])) {
            $this->x[$nm] = $val;
            print "OK!\n";
        } else {
            print "Not OK!\n";
        }
    }
 }
 
 
 $foo = new Setter();
 $foo->n = 1;
 $foo->a = 100;
 $foo->a++;
 $foo->z++;
 var_dump($foo);
?>
