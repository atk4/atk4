<?
class BaseException extends Exception {
    // Exception defines it's methods as "final", which is complete nonsence
    // and incorrect behavor in my opinion. Therefore I need to re-declare
    // it's class and re-define the methods so I could extend my own methods
    // in my classes.
    private $frame_stop;
    public $my_backtrace;
    public $shift=0;
    function __construct($msg,$func=null,$shift=1){
        parent::__construct($msg);
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
        return; 
    }
    function getMyTrace(){
        return $this->my_backtrace;
    } 
    function getMyFile(){ return $this->my_backtrace[$this->shift]['file']; }
    function getMyLine(){ return $this->my_backtrace[$this->shift]['line']; }

    function getHTML($message=null){
        $html='';
        $html.= '<h2>'.get_class($this).(isset($message)?': '.$message:'').'</h2>'; 
        $html.= '<p><font color=red>' . $this->getMessage() . '</font></p>'; 
        $html.= '<p><font color=blue>' . $this->getMyFile() . ':' . $this->getMyLine() . '</font></p>'; 
        $html.=$this->getDetailedHTML();
        $html.= backtrace($this->shift+1,$this->getMyTrace());
        return $html;
    }
    function getDetailedHTML(){
        return '';
    }
}

class ExceptionNotConfigured extends Exception {}   // used when config variable is not set
class ObsoleteException extends BaseException {}    // used if obsolete function is called
