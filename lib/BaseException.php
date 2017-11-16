<?php
/**
 * BaseException is parent of all exceptions in Agile Toolkit which
 * are meant to be for informational purposes. There are also some
 * exceptions (StopInit) which are used for data-flow.
 */
class BaseException extends Exception
{
    // Exception defines it's methods as "final", which is complete nonsence
    // and incorrect behavor in my opinion. Therefore I need to re-declare
    // it's class and re-define the methods so I could extend my own methods
    // in my classes.

    /** @var array Backtrace array */
    public $my_backtrace;

    /** @var int Backtrace shift */
    public $shift = 0;

    /** @var string Classname of exception */
    public $name;

    /** @var string|int error code */
    public $code;

    /** @var array Array with more info */
    public $more_info = array();

    /** @var string Plain text recommendation on how the problem can be solved */
    public $recommendation;

    /** @var array Array of available actions */
    public $actions = array();

    /** @var Exception Link to another exception which caused this one */
    public $by_exception = null;

    /** @var AbstractObject Link to object into which we added this object */
    public $owner;

    /** @var App_CLI Always points to current Application */
    public $app;

    /**
     * @deprecated 4.3.0 Left for compatibility with ATK 4.2 and lower, use ->app instead
     */
    public $api;



    /**
     * Initialization.
     */
    public function init()
    {
    }

    /**
     * On class construct.
     *
     * @param string $msg  Error message
     * @param string|int $code Error code
     */
    public function __construct($msg, $code = 0)
    {
        parent::__construct($msg, $code);
        $this->collectBasicData($code);
    }

    /**
     * Collect basic data of exception.
     *
     * @param string|int $code Error code
     */
    public function collectBasicData($code)
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
     * @param mixed $value
     *
     * @return $this
     */
    public function addMoreInfo($key, $value)
    {
        $this->more_info[$key] = $value;

        return $this;
    }

    /**
     * Add reference to the object.
     * Do not call this directly, exception() method takes care of that.
     *
     * @param AbstractObject $obj
     */
    public function addThis($obj)
    {
        return $this->addMoreInfo('Raised by object', $obj);
    }

    /**
     * Set error code
     *
     * @param int $code
     */
    public function setCode(int $code)
    {
        $this->code = $code;
    }

    /**
     * Records another exception as a cause of your current exception.
     * Wrapping one exception inside another helps you to track problems
     * better.
     *
     * @param Exception $e
     *
     * @return $this
     */
    public function by(Exception $e)
    {
        $this->by_exception = $e;

        return $this;
    }

    /**
     * Actions will be displayed as links on the exception page allowing viewer
     * to perform additional debug functions.
     * addAction('show info',array('info'=>true)) will result in link to &info=1.
     *
     * @param string|array $key
     * @param string|array $descr
     *
     * @return $this
     */
    public function addAction($key, $descr)
    {
        if (is_array($key)) {
            $this->recommendation = (string) $descr;
            $this->actions = array_merge($this->actions, $key);

            return $this;
        }
        $this->actions[$key] = $descr;

        return $this;
    }

    /**
     * Return collected backtrace info.
     *
     * @return array
     */
    public function getMyTrace()
    {
        return $this->my_backtrace;
    }

    /**
     * Return filename from backtrace log.
     *
     * @return string
     */
    public function getMyFile()
    {
        return $this->my_backtrace[2]['file'];
    }

    /**
     * Return line number from backtract log.
     *
     * @return string
     */
    public function getMyLine()
    {
        return $this->my_backtrace[2]['line'];
    }

    /**
     * Returns HTML representation of the exception.
     *
     * @return string
     */
    public function getHTML()
    {
        $e = $this;

        $o = '<div class="atk-layout">';

        $o .= $this->getHTMLHeader();

        $o .= $this->getHTMLSolution();

        //$o.=$this->getHTMLBody();

        $o .= '<div class="atk-layout-row"><div class="atk-wrapper atk-section-small">';
        if (isset($e->more_info)) {
            $o .= '<h3>Additional information:</h3>';
            $o .= $this->print_r($e->more_info, '<ul>', '</ul>', '<li>', '</li>', ' ');
        }
        if (method_exists($e, 'getMyFile')) {
            $o .= '<div class="atk-effect-info">'.$e->getMyFile().':'.$e->getMyLine().'</div>';
        }

        if (method_exists($e, 'getMyTrace')) {
            $o .= $this->backtrace(3, $e->getMyTrace());
        } else {
            $o .= $this->backtrace(@$e->shift, $e->getTrace());
        }

        if (isset($e->by_exception)) {
            $o .= '<h3>This error was triggered by the following error:</h3>';
            if ($e->by_exception instanceof self) {
                $o .= $e->by_exception->getHTML();
            } elseif ($e->by_exception instanceof Exception) {
                $o .= $e->by_exception->getMessage();
            }
        }
        $o .= '</div></div>';

        return $o;
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return get_class($this).': '.htmlspecialchars($this->getMessage()).
            ($this->getCode() ? ' [code: '.$this->getCode().']' : '');
    }

    /**
     * @return string
     */
    public function getHTMLHeader()
    {
        return
            "<div class='atk-layout-row atk-effect-danger atk-swatch-red'>".
            "<div class='atk-wrapper atk-section-small atk-align-center'><h2>".
            $this->getHeader().
            "</h2>\n".
            '</div></div>';
    }

    /**
     * @return array
     */
    public function getSolution()
    {
        return $this->actions;
    }

