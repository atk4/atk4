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
class ApiInstall extends ApiWeb {
    function init(){
        parent::init();

        $this->page=null;
        $this->saved_base_path = $this->pm->base_path;
        $this->pm->base_path.=basename($_SERVER["SCRIPT_NAME"]);

        $this->add('jUI');

        $this->template->trySet('version','Web Software Installer');

        $this->stickyGET('step');

        $this->s_first=$this->s_last=$this->s_current=$this->s_prev=$this->s_next=null;
        $this->s_cnt=0;

        $this->initInstaller();
    }
    function initInstaller(){
        $m=$this->add('Menu',null,'Menu');

        foreach(get_class_methods($this) as $method){
            list($a,$method)=explode('step_',$method); if(!$method)continue;
            //var_dump($method);

            if(is_null($this->s_first))$this->s_first=$method;
            $this->s_last=$method;

            $u=$this->url(null,array('step'=>$method));
            $this->page=$this->pm->base_path.'?step='.$_GET['step'];
            $m->addMenuItem($u,ucwords(strtr($method,'_',' ')));
            $this->page=null;

            if(is_null($this->s_current)){
                $this->s_cnt++;
                if($method==$_GET['step']){
                    $this->s_current=$method;
                    $this->s_title=ucwords(strtr($method,'_',' '));
                }else{
                    $this->s_prev=$method;
                }
            }else{
                if(is_null($this->s_next))$this->s_next=$method;
            }


        }
        if(!$_GET['step']){
            return $this->showIntro();
        }

        $this->initStep($this->s_current);
    }
    function initStep($step){
        //var_dump($this->s_first,$this->s_prev,$this->s_current,$this->s_next,$this->s_last);
        //echo $this->url(null,array('step'=>$this->s_first));
        $step='step_'.$step;
        if(!$this->hasMethod($step))return $this->add('H1')->set('No such step');
        $this->header=$this->add('H1')->set('Step '.$this->s_cnt.': '.$this->s_title);
        return $this->$step();
    }
    function showIntro(){
        $this->add('H1')->set('Welcome to Web Software');
        $this->add('P')->set('Thank you for downloading this software. This wizard will guide you through the installation procedure.');

        if(!is_writable('.') ){
            $this->add('View_Warning')->setHTML('This installation does not have permissions to create your <b>config.php</b> file for you. You will need to manually create this file');
        }elseif(file_exists('config.php')){
            $this->add('View_Warning')->setHTML('It appears that you alerady have <b>config.php</b> file in your applicaiton folder. This installation will read defaults from config.php, but it will ultimatelly <b>overwrite</b> it with the new settings.');
        }

        $this->add('Button')->set('Start')->js('click')->univ()->location($this->stepURL('first'));
    }
    function stepURL($position){
        $s='s_'.$position;
        $s=$this->$s;
        return $this->url(null,array('step'=>$s));
    }
}
