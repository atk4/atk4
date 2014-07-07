<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * BaseException is parent of all exceptions in Agile Toolkit which
 * are meant to be for informational purposes. There are also some
 * exceptions (StopInit) which are used for data-flow.
 *
 * Learn:
 *
 * Reference:
 *//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
class BaseException extends Exception
{
    // Exception defines it's methods as "final", which is complete nonsence
    // and incorrect behavor in my opinion. Therefore I need to re-declare
    // it's class and re-define the methods so I could extend my own methods
    // in my classes.

    // Backtrace array
    public $my_backtrace;

    // Backtrace shift
    public $shift = 0;

    // Classname of exception
    public $name;

    // Array with more info
    public $more_info = array();

    // Array of available actions
    public $actions;

    // Link to another exception which caused this one
    public $by_exception=null;



    /**
     * Initialization
     */
    function init()
    {
    }

    /**
     * On class construct
     *
     * @param string $msg Error message
     * @param string $code Error code
     *
     * @return void
     */
    function __construct($msg, $code = 0)
    {
        parent::__construct($msg, $code);
        $this->collectBasicData($code);
    }

    /**
     * Collect basic data of exception
     *
     * @param string $code Error code
     *
     * @return void
     */
    function collectBasicData($code)
    {
        $this->name = get_class($this);
        $this->my_backtrace = debug_backtrace();
        array_shift($this->my_backtrace);
        array_shift($this->my_backtrace);
    }

    /**
     * Call this to add additional information to the exception you are about
     * to throw.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    function addMoreInfo($key, $value)
    {
        $this->more_info[$key] = $value;
        return $this;
    }

    /**
     * Add reference to the object.
     * Do not call this directly, exception() method takes care of that.
     *
     * @param string $t
     */
    function addThis($t)
    {
        return $this->addMoreInfo('Raised by object', $t);
    }

    function setCode(int $code){
        $this->code=$code;
    }

    /**
     * Records another exception as a cause of your current exception.
     * Wrapping one exception inside another helps you to track problems
     * better
     */
    function by(Exception $e){
        $this->by_exception=$e;
        return $this;
    }

    /**
     * Actions will be displayed as links on the exception page allowing viewer
     * to perform additional debug functions.
     * addAction('show info',array('info'=>true)) will result in link to &info=1
     *
     * @param string $key
     * @param array $descr
     *
     * @return $this
     */
    function addAction($key, $descr)
    {
        $this->actions[$key] = $descr;
        return $this;
    }

    /**
     * Return collected backtrace info
     *
     * @return array
     */
    function getMyTrace()
    {
        return $this->my_backtrace;
    }

    /**
     * Return filename from backtrace log
     *
     * @return string
     */
    function getMyFile()
    {
        return $this->my_backtrace[2]['file'];
    }

    /**
     * Return line number from backtract log
     *
     * @return string
     */
    function getMyLine()
    {
        return $this->my_backtrace[2]['line'];
    }

    /**
     * Returns HTML representation of the exception
     *
     * @param string $message
     *
     * @return string
     */
    function getHTML()
    {
        /*
        $msg = isset($message) ? ': ' . $message : '';

        $html =
            '<h2>' . get_class($this) . $msg . '</h2>' .
            '<p><font color=red>'  . $this->getMessage() . '</font></p>' .
            '<p><font color=blue>' . $this->getMyFile() . ':' .
            $this->getMyLine() . '</font></p>' .
            $this->getDetailedHTML() .
            '' //backtrace($this->shift + 1, $this->getMyTrace())
            ;
            */

        $e=$this;
        $o='<div class="">';
        $o.= "<h2 class='atk-effect-danger'>".get_class($e).": ". htmlspecialchars($e->getMessage()). ($e->getCode()?' [code: '.$e->getCode().']':'') ."</h2>\n";
        if(@$e->more_info){
            $o.= '<div class="atk-box-small">Additional information:';
            $o.= $this->print_r($e->more_info,'<ul>','</ul>','<li>','</li>',' ');
            $o.= '</div>';
        }
        if(method_exists($e,'getMyFile'))$o.= '<div class="atk-effect-info">' . $e->getMyFile() . ':' . $e->getMyLine() . '</div>';

        if(method_exists($e,'getMyTrace'))$o.= $this->backtrace(3,$e->getMyTrace());
        else $o.= $this->backtrace(@$e->shift,$e->getTrace());

        if(@$e->by_exception){
            $o.="<h3>This error was triggered by the following error:</h3>";
            $o.=$e->by_exception->getHTML();
        }
        $o.='</div>';


        return $o;
    }

