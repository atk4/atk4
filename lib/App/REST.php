<?php
/**
 * REST Server implementation for Agile Toolkit.
 *
 * This class takes advantage of the tight integration for Agile Toolkit
 * to enhance and make it super simple to create an awesome API for
 * your existing application.
 */
// @codingStandardsIgnoreStart because REST is acronym
class App_REST extends App_CLI
{
    // @codingStandardsIgnoreEnd

    public $doc_page = 'app/rest';

    public $page;

    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();

        try {
            // Extra 24-hour protection
            parent::init();

            $this->logger = $this->add('Logger');
            $this->add('Controller_PageManager')
                ->parseRequestedURL();

            // It's recommended that you use versioning inside your API,
            // for example http://api.example.com/v1/user
            //
            // This way version is accessible anywhere from $this->app->version
            list($this->version, $junk) = explode('_', $this->page, 2);

            // Add-ons may define additional endpoints for your API, but
            // you must activate them explicitly.
            $this->pathfinder->base_location->defineContents(array('endpoint' => 'endpoint'));
        } catch (Exception $e) {
            $this->caughtException($e);
        }
    }

    /**
     * Output will be properly fromatted.
     *
     * @param mixed $data
     */
    public function encodeOutput($data)
    {
        // TODO - use HTTP_ACCEPT here ?
        //var_dump($_SERVER['HTTP_ACCEPT']);

        if ($_GET['format'] == 'xml') {
            throw $this->exception('only JSON format is supported', null, 406);
        } elseif ($_GET['format'] == 'json_pretty') {
            header('Content-type: application/json');
            echo json_encode($data, JSON_PRETTY_PRINT);
        } elseif ($_GET['format'] == 'html') {
            echo '<pre>';
            echo json_encode($data, JSON_PRETTY_PRINT);
        } elseif ($data === null) {
            header('Content-type: application/json');
            echo json_encode(array());
        }
    }

    /**
     * Main.
     */
    public function main()
    {
        $this->execute();
        $this->hook('saveDelayedModels');
    }

    /**
     * Execute.
     */
    public function execute()
    {
        try {
            try {
                $file = $this->app->locatePath('endpoint', str_replace('_', '/', $this->page).'.php');
                include_once $file;

                $this->pm->base_path = '/';
            } catch (Exception $e) {
                http_response_code(500);
                if ($e instanceof Exception_Pathfinder) {
                    $error = array(
                        'error' => 'No such endpoint',
                        'type' => 'API_Error',
                        );
                } else {
                    $error = array(
                        'error' => 'Problem with endpoint',
                        'type' => 'API_Error',
                        );
                }
                $this->caughtException($e);
                $this->encodeOutput($error, null);

                return;
            }

            try {
                $class = 'endpoint_'.$this->page;
                $this->endpoint = $this->add($class);
                $this->endpoint->app = $this;
                $this->endpoint->api = $this->endpoint->app; // compatibility with ATK 4.2 and lower

                $method = strtolower($_SERVER['REQUEST_METHOD']);
                $raw_post = file_get_contents('php://input');

                if ($raw_post && $raw_post[0] == '{') {
                    $args = json_decode($raw_post, true);
                } elseif ($method == 'put') {
                    parse_str($raw_post, $args);
                } else {
                    $args = $_POST;
                }

                if ($_GET['method']) {
                    $method .= '_'.$_GET['method'];
                }
                if (!$this->endpoint->hasMethod($method)) {
                    throw $this->exception('Method does not exist for this endpoint', null, 404)
                        ->addMoreInfo('method', $method)
                        ->addMoreInfo('endpoint', $this->endpoint)
                        ;
                }

                $this->logRequest($method, $args);

                // Perform the desired action
                $this->encodeOutput($this->endpoint->$method($args));

                $this->logSuccess();
            } catch (Exception $e) {
                $this->caughtException($e);
                http_response_code($e->getCode() ?: 500);

                $error = array(
                    'error' => $e->getMessage(),
                    'type' => get_class($e),
                    'more_info' => $e instanceof BaseException ? $e->more_info : null,
                    );
                array_walk_recursive($error, function (&$item, $key) {
                    if (is_object($item)) {
                        $item = (string) $item;
                    }
                });
                $this->encodeOutput($error, null);
            }
        } catch (Exception $e) {
            $this->caughtException($e);
        }
    }

    /**
     * Override to extend the logging.
     *
     * @param [type] $method [description]
     * @param [type] $args   [description]
     *
     * @return [type] [description]
     */
    public function logRequest($method, $args)
    {
    }

    /**
     * Overwrite to extend the logging.
     *
     * @return [type] [description]
     */
    public function logSuccess()
    {
    }
}
