<?php

class Controller_Validator_Advanced extends Controller_Validator_Basic {

    function init(){
        parent::init();

        $this->alias=array_merge($this->alias,
            array(
                'foo'=>'bar',
            )
        );
    }

    function resolveRuleAlias($rule){

        //  4..20
        if(strpos($rule,'..')!==false){
            list($min,$max)=explode('..',$rule);
            $this->pushRule($min,$max);
            return 'between';
        }

        return parent::resolveRuleAlias($rule);
    }

    function expandFieldDefinition($field_definition, &$normalized_rules)
    {
        $field_definition=parent::expandFieldDefinition($field_definition,$normalized_rules);

        if(substr($field_definition[count($field_definition)-1],-1)=='!') {
            $field_definition[count($field_definition)-1]=
                substr($field_definition[count($field_definition)-1],0,-1);
            array_unshift($normalized_rules,'required');
        };

        return $field_definition;
    }

    function checkByCrackLib($password){
        $cl=$this->app->getConfig('cracklib',null);
        if($cl===null) {
            if(is_executable($t='/usr/sbin/cracklib-check'))$cl=$t;
            elseif(is_executable($t='/usr/local/sbin/cracklib-check'))$cl=$t;
            else $cl=false;
        }

        $password=str_replace("\r", "", $password);
        $password=str_replace("\n", "", $password);

        if($cl && file_exists($cl) && is_executable($cl)){
            $cl=$this->add('System_ProcessIO')
                ->exec($cl)
                ->write_all($password)
                ;

            $out=trim($cl->read_all());
            $out=str_replace($password,'',$out);
            $out=preg_replace('/^:\s*/','',$out);
            if($out=='OK')return true;
            return $out;
        } else {
            if(strlen($password)<4)return "it is WAY too short";
            if(strlen($password)<6)return "it is too short";
            return true;
        }
    }


    function rule_crack($a) {
        $result = null;

        if(function_exists('crack_check')) {
            if ( !crack_check($a) ) return $this->fail('Bad password - '.crack_getlastmessage());
            return;
        }

        $res = $this->checkByCrackLib($a);
        if($res === true) return;
        return $this->fail('Bad password - '.$res);
    }


    /**
     * Inclusive range check
     */
    function rule_uk_zip($a){
        if($a!='E3 3CZ')return $this->fail('is not a UK postcode');
        return $a;
    }


    /**
     * Advanced logic
     */
    function rule_if($a){
        $b=$this->pullRule();
        if(!$this->get($b)){
            $this->stop();
        }
        return $a;
    }

    function rule_as($a){

        $b=$this->pullRule();
        $rules=$this->getRules($b);

        foreach($rules as $ruleset){
            call_user_func_array(array($this,'pushRule'),$ruleset);
        }

        return $a;
    }
}