    /**
     * Utility
     *
     * @param  [type] $key [description]
     * @param  [type] $gs  [description]
     * @param  [type] $ge  [description]
     * @param  [type] $ls  [description]
     * @param  [type] $le  [description]
     * @param  string $ind [description]
     * @return [type]      [description]
     */
    function print_r($key,$gs,$ge,$ls,$le,$ind=' '){
        $o='';
        if(strlen($ind)>3)return;
        if(is_array($key)){
            $o=$gs;
            foreach($key as $a=>$b){
                $o.= $ind.$ls.$a.': '.$this->print_r($b,$gs,$ge,$ls,$le,$ind.' ').$le;
            }
            $o.=$ge;

        }else{
            $o.=$gs?htmlspecialchars($key):$key;
        }
        return $o;
    }

    function backtrace($sh=null,$backtrace=null){

        $output  = '<div class="atk-box-small atk-table atk-table-zebra">';
        $output .= "<table>\n";
        $output .= "<tr><th align='right'>File</th><th>Object Name</th><th>Stack Trace</th></tr>";
        if(!isset($backtrace)) $backtrace=debug_backtrace();
        $sh-=2;

        $n=0;
        foreach($backtrace as $bt){
            $n++;
            $args = '';
            if(!isset($bt['args']))continue;
            foreach($bt['args'] as $a){
                if(!empty($args)){
                    $args .= ', ';
                }
                switch (gettype($a)) {
                    case 'integer':
                    case 'double':
                        $args .= $a;
                        break;
                    case 'string':
                        $a = htmlspecialchars(substr($a, 0, 128)).((strlen($a) > 128) ? '...' : '');
                        $args .= "\"$a\"";
                        break;
                    case 'array':
                        $args .= "Array(".count($a).")";
                        break;
                    case 'object':
                        $args .= "Object(".get_class($a).")";
                        break;
                    case 'resource':
                        $args .= "Resource(".strstr((string)$a, '#').")";
                        break;
                    case 'boolean':
                        $args .= $a ? 'True' : 'False';
                        break;
                    case 'NULL':
                        $args .= 'Null';
                        break;
                    default:
                        $args .= 'Unknown';
                }
            }

            if(($sh==null && strpos($bt['file'],'/atk4/lib/')===false) || (!is_int($sh) && $bt['function']==$sh)){
                $sh=$n;
            }

            $output .= "<tr><td valign=top align=right class=atk-effect-".($sh==$n?'danger':'info').">".htmlspecialchars(dirname($bt['file']))."/".
                "<b>".htmlspecialchars(basename($bt['file']))."</b>";
            $output .= ":{$bt['line']}</font>&nbsp;</td>";
            $name=(!isset($bt['object']->name))?get_class($bt['object']):$bt['object']->name;
            if($name)$output .= "<td>".$name."</td>";else $output.="<td></td>";
            $output .= "<td valign=top class=atk-effect-".($sh==$n?'danger':'success').">".get_class($bt['object'])."{$bt['type']}<b>{$bt['function']}</b>($args)</font></td></tr>\n";
        }
        $output .= "</table></div>\n";
        return $output;
    }



    /**
     * Returns Textual representation of the exception
     *
     * @return string
     */
    function getText()
    {
        $text = '';
        $args = array();
        foreach ($this->more_info as $key => $value) {
            if (is_array($value)) {
                $value = 'Array()';
            }
            $args[] = $key . '=' . $value;
        }

        $text .= get_class($this) . ': ' . $this->getMessage() .
                 ' (' . join(', ', $args) . ')';
        $text .= ' in ' . $this->getMyFile() . ':' . $this->getMyLine();
        return $text;
    }

    /**
     * Redefine this function to add additional HTML output
     *
     * @return string
     */
    function getDetailedHTML()
    {
        return '';
    }

    /**
     * Undocumented
     *
     * @return string
     * @todo Check this method, looks something useless. Optionally used only in Logger class.
     */
    function getAdditionalMessage()
    {
        return '';
    }
}
