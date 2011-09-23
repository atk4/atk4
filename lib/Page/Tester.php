<?php
class Page_Tester extends Page {
    public $variances=array();
    public $input;
    function setVariance($arr){
        $this->variances=$arr;
        foreach($arr as $key=>$item){
            if(is_numeric($key))$key=$item;
            $this->grid->addColumn('html',$key.'_inf',$key.' info');
            $this->grid->addColumn('text,wrap',$key.'_res',$key.' result');
        }
    }
    function init(){
        parent::init();
        $this->grid=$this->add('Grid');
        $this->grid->addColumn('text','name');

        //$this->setVariance(array('GiTemplate','SMlite'));
        $this->setVariance(array('Test'));
        
        $this->runTests();
    }
    function runTests($test_obj=null){

        if(!$test_obj)$test_obj=$this;

        $tested=array();
        $data=array();
        foreach(get_class_methods($test_obj) as $method){
            $m='';
            if(substr($method,0,5)=='test_'){
                $m=substr($method,5);
            }elseif(substr($method,0,8)=='prepare_'){
                $m=substr($method,8);
            }else continue;

            // Do not retest same function even if it has both prepare and test
            if($tested[$m])continue;$tested[$m]=true;

            // Row contains test result data
            $row=array('name'=>$m,'id'=>$m);

            foreach($this->variances as $key=>$vari){
                if(is_numeric($key))$key=$vari;

                // Input is a result of preparation function
                if(method_exists($test_obj,'prepare_'.$m)){
                    $input=$test_obj->{'prepare_'.$m}($vari,$method);
                }else{
                    $input=$test_obj->prepare($vari,$method);
                }

                $this->input=$input;

                $test_func=method_exists($test_obj,'test_'.$m)?
                    'test_'.$m:'test';

                // Test speed
                $me=memory_get_peak_usage();
                $ms=microtime(true);
                /*
                $limit=20;$hl=round($limit /2);
                for($i=0;$i<$limit;$i++){
                    //$result=call_user_func_array(array($test_obj,$test_func),$input);
                    */
                    $result=(string)$test_obj->$test_func($input[0],$input[1]);
                    //$this->$method($vari);
                    /*
                    if($i==$hl){
                        $meh=memory_get_peak_usage();
                    }
                    */
                //}
                $ms=microtime(true)-$ms;
                $me=($mend=memory_get_peak_usage())-$me;
                $row[$key.'_inf']='Speed: '.round($ms,3).'<br/>Memory: '.$me;

                $this->formatResult($row,$key,$result);
            }

            $data[]=$row;
        }
        $this->grid->setStaticSource($data);
    }
    function formatResult(&$row,$key,$result){
        $row[$key.'_res']=$result;
    }
    function expect($value,$expectation){
        return $value==$expectation?'OK':'ERR';
    }

    function _prepare($t,$str){
        $result='';

        for($i=0;$i<100;$i++){
            $result.=$str;
        }
        return array($this->add($t),$result);
    }

}
