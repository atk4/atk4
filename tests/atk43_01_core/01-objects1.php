<?php
class Test_Objects1 extends AbstractController {

     public $proper_responses=array(
        "Test_empty"=>'1',
        "Test_clone"=>'1',
        "Test_toString"=>'1',
        "Test_destroy"=>'1',
        "Test_newInstance"=>'1',
        "Test_getElement"=>'1',
        "Test_getElement1"=>'TrackedObject',
        "Test_getElement2"=>'1',
        "Test_hasElement2"=>'1',
        "Test_uniqname"=>'1',
        "Test_add"=>'1',
        "Test_add2_controller"=>'',
        "Test_add1_string_argument"=>'myname',
        "Test_add1_string_argument_arr"=>'myname',
        "Test_add2_array_argument"=>'2',
        "Test_add3_adding_existing_object"=>'obj2',
        "Test_add4_add_fail"=>'You can add only classes based on AbstractObject',
        "Test_add5_name_duplicate"=>'a:2:{i:0;s:4:"obj1";i:1;s:6:"obj1_2";}',
        "Test_add6_both_owners_has_child"=>'1',
        "Test_add7_wrong_class"=>'Class is not valid',
        "Test_add8_hook"=>'OK',
        "Test_add9_hook"=>'OK',
        "Test_session"=>'1',
        "Test_session2"=>'1',
        "Test_session3"=>'1',
        "Test_exception"=>'1',
        "Test_exception2"=>'1',
        "Test_exception3"=>'1',
        "Test_hook1"=>'1',
        "Test_hook2"=>'1',
        "Test_hook3"=>'1',
        "Test_addMethod"=>'1',
        "Test_addGlobalMethod"=>'1'
    );
    function prepare(){
        return array($this->add('MyObject'));
    }
    function test_empty($t){
        return $t->x;
    }
    function test_clone($t){
        $t2=clone $t;
        return $t2->x;
    }
    function test_toString($t){
        return is_string((string)$t);
    }
    function test_destroy($t){
        $s=serialize($t->elements);
        $t->add('MyObject')->destroy();
        $s2=serialize($t->elements);
        unset($t);
        gc_collect_cycles();
        return $s==$s2;
    }
    function test_newInstance($t){
        return $t->newInstance()->x;
    }
    function test_getElement($t){
        return !is_object($t->owner->getElement($t->short_name));
    }
    function test_getElement1($t){
        $t=$this->add('TrackedObject');
        return get_class($t->owner->getElement($t->short_name));
    }
    function test_getElement2($t){
        try{
            $t->owner->getElement('no such element');
            return false;
        }catch(Exception $e){
            return true;
        }
    }
    function test_hasElement2($t){
        return !$t->owner->hasElement('no such element');
    }
    function test_uniqname($t){
        $o1=$t->add('MyObject','c1');
        $o2=$t->add('MyObject','c1');
        return $o1->name!=$o2->name;
    }
    function test_add($t){
        $o1=$t->add('MyObject','c1');
        $o2=$t->add('MyObject','c1');
        $o1->x++;
        return $o1->x != $o2->x;
    }
    function test_add2_controller($t){
        // same controller added twice does not create new object
        $o1=$t->add('MyController','c1');
        $o2=$t->add('MyController','c1');
        $o1->x++;
        return $o1->x == $o2->x;
    }
    function test_add1_string_argument($t){
        $t2 = $t->add('MyObject', 'myname');
        return $t2->short_name;
    }
    function test_add1_string_argument_arr($t){
        $t2 = $t->add('MyObject', array('name'=>'myname'));
        return $t2->short_name;
    }
    function test_add2_array_argument($t){
        $t2 = $t->add('MyObject', array('x'=>2));
        return $t2->x;
    }
    function test_add3_adding_existing_object($t){
        // adding one object into another
        $t1 = $t->add('MyObject', 'obj1');
        $t2 = $t->add('MyObject', 'obj2');

        $x = $t1->add('MyObject', 'child');
        $t2->add($x);
        return $x->owner->short_name;
    }
    function test_add4_add_fail($t){
        try {
            $x = $t->add('Exception');
        }catch(Exception $e){
            return $e->getMessage();
        }
        return "FAIL";
    }
    function test_add5_name_duplicate($t){
        // adding one object into another
        $t1 = $t->add('MyObject', 'obj1');
        $t2 = $t->add('MyObject', 'obj1');

        return serialize(array_keys($t->elements));
    }
    function test_add6_both_owners_has_child($t){
        // adding one object into another
        $t1 = $t->add('MyObject', 'obj1');
        $t2 = $t->add('MyObject', 'obj2');

        $x = $t1->add('MyObject', 'child');
        $t2->add($x);
        return serialize(array_keys($t1->elements)) == serialize(array_keys($t2->elements));
    }
    function test_add7_wrong_class($t){
        // adding one object into another
        try {
            $t1 = $t->add(123);
        }catch(Exception $e){
            return $e->getMessage();
        }
        return "FAIL";
    }
    function test_add8_hook($t){
        $result = "FAIL";

        $t->addHook('afterAdd',function()use(&$result){ $result = "OK"; });
        $t->add('MyObject');

        return $result;
    }
    function test_add9_hook($t){
        $result = "FAIL";

        $app = new App_CLI();
        $app->addHook('beforeObjectInit',function()use(&$result){ $result = "OK"; });
        $t = $app->add('MyObject');

        return $result;
    }
    function test_session($t){
        $t->memorize('foo',true);
        return $t->recall('foo');
    }
    function test_session2($t){
        $t->learn('foo',true);
        $t->learn('foo','123');
        return $t->recall('foo');
    }
    function test_session3($t){
        $t->learn('foo',1);
        $t->forget('foo');
        return $t->recall('foo',2)===2;
    }
    function test_exception($t){
        try{
            throw $t->exception('test');
        }catch(Exception $e){
            return true;
        }
        return false;
    }
    function test_exception2($t){
        try{
            throw $t->exception('test','Logic');
        }catch(Exception_Logic $e){
            return true;
        }
        return false;
    }
    function test_exception3($t){
        try{
            $t->default_exception='Exception_Logic';
            throw $t->exception('test','_Ouch');
        }catch(Exception_Logic_Ouch $e){
            return true;
        }
        return false;
    }
    function myfunc($t,$br=false){
        if($br)$t->breakHook(65);
        return 42;
    }
    function myfunc2($x,$t){
        return 44+$t->x;
    }
    function test_hook1($t){
        $t->addHook('test',array($this,'myfunc'));
        $r=$t->hook('test');
        return $r[0]==42;
    }
    function test_hook2($t){
        $t->addHook('test',array($this,'myfunc'));
        $r=$t->hook('test',array(true));
        return $r==65;
    }
    function test_hook3($t){
        $t->addHook('test',array($this,'myfunc'));
        $t->removeHook('test');
        $r=$t->hook('test',array($t,true));
        return !$r;
    }
    function test_addMethod($t){
        $t->addMethod('testmethod',array($this,'myfunc'));
        return $t->testmethod()==42;
    }
    function test_addGlobalMethod($t){
        $t->app->addGlobalMethod('testmethod_gl',array($this,'myfunc2'));
        $res=($x=$t->testmethod_gl())==45;
        $t->app->removeGlobalMethod('testmethod');
        return $res;
    }
}

class MyObject extends AbstractObject {
    public $x=1;
}
class MyController extends AbstractObject {
    public $x=1;
}
class Exception_Logic_Ouch extends Exception_Logic {
}
class TrackedObject extends AbstractObject {
    public $auto_track_element=true;
}
