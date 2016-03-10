<?php
/**
 * Undocumented
 */
class System_ProcessIO_Tester extends AbstractController
{
    public $scripts = array(
            'basic' => array('exec', 'close'),
            'readwrite' => array('exec', 'close', 'read_all', 'write_all'),
            'failure' => array('exec', 'read_stderr'),
            'advanced' => array('exec', 'write', 'read_line', 'terminate'),
            );
    public function test_basic()
    {
        $p = $this->add('System_ProcessIO')
            ->exec('/usr/bin/perl', array('-e', 'Hello|world\n'));

        $out = $p->close();

        $this->expects($out, "Hello|world\n");

        return $p;
    }
    public function test_readwrite()
    {
        $p = $this->add('System_ProcessIO')
            ->exec('/usr/bin/sed', 's/l/r/g')
            ->write_all('Hello world');
        $out = $p->read_all();

        $this->expects($out, 'Herro worrd');

        return $p;
    }
    public function test_failure()
    {
        $error = '';
        try {
            $p = $this->add('System_ProcessIO')
                ->exec('/usr/non/existant/path', 'hello world')
                ->write_all('Hello world');
            $out = $p->close();
        } catch (Exception_SystemProcessIO $e) {
            $error = $e->getMessage();
            $this->more_info('Expected exception caught', $e);
        }

        $this->expects($error);

        return $p;
    }
    public function test_advanced()
    {
        $p = $this->add('System_ProcessIO')
            ->exec('/usr/bin/sed', 's/l/r/g');

        $p->write('coca cola');
        $out = $p->read_line();
        $this->expects($out, 'coca cora');

        $p->write('little love');
        $out = $p->read_line();
        $this->expects($out, 'rittre rove');

        $this->terminate();

        return $p;
    }
}
