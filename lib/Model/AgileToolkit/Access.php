<?php
class Model_AgileToolkit_Access extends Model {
    public $table='agiletoolkit_access';
    function init(){
        parent::init();

        $this->addField('email');
        $this->addField('password');
        $this->addField('token')->system(true);     // token is issued as alternative to password

        $this->setSource('Session');

        $this->addHook('beforeSave',$this);

        $this->hasMany('AgileToolkit_Licenes');
        $this->hasMany('AgileToolkit_Purchase');
        $this->hasMany('AgileToolkit_Addon');
    }

    function auth(){
        $result=$this->request(array('email'=>$this['email'], 'password'=>$this['password']));
        if($result['token']){

            $this['token']=$result['token'];
            $this['password']=null;

        }else throw $this->exception('Authentication Failed','AccessDenied')->addMoreInfo('error',$result['error']);
    }

    function beforeSave(){
        // Connect, then convert password into token, which is saved 
        if($this['password']){
            $this->auth();
        }
    }
}
