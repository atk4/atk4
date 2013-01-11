<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class Model_AgileToolkit_Access extends Model {
    public $table='agiletoolkit_access';
    function init(){
        parent::init();

        $this->addField('email');
        $this->addField('password');
        $this->addField('token')->system(true);     // token is issued as alternative to password

        $this->setSource('Session');
        //$this->addCache('Session');

        $this->addHook('beforeSave',$this);

        $this->hasMany('AgileToolkit_Licenes');
        $this->hasMany('AgileToolkit_Purchase');
        $this->hasMany('AgileToolkit_Addon');

        // We would need to use controller if we want to talk with API
    }

    function auth(){
        //$s=$this->setController('Data_JSONRPC')
            //->setSource('http://sites.local.agiletech.ie/atk42/atk4-testsuite/testrpc.php');

        $s=$this->setController('Data_Dumper');
        $s->setPrimarySource($this,'JSONRPC','http://sites.local.agiletech.ie/atk42/atk4-testsuite/testrpc.php');

        $result=$s->request($this, 'auth',array(
            'email'=>$this['email'], 'password'=>$this['password']));

        if($result['token']){

            $this['token']=$result['token'];
            $this['password']=null;

        }else throw $this->exception('Authentication Failed','AccessDenied')->addMoreInfo('error',$result['error']);
        $this->save();
    }

    function beforeSave(){
        // Connect, then convert password into token, which is saved 
        if($this['password']){
            $this->auth();
        }
    }
}
