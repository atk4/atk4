<?php // vim:ts=4:sw=4:et:fdm=marker
/**
  BaseException is parent of all exceptions in Agile Toolkit which
  are meant to be for informational purposes. There are also some
  exceptions (StopInit) which are used for data-flow.

  Learn:

  Reference:
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4 
    http://agiletoolkit.org/
  
   (c) 2008-2012 Romans Malinovskis <romans@agiletoolkit.org>
   Distributed under Affero General Public License v3
   
   See http://agiletoolkit.org/about/license
 =====================================================ATK4=*/
class BaseException extends Exception {
    // Exception defines it's methods as "final", which is complete nonsence
    // and incorrect behavor in my opinion. Therefore I need to re-declare
    // it's class and re-define the methods so I could extend my own methods
    // in my classes.
    private $frame_stop;
    public $my_backtrace;
    public $shift=0;
    public $name;
    public $owner=null;
    public $api=null;

    public $more_info=array();
    public $actions;
    function init(){
    }
    function __construct($msg,$func=null,$shift=1,$code=0){
        parent::__construct($msg,$code);
        $this->collectBasicData($func,$shift,$code);
    }
    function collectBasicData($func,$shift,$code){
        $this->name=get_class($this);
        $this->frame_stop=$func;
        $this->shift=$shift;

        if(is_int($func)){
            $shift=$func;$func=null;
        }

        $tr=debug_backtrace();
        if(!isset($this->frame_stop)){
            $this->my_backtrace=$tr;
            return;
        }

        while($tr[0] && $tr[0]['function']!=$this->frame_stop){
            array_shift($tr);
        }
        if($tr){
            $this->my_backtrace=$tr;
            return;
        }
        $this->my_backtrace = debug_backtrace();
    }
    /** Call this to add additional information to the exception you are about to throw */
    function addMoreInfo($key,$value){
        $this->more_info[$key]=$value;
        return $this;
    }
    /** Add reference to the object. Do not call this directly, exception() method takes care of that */
    function addThis($t){
        return $this->addMoreInfo('Raised by object',$t);
    }
    /** Actions will be displayed as links on the exception page allowing viewer to perform additional debug
     * functions. addAction('show info',array('info'=>true)) will result in link to &info=1  */
    function addAction($key,$descr){
        $this->actions[$key]=$descr;
        return $this;
    }
    function getMyTrace(){
        return $this->my_backtrace;
    }
    function getAdditionalMessage(){
        return '';
    }
    function getMyFile(){ return $this->my_backtrace[$this->shift]['file']; }
    function getMyLine(){ return $this->my_backtrace[$this->shift]['line']; }

    /** Returns HTML representation of the exception */
    function getHTML($message=null){
        $html='';
        $html.= '<h2>'.get_class($this).(isset($message)?': '.$message:'').'</h2>';
        $html.= '<p><font color=red>' . $this->getMessage() . '</font></p>';
        $html.= '<p><font color=blue>' . $this->getMyFile() . ':' . $this->getMyLine() . '</font></p>';
        $html.=$this->getDetailedHTML();
        $html.= backtrace($this->shift+1,$this->getMyTrace());
        return $html;
    }
    /** Returns Textual representation of the exception */
    function getText(){
        $text='';$args=array();
        foreach($this->more_info as $key=>$value){
            $args[]=$key.'='.$value;
        }

        $text.= get_class($this).': '.$this->getMessage().' ('.join(', ',$args).')';
        $text.= ' in '.$this->getMyFile() . ':' . $this->getMyLine();
        return $text;
    }
    /** Redefine this function to add additional HTML output */
    function getDetailedHTML(){
        return '';
    }
}