    /**
     * @return string
     */
    public function getHTMLSolution()
    {
        $solution = $this->getSolution();
        if (empty($solution)) {
            return '';
        }

        return
            "<div class='atk-layout-row atk-effect-info'>".
            "<div class='atk-wrapper atk-section-small atk-swatch-white atk-align-center'>".
            "<h3>".$this->recommendation."</h3>".
            $this->getHTMLActions().
            '</div></div>';
    }

    /**
     * @return string
     */
    public function getHTMLActions()
    {
        $o = '';
        foreach ($this->actions as $label => $url) {
            $o .= "<a href='".$url."' class='atk-button atk-swatch-yellow'>".$label."</a>\n";
        }

        return $o;
    }

    /**
     * Utility.
     *
     * @param array|object|string $key
     * @param string $gs
     * @param string $ge
     * @param string $ls
     * @param string $le
     * @param string $ind
     *
     * @return null|string
     */
    public function print_r($key, $gs, $ge, $ls, $le, $ind = ' ')
    {
        $o = '';
        if (strlen($ind) > 3) {
            return;
        }
        if (is_array($key)) {
            $o = $gs;
            foreach ($key as $a => $b) {
                $o .= $ind.$ls.$a.': '.$this->print_r($b, $gs, $ge, $ls, $le, $ind.' ').$le;
            }
            $o .= $ge;
        } elseif (is_object($key)) {
            $o .= 'Object '.get_class($key);
        } else {
            $o .= $gs ? htmlspecialchars($key) : $key;
        }

        return $o;
    }

    /**
     * Classes define a DOC constant which points to a on-line resource
     * containing documentation for given class. This method will
     * return full URL for the specified object.
     *
     * @param AbstractObject $o
     *
     * @return bool|string
     */
    public function getDocURL($o)
    {
        if (!is_object($o)) {
            return false;
        }

        if (!$o instanceof AbstractObject) {
            return false;
        }

        /*$refl = new ReflectionClass($o);
        $parent = $refl->getParentClass();


        if($parent) {
            // check to make sure property is overriden in child
            $const = $parent->getConstants();
        var_Dump($const);
            if ($const['DOC'] == $o::DOC) return false;
        }
        */

        $url = $o::DOC;
        if (substr($url, 0, 4) != 'http') {
            return 'http://book.agiletoolkit.org/'.$url.'.html';
        }

        return $url;
    }

    /**
     * @param int $sh
     * @param array $backtrace
     *
     * @return string
     */
    public function backtrace($sh = null, $backtrace = null)
    {
        $output = '<div class="atk-box-small atk-table atk-table-zebra">';
        $output .= "<table>\n";
        $output .= "<tr><th align='right'>File</th><th>Object Name</th><th>Stack Trace</th><th>Help</th></tr>";
        if (!isset($backtrace)) {
            $backtrace = debug_backtrace();
        }
        $sh -= 2;

        $n = 0;
        foreach ($backtrace as $bt) {
            ++$n;
            $args = '';
            if (!isset($bt['args'])) {
                continue;
            }
            foreach ($bt['args'] as $a) {
                if (!empty($args)) {
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
                        $args .= 'Array('.count($a).')';
                        break;
                    case 'object':
                        $args .= 'Object('.get_class($a).')';
                        break;
                    case 'resource':
                        $args .= 'Resource('.strstr((string) $a, '#').')';
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

            if (($sh == null && strpos($bt['file'], '/atk4/lib/') === false)
                || (!is_int($sh) && $bt['function'] == $sh)
            ) {
                $sh = $n;
            }

            $doc = $this->getDocURL($bt['object']);
            if ($doc) {
                $doc .= '#'.get_class($bt['object']).'::'.$bt['function'];
            }

            $output .= '<tr><td valign=top align=right class=atk-effect-'.
                ($sh == $n ? 'danger' : 'info').'>'.htmlspecialchars(dirname($bt['file'])).'/'.
                '<b>'.htmlspecialchars(basename($bt['file'])).'</b>';
            $output .= ":{$bt['line']}</font>&nbsp;</td>";
            $name = (!isset($bt['object']->name)) ? get_class($bt['object']) : $bt['object']->name;
            if ($name) {
                $output .= '<td>'.$name.'</td>';
            } else {
                $output .= '<td></td>';
            }
            $output .= '<td valign=top class=atk-effect-'.($sh == $n ? 'danger' : 'success').'>'.
                get_class($bt['object'])."{$bt['type']}<b>{$bt['function']}</b>($args)</td>";

            if ($doc) {
                $output .= "<td><a href='".$doc."' target='_blank'><i class='icon-book'></i></a></td>";
            } else {
                $output .= '<td>&nbsp;</td>';
            }
            $output .= '</tr>';
        }
        $output .= "</table></div>\n";

        return $output;
    }

    /**
     * Returns Textual representation of the exception.
     *
     * @return string
     */
    public function getText()
    {
        $more_info = $this->print_r($this->more_info, '[', ']', '', ',', ' ');

        $text = get_class($this).': '.$this->getMessage().' ('.$more_info.')'.
            ' in '.$this->getMyFile().':'.$this->getMyLine();

        return $text;
    }

    /**
     * Redefine this function to add additional HTML output.
     *
     * @return string
     */
    public function getDetailedHTML()
    {
        return '';
    }

    /**
     * Undocumented.
     *
     * @todo Check this method, looks something useless. Optionally used only in Logger class.
     *
     * @return string
     */
    public function getAdditionalMessage()
    {
        return $this->recommendation;
    }
}
