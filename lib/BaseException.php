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
    function getHTML($message = null)
    {
        $msg = isset($message) ? ': ' . $message : '';
        
        $html =
            '<h2>' . get_class($this) . $msg . '</h2>' .
            '<p><font color=red>'  . $this->getMessage() . '</font></p>' .
            '<p><font color=blue>' . $this->getMyFile() . ':' .
            $this->getMyLine() . '</font></p>' .
            $this->getDetailedHTML() .
            backtrace($this->shift + 1, $this->getMyTrace());
        
        return $html;
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
