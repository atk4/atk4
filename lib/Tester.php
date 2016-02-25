<?php
/**
 * Tester implements a test-buddy class, which is passed to all your test
 * methods. You can use some of the methods to interract, output data or
 * debug information from outside of your test. This output will only
 * be displayed if any of the assertions fail.
 */
class Tester extends AbstractObject
{
    /**
     * Contains model with test results.
     */
    public $results = null;

    /**
     * If set, it will run only a single test, useful for
     * isolated testing.
     */
    public $single_test_only = null;

    public function init()
    {
        parent::init();

        $m = $this->results = $this->add('Model');

        $m->addField('name');
        $m->addField('is_success')->type('boolean');
        $m->addField('exception')->type('object');
        $m->addField('time')->type('float');
        $m->addField('memory')->type('int');
        $m->addField('ticks')->type('int');
        $m->addField('result')->type('object');
        $m->addfield('debug')->type('text');

        $a = array();
        $m->setSource('Array', $a);
    }

    public function prepareForTest()
    {
        // nothnig
    }

    public function skip()
    {
        throw new Exception_SkipTests();
    }

    private $_assert_options;

    /**
     * Configures PHP assert() method to use internal callback for error
     * reporting.
     */
    public function enableAsserts()
    {
        $this->_assert_options = assert_options();
    }

    /**
     * Restores previous setting of assert() method.
     */
    public function disableAsserts()
    {
        assert_options($this->_assert_options);
    }

    /**
     * Sets the state of your application.
     */
    public function setState($state)
    {
    }

    /**
     * Verifies that the state of your application matches selected.
     */
    public function verifyState($state)
    {
    }

    /**
     * Outputs some debug information, which is cached until the end
     * of current test. If any of the assertions fail, this will also
     * output debug information.
     */
    public function debug($text)
    {
        $this->results['debug'] =
        $this->results['debug'].$text."\n";
    }

    /**
     * Force output of the text which will appear while the tests are
     * being executed. This is useful if your test takes some time to
     * perform and you would want to have some output on the console.
     */
    public function info($text)
    {
    }

    /**
     * Similar to a regular Agile Toolkit add method, however will
     * will return a "wrapper" object of a specified class. This
     * wrapper will automatically intercept all method calls of a
     * host object and send it to $debug.
     */
    public function _add($class)
    {
    }
}
