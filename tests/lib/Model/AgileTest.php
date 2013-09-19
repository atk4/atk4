<?php
/**
 * This model traverses pages of your project to look for test-cases.
 *
 * Test-files must implement a class descending from Page_Tester, refer
 * to that class for more info. 
 */
class Model_AgileTest extends Model {
    public $dir='page';

    function init(){
        parent::init();

        $this->addField('name');
        $this->addField('total');
        $this->addField('success');
        $this->addField('fail');
        $this->addField('exception');
        $this->addField('speed');
        $this->addField('memory');
        $this->addField('result');

        /**
         * This model automatically sets its source by traversing 
         * and searching for suitable files
         */
        $p=$this->api->pathfinder->searchDir($this->dir);
        sort($p);
        $this->setSource('ArrayAssoc',$p);
        $this->addHook('afterLoad',$this);

        return $this;
    }
    function skipped(){
        $this['result']='Skipped';
        return $this;
    }
    function afterLoad(){
        // Extend this method and return skipped() for the tests which
        // you do not want to run
        if (false) {
            return $this->skipped();
        }

        $page='page_'.str_replace('/','_',str_replace('.php','',$this['name']));
        try {
            $p=$this->api->add($page,array('auto_test'=>false));

            if(!$p instanceof Page_Tester){
                $this['result']='Not Supported';
                return;
            }

            if(!$p->proper_responses){
                $this['result']='No proper responses';
                return;
            }

            // This will execute the actual test
            $res=$p->silentTest();

            if($res['skipped']){
                $this['result']='Test was skiped ('.$res['skipped'].')';
                return;
            }


            $this->set($res);
            $this['speed']=round($this['speed'],3);
            //list($this['total_tests'], $this['successful'], $this['time']) = 
            $this['result']=$this['success']==$this['total']?'OK':('FAIL: '.join(', ',$res['failures']));

            $p->destroy();
        }catch(Exception $e){
            $this['fail']='!!';
            $this['result']='Exception: '.($e instanceof BaseException?$e->getText():$e->getMessage());
            return;
        }
    }
}
