<?php

class ATK_Test_Object extends AbstractController {

    function prepare(){
        return [$this->t = $this->add('Controller_Validator')];
    }

    function t($rule, $tests = [], $debug = false)
    {

        $this->t->is([$rule]);
        if($debug)$this->t->debug();

        $res = [];

        foreach($tests as $test){

            try {

                if(is_string($test)){
                    $this->t->setSource(['name'=>$test]);
                }else{
                    $this->t->setSource($test);
                }
                $this->t->now();
                $res[] = 'OK';
            }catch(Exception_ValidityCheck $e){
                $res[] = $e->getMessage();
            }
        }
        unset($this->t);
        return json_encode($res);
    }

    function test_required($v) {return $this->t('name!', ['','foo','false',false,0]); }
    function test_len($v) {return $this->t('name|len|2..3', ['','foo','false',false,0,'longname']); }
    function test_regexp($v) {return $this->t('name|[a-zA-Z]*', ['','fo3o',123,false,0,'longname']); }
    function test_in($v) {return $this->t(['name','in','foo,bar,baz'], ['','foo','false',false,0,'longname']); }
    function test_in_arr($v) {return $this->t(['name','in',['foo','bar','baz']], ['','foo','false',false,0,'longname']); }
    function test_in_not($v) {return $this->t(['name','not_in',['foo','bar','baz']], ['','foo','false',false,0,'longname']); }
    function test_betw($v) {return $this->t('name|3..7', [3,'3','3foo','foo3',false,-3,'7','longname']); }
    function test_gt($v) {return $this->t('name|>3', [3,'3','4foo',4,'4',false,-5,'longname']); }
    function test_lt($v) {return $this->t('name|<4', [3,'3','4foo',4,'4',false,-5,'longname']); }
    function test_gte($v) {return $this->t('name|>=3', [3,'3','4foo',4,'4',false,-5,'longname']); }
    function test_gtf($v) {return $this->t('from|>$to', [['from'=>3,'to'=>4],['from'=>4,'to'=>3]]); }
    function test_gt_date($v) {return $this->t('now|>$yesterday?bust me tomorrow', [['now'=>time(),'yesterday'=>strtotime('yesterday')]]); }
    function test_eq_noalias($v) {return $this->t('one|=$two', [['one'=>1,'two'=>1]]); }
    function test_eq_direct($v) {return $this->t('one|eq|$two', [['one'=>1,'two'=>1]]); }
    function test_eqf($v) {return $this->t('one|eqf|$two', [['one'=>1,'two'=>1]]); }

    function test_number($v) {return $this->t('name|number', [1,'foo','3','0',true,'23,199.00']); }
    function test_decimal($v) {return $this->t('name|decimal', [1,'foo','3','0',true,'23,199.00']); }

    function test_type($v) {return $this->t('name|type|=string', [1,'foo','3','0',true,'23,199.00']); }
    function test_class($v) {return $this->t('name|class|=string', [1,'foo','3','0',true,'23,199.00']); }
}
