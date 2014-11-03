<?php
class App_TestCLI extends App_CLI {

    function run($argv) {
        $existing_tests = $this->getTestList();
        $tests_to_be_executed = $this->getTestsToBeExecuted($existing_tests,$argv);
        $this->executeTests($tests_to_be_executed);
    }

    /**
     * @param $existing_tests
     * @param $argv
     * @return array
     * @throws BaseException
     *
     * Lets you execute not all tests
     * Usage: php text.php list of tests to be executed separated by whitespace
     */
    protected function getTestsToBeExecuted($existing_tests,$argv) {

        array_shift($argv); // remove script name

        //  No command line args? Les't execute all tests.
        if (count($argv) == 0) {
            return $existing_tests;
        }

        $list = array();

        foreach ($argv as $t) {
            $t_full_name = $this->test_function_starts_with . $t;
            if (!in_array($t_full_name,$existing_tests)) {
                throw $this->exception('There is no test with name "'.$t.'"');
            }
            $list[] = $t_full_name;
        }

        return $list;
    }
}
