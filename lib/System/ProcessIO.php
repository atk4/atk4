<?php
/**
 * This class provides a much more flexible and efficient interface
 * to process creation and execution. It's similar to how popen
 * works but allows handling multiple streams and supports
 * exceptions
 */
class System_ProcessIO extends AbstractModel
{
    // allow us to execute commands in a flexible and easy way
    public $descriptorspec = array();
    public $args = array();
    public $cmd = null;

    public $pipes = array();  // contain descriptions for stdin/out/err
    public $process = null;

    public $stderr = '';
    public $exic_code = null;

    /**
     * Initialization of the object.
     */
    public function init()
    {
        parent::init();
        $this->debug('ProcessIO initialised, setting descriptor options');
        $this->descriptorspec = array(
                0 => array('pipe', 'r'),  // stdin
                1 => array('pipe', 'w'),  // stdout
                2 => array('pipe', 'w'),
                //2 => array("file", BASEDIR.'/logs/exec.log', 'a' )  // stderr ?? instead of a file
                );
    }
    public function debugStatus()
    {
        //$this->debug('process status: '.print_r(proc_get_status($this->process),true));
    }
    /**
     * Execute the process and associate with this object.
     *
     * @param string $cmd  Command to be executed
     * @param array  $args Arguments (strings)
     *
     * @return self
     */
    public function exec($cmd, $args = array())
    {
        // Arguments must be safe
        if (!is_array($args)) {
            $args = array($args);
        }
        foreach ($args as $val) {
            if (!is_string($val)) {
                throw new BaseException('You must specify arguments as strings');
            }
            $this->args[] = escapeshellarg($val);
        }

        // Command myst be safe
        $this->cmd = escapeshellcmd($cmd);

        $this->debug('Executing '.$cmd.($this->args ? ' '.implode(' ', $this->args) : ''));
        // Now do the execution
        $this->executeRaw($this->cmd.' '.implode(' ', $this->args));

        return $this;
    }

    /**
     * This function just executes command and returns hash of descriptors.
     * Hash will have keys 'in', 'out' and 'err'.
     *
     * This is a handy function to override if you are piping input and
     * output through sockets (such as SSH connection)
     *
     * @param string $command raw and proprely escaped command
     *
     * @return array of pipes
     */
    protected function executeRaw($command)
    {
        $pipes = null;
        $this->process = proc_open($command, $this->descriptorspec, $pipes);
        if (!is_resource($this->process)) {
            throw new Exception_SystemProcessIO('Failed to execute');
        }
        $this->debug('Execute successful');
        $this->debugStatus();

        $this->pipes['in'] = &$pipes[0];
        $this->pipes['out'] = &$pipes[1];
        $this->pipes['err'] = &$pipes[2];

        return $pipes;  // in case you need more streams redirected
    }
    protected function execute_raw($command)
    {
        return $this->executeRaw($command);
    }

    /**
     * Sends string to process, but process will wait for more input. Always
     * adds newline at the end.
     *
     * @param string $str any input data.
     *
     * @return self
     */
    public function write($str)
    {
        if (!is_resource($this->pipes['in'])) {
            throw new Exception_SystemProcessIO("stdin is closed or haven't been opened. Cannot write to process");
        }
        $this->debug('writing '.$str.'+newline into stdin');
        fwrite($this->pipes['in'], $str."\n");
        $this->debugStatus();

        return $this;
    }

    /**
     * Similar to write but will send EOF after sending text.
     * Also makes sure your list do not end with endline (because write
     * adds it).
     *
     * @param string $str any input data.
     *
     * @return self
     */
    public function writeAll($str)
    {
        if (substr($str, -1) == "\n") {
            $str = substr($str, 0, -1);
        }
        $this->write($str);
        $this->close('in');
        $this->debugStatus();

        return $this;
    }
    public function write_all($str)
    {
        return $this->writeAll($str);
    }

    /**
     * Reads one line of output. Careful - if no output is provided it
     * this function  will be waiting.
     *
     * @param string $res optional descriptor (either out or err)
     *
     * @return self
     */
    public function readLine($res = 'out')
    {
        $str = fgets($this->pipes[$res]);
        if (substr($str, -1) == "\n") {
            $str = substr($str, 0, -1);
        }

        return $str;
    }
    public function read_line($res = 'out')
    {
        return $this->returnLine($res);
    }

    /**
     * Reads all output and returns. Closes stdout when EOF reached.
     *
     * @param string $res optional descriptor (out or err)
     *
     * @return self
     */
    public function readAll($res = 'out')
    {
        $str = '';
        $this->debugStatus();
        $this->debug('reading all output');
        //stream_set_blocking($this->pipes[$res],0);
        $str = stream_get_contents($this->pipes[$res]);
        $this->close($res);
        if (substr($str, -1) != "\n") {
            $str .= "\n";
        }

        return $str;
    }
    public function read_all($res = 'out')
    {
        return $this->readAll();
    }

    public function readStdErr()
    {
        return $this->read_all('err');
    }
    public function read_stderr()
    {
        return $this->readStdErr();
    }

    /**
     * Closing IO and terminating.
     *
     * @param int $sig Unix signal
     */
    public function terminate($sig = null)
    {
        // Terminates application without reading anything more.
        foreach ($this->pipes as $key => $res) {
            $this->close($key);
        }
        $this->debug('process terminated');
        proc_terminate($this->process, $sig);
    }

    /**
     * This function will finish reading from in/err streams
     * and will close all streams. If you are doing your
     * own reading line-by-line or you want to terminate
     * application without reading all of it's output -
     * use terminate() instead;.
     */
    public function close($res = null)
    {
        if (is_null($res)) {
            $this->debug('closing ALL streams, starting with IN');
            $this->close('in');

            $this->debug('Reading all data from OUT');
            // Read remaining of stdout if not read
            if (!feof($this->pipes['out'])) {
                $out = $this->read_all();
            }
            $out = $this->close('out');

            // Read stderr if anything is left there
            $this->stderr .= $this->read_stderr();
            $this->close('err');

            return $out;
        } else {
            $this->debug('Closing '.$res);
            if (is_resource($this->pipes[$res])) {
                fclose($this->pipes[$res]);
                $this->pipes[$res] = null;
                $this->debugStatus();
            }
        }
        //$this->exit_code = proc_close($this->process);
    }

    /**
     * Set "nice" value for the process.
     *
     * @param [type] $nice Nice level 0 .. 20
     */
    public function nice($nice)
    {
        if (function_exists('proc_nice')) {
            proc_nice($this->process, $nice);
        }
    }
}
