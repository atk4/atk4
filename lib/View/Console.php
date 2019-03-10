<?php
/**
 * Implements a visual console, which receives information from PHP script
 * in real-time and outputs it on the screen. Console takes advantage of
 * server-side events in HTML5.
 *
 * There are several usage patters with the console. The simplest is
 * to excetue a process and have it's output streamed into the console
 * in real-time.
 *
 * You can manually specify a call-back method which will be executed
 * and you can send regular and error output to the console with
 * simple methods out() ond err().
 *
 * Finally - you can supply several streams which cosole will read
 * from and output to the browser until streams are closed.
 */
class View_Console extends \View
{
    /** @var System_ProcessIO */
    public $process = null;     // ProcessIO, if set
    
    /** @var array */
    public $streams = [];       // PHP stream if set.

    /** @var array */
    public $prefix = [];

    /** @var callable */
    public $callback = null;

    /** @var array */
    public $color;

    public function afterAdd($me, $o)
    {
        $this->app->addHook('output-debug', function ($junk, $o, $msg) {
            if ($o instanceof DB) {
                $this->breakHook(true);
            }
            $this->out(get_class($o).': '.$msg);
            $this->breakHook(true);
        }, [], 1);
    }

    /**
     * Sends text through SSE channel. Text may contain newlines
     * which will be transmitted proprely. Optionally you can
     * specify ID also.
     */
    public function sseMessageLine($text, $id = null)
    {
        if (!is_null($id)) {
            echo "id: $id\n";
        }

        $text = explode("\n", $text);
        $text = 'data: '.implode("\ndata: ", $text)."\n\n";
        echo $text;
        flush();
    }

    /**
     * Sends text or structured data through SSE channel encoded
     * in JSON format. You may supply id argument.
     */
    public function sseMessageJSON($text, $id = null)
    {
        if (!is_null($id)) {
            echo "id: $id\n";
        }

        $text = 'data: '.json_encode($text)."\n\n";
        $this->_out_encoding = false;
        echo $text;
        flush();
        $this->_out_encoding = true;
    }

    /**
     * Evaluates piece of code.
     *
     * @param callable $callback function($console)
     */
    public function set($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Displays error on the console (in red).
     */
    public function err($str)
    {
        $data = ['text' => rtrim($str, "\n")];
        $data['style'] = 'color: #f88';
        $this->sseMessageJSON($data);
    }

    /**
     * Add ability to send javascript.
     */
    public function jsEval($str)
    {
        if (is_object($str)) {
            $str = $str->_render();
        }
        $data = ['js' => $str];
        $this->sseMessageJSON($data);
    }

    /**
     * Displays output in the console.
     */
    public function out($str, $opt = array())
    {
        $data = array_merge($opt, ['text' => rtrim($str, "\n")]);
        //if($color)$data['style']='color: '.$color;
        $this->sseMessageJSON($data);
    }

    private $_out_encoding = true;
    public function _out($str)
    {
        if (!$this->_out_encoding) {
            return $str;
        }

        return 'data: '.json_encode(['text' => rtrim($str, "\n")])."\n\n";
    }

    private $destruct_send = false;
    public function __destruct()
    {
        if ($this->destruct_send) {
            $this->out('--[ <i class="icon-ok"></i> DONE ]--------');
        }
    }

    public function render()
    {
        if ($_GET['sse_'.$this->name]) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Cache-Control: private');
            header('Pragma: no-cache');
            // Imants: browser/server should sort this out themselves.
            // See: https://bugs.chromium.org/p/chromium/issues/detail?id=746421
            //header('Content-Encoding: none;');
            $this->destruct_send = true;

            if (ob_get_level()) {
                ob_end_clean();
            }

            $this->out('--[ <i class="icon-spinner"></i> Executing... ]--------');
            // If the process is running, it will have
            // stdout we can read:
            if ($this->process) {
                // fetch streams
                if (!$this->process->pipes['out']) {
                    throw $this->exception('If you associate console with the process, you should execute it.');
                }
                $this->addStream($this->process->pipes['out']);
                $this->addStream($this->process->pipes['err'], 'ERR', '#f88');
            }

            while (!empty($this->streams)) {
                $read = $this->streams; // copy
                $write = $except = [];

                if (($foo = stream_select($read, $write, $except, 5)) !== false) {
                    foreach ($read as $socket) {
                        $data = fgets($socket);
                        if ($data === false) {
                            if (($key = array_search($socket, $this->streams)) !== false) {
                                unset($this->streams[$key]);
                            }
                            continue;
                        }
                        $data = ['text' => rtrim($data, "\n")];

                        $s = (string) $socket;
                        if ($this->prefix[$s]) {
                            $data['text'] = $this->prefix[$s].': '.$data['text'];
                        }
                        if ($this->color[$s]) {
                            $data['style'] = 'color: '.$this->color[$s];
                        }

                        if (!empty($data)) {
                            $this->sseMessageJSON($data);
                        }
                    }
                }
            }

            if ($this->callback) {
                try {
                    $c = $this;
                    ob_start([$this, '_out'], 1);

                    $this->addHook('afterAdd', array($this, 'afterAdd'));

                    call_user_func($this->callback, $this);
                    ob_end_flush();
                } catch (Exception $e) {
                    $this->err('Exception: '.($e instanceof BaseException ? $e->getText() : $e->getMessage()));
                }
                exit;
            }

            exit;
        }

        $url = $this->app->url(null, array('sse_'.$this->name => true));
        $key = $this->getJSID().'_console';

        // TODO: implement this:
        // http://www.qlambda.com/2012/10/smoothly-scroll-element-inside-div-with.html

        parent::render();
        $j = $this->getJSID();
        $this->output(<<<EOF
<script>
var source_$j = new EventSource("$url");
source_$j.onmessage = function(event) {
    var dst = $('#$key');
    var data=$.parseJSON(event.data);

    if(data.js){
        eval(data.js);
        return;
    }

    var text=data.text;

    if(data.class)text='<span class="'+data.class+'">'+text+'</span>';
    if(data.style)text='<span style="'+data.style+'">'+text+'</span>';

    dst.html(dst.html()+text+"\\n");
    var height = dst[0].scrollHeight;
    console.log(height);
    dst.stop().animate({scrollTop:height});

};
source_$j.onerror = function(event) {
    event.target.close();
}
</script>
EOF
        );
    }

    public function addStream($stream, $prefix = null, $color = null)
    {
        $this->streams[] = $stream;

        if (!is_null($prefix)) {
            $this->prefix[(string) $stream] = $prefix;
        }
        if (!is_null($color)) {
            $this->color[(string) $stream] = $color;
        }

        return $this;
    }

    public function getProcessIO()
    {
        return $this->process = $this->add('System_ProcessIO');
    }

    public function defaultTemplate()
    {
        return array('view/console');
    }
}
