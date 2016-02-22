<?php
/**
 * Contains static functions. Agile Toolkit does not generally use
 * static functions, so please do not use any functions here.
 */
// @codingStandardsIgnoreStart

// Let's see if static is already loaded. It's a common problem when
// We using old-style 'include.php' which includes both agile toolkit and
// Composer
if (defined('AGILE_TOOLKIT_STATIC_LOADED')) {
    trigger_error('Please include either "atk4/loader.php" or "vendor/autoload.php" and only once.');
}
define('AGILE_TOOLKIT_STATIC_LOADED', true);

// @codingStandardsIgnoreStart
if (!defined('undefined')) {
    define('undefined', '_atk4_undefined_value');
}
// @codingStandardsIgnoreEnd
if (!defined('UNDEFINED')) {
    define('UNDEFINED', '_atk4_undefined_value');
}

if (!function_exists('error_handler')) {
    function error_handler($errno, $errstr, $errfile, $errline)
    {
        $errorType = array(
                E_ERROR => 'Error',             // 1
                E_WARNING => 'Warning',           // 2
                E_PARSE => 'Parsing Error',     // 4
                E_NOTICE => 'Notice',            // 8
                E_CORE_ERROR => 'Core Error',        // 16
                E_CORE_WARNING => 'Core Warning',      // 32
                E_COMPILE_ERROR => 'Compile Error',     // 64
                E_COMPILE_WARNING => 'Compile Warning',   // 128
                E_USER_ERROR => 'User Error',        // 256
                E_USER_WARNING => 'User Warning',      // 512
                E_USER_NOTICE => 'User Notice',       // 1024
                E_STRICT => 'PHP suggestions',   // 2048
                E_RECOVERABLE_ERROR => 'Runtime Notice',    // 4096
                E_DEPRECATED => 'Deprecated Notice', // 8192
                E_USER_DEPRECATED => 'User Deprecated Notice', // 16384
                E_ALL => 'All Errors',        // 32767
                );

        if ((error_reporting() & $errno) != 0) {
            $errfile = dirname($errfile).'/<b>'.basename($errfile).'</b>';
            $str = "<font style='font-family: verdana;  font-size:10px'><font color=blue>$errfile:$errline</font> ".
                "<font color=red>[$errno] <b>$errstr</b></font></font>";

            switch ($errno) {
                case 2: // E_WARNING
                    if (strpos($errstr, 'mysql_connect') !== false) {
                        break;
                    }
                    // no-break
                case 8: // E_NOTICE
                    if (strpos($errstr, 'Undefined offset') === 0) {
                        break;
                    }
                    if (strpos($errstr, 'Undefined index') === 0) {
                        break;
                    }
                    // no-break
                case 2048: // E_STRICT
                    if (strpos($errstr, 'var: Deprecated') === 0) {
                        break;
                    }
                    if (strpos($errstr, 'Declaration of ') === 0) {
                        break;
                    }
                    if (strpos($errstr, 'Non-static method') === 0) {
                        break;
                    }
                    // no-break
                case 8192: // E_DEPRECATED
                    if (substr($errstr, -13) == 'is deprecated') {
                        break;
                    }
                    // no-break
                default:
                    // throw consistently all errors into exceptions
                    throw new ErrorException(
                        $errorType[$errno].': ['.$errfile.':'.$errline.']'.$errstr,
                        0,
                        $errno,
                        $errfile,
                        $errline
                    );
            }
        }
    }
    set_error_handler('error_handler');
};

// From: https://raw.github.com/ircmaxell/password_compat/master/lib/password.php
/*
 * A Compatibility library with PHP 5.5's simplified password hashing API.
 *
 * @author Anthony Ferrara <ircmaxell@php.net>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @copyright 2012 The Authors
 */

if (!defined('PASSWORD_BCRYPT')) {
    define('PASSWORD_BCRYPT', 1);
    define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

    /**
     * Hash the password using the specified algorithm.
     *
     * @param string $password The password to hash
     * @param int    $algo     The algorithm to use (Defined by PASSWORD_* constants)
     * @param array  $options  The options for the algorithm to use
     *
     * @return string|false The hashed password, or false on error.
     */
    function password_hash($password, $algo, array $options = array())
    {
        if (!function_exists('crypt')) {
            trigger_error(
                'Crypt must be loaded for password_hash to function',
                E_USER_WARNING
            );
            return;
        }
        if (!is_string($password)) {
            trigger_error(
                'password_hash(): Password must be a string',
                E_USER_WARNING
            );
            return;
        }
        if (!is_int($algo)) {
            trigger_error(
                'password_hash() expects parameter 2 to be long, '.gettype($algo).' given',
                E_USER_WARNING
            );
            return;
        }
        switch ($algo) {
            case PASSWORD_BCRYPT:
                // Note that this is a C constant, but not exposed to PHP, so we don't define it here.
                $cost = 10;
                if (isset($options['cost'])) {
                    $cost = $options['cost'];
                    if ($cost < 4 || $cost > 31) {
                        trigger_error(
                            sprintf('password_hash(): Invalid bcrypt cost parameter specified: %d', $cost),
                            E_USER_WARNING
                        );
                        return;
                    }
                }
                $required_salt_len = 22;
                $hash_format = sprintf('$2y$%02d$', $cost);
                break;
            default:
                trigger_error(
                    sprintf('password_hash(): Unknown password hashing algorithm: %s', $algo),
                    E_USER_WARNING
                );
                return;
        }
        if (isset($options['salt'])) {
            switch (gettype($options['salt'])) {
                case 'NULL':
                case 'boolean':
                case 'integer':
                case 'double':
                case 'string':
                    $salt = (string) $options['salt'];
                    break;
                case 'object':
                    if (method_exists($options['salt'], '__tostring')) {
                        $salt = (string) $options['salt'];
                        break;
                    }
                    // no-break
                case 'array':
                case 'resource':
                default:
                    trigger_error('password_hash(): Non-string salt parameter supplied', E_USER_WARNING);

                    return;
            }
            if (strlen($salt) < $required_salt_len) {
                trigger_error(
                    sprintf(
                        'password_hash(): Provided salt is too short: %d expecting %d',
                        strlen($salt),
                        $required_salt_len
                    ),
                    E_USER_WARNING
                );
                return;
            } elseif (0 == preg_match('#^[a-zA-Z0-9./]+$#D', $salt)) {
                $salt = str_replace('+', '.', base64_encode($salt));
            }
        } else {
            $buffer = '';
            $raw_length = (int) ($required_salt_len * 3 / 4 + 1);
            $buffer_valid = false;
            if (function_exists('mcrypt_create_iv')) {
                $buffer = mcrypt_create_iv($raw_length, MCRYPT_DEV_URANDOM);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes')) {
                $buffer = openssl_random_pseudo_bytes($raw_length);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }
            if (!$buffer_valid && file_exists('/dev/urandom')) {
                $f = @fopen('/dev/urandom', 'r');
                if ($f) {
                    $read = strlen($buffer);
                    while ($read < $raw_length) {
                        $buffer .= fread($f, $raw_length - $read);
                        $read = strlen($buffer);
                    }
                    fclose($f);
                    if ($read >= $raw_length) {
                        $buffer_valid = true;
                    }
                }
            }
            if (!$buffer_valid || strlen($buffer) < $raw_length) {
                $bl = strlen($buffer);
                for ($i = 0; $i < $raw_length; ++$i) {
                    if ($i < $bl) {
                        $buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
                    } else {
                        $buffer .= chr(mt_rand(0, 255));
                    }
                }
            }
            $salt = str_replace('+', '.', base64_encode($buffer));
        }
        $salt = substr($salt, 0, $required_salt_len);

        $hash = $hash_format.$salt;

        $ret = crypt($password, $hash);

        if (!is_string($ret) || strlen($ret) <= 13) {
            return false;
        }

        return $ret;
    }

    /**
     * Get information about the password hash. Returns an array of the information
     * that was used to generate the password hash.
     *
     * array(
     *    'algo' => 1,
     *    'algoName' => 'bcrypt',
     *    'options' => array(
     *        'cost' => 10,
     *    ),
     * )
     *
     * @param string $hash The password hash to extract info from
     *
     * @return array The array of information about the hash.
     */
    function password_get_info($hash)
    {
        $return = array(
            'algo' => 0,
            'algoName' => 'unknown',
            'options' => array(),
        );
        if (substr($hash, 0, 4) == '$2y$' && strlen($hash) == 60) {
            $return['algo'] = PASSWORD_BCRYPT;
            $return['algoName'] = 'bcrypt';
            list($cost) = sscanf($hash, '$2y$%d$');
            $return['options']['cost'] = $cost;
        }

        return $return;
    }

    /**
     * Determine if the password hash needs to be rehashed according to the options provided.
     *
     * If the answer is true, after validating the password using password_verify, rehash it.
     *
     * @param string $hash    The hash to test
     * @param int    $algo    The algorithm used for new password hashes
     * @param array  $options The options array passed to password_hash
     *
     * @return bool True if the password needs to be rehashed.
     */
    function password_needs_rehash($hash, $algo, array $options = array())
    {
        $info = password_get_info($hash);
        if ($info['algo'] != $algo) {
            return true;
        }
        switch ($algo) {
            case PASSWORD_BCRYPT:
                $cost = isset($options['cost']) ? $options['cost'] : 10;
                if ($cost != $info['options']['cost']) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Verify a password against a hash using a timing attack resistant approach.
     *
     * @param string $password The password to verify
     * @param string $hash     The hash to verify against
     *
     * @return bool If the password matches the hash
     */
    function password_verify($password, $hash)
    {
        if (!function_exists('crypt')) {
            trigger_error('Crypt must be loaded for password_verify to function', E_USER_WARNING);

            return false;
        }
        $ret = crypt($password, $hash);
        if (!is_string($ret) || strlen($ret) != strlen($hash) || strlen($ret) <= 13) {
            return false;
        }

        $status = 0;
        for ($i = 0; $i < strlen($ret); ++$i) {
            $status |= (ord($ret[$i]) ^ ord($hash[$i]));
        }

        return $status === 0;
    }
}
// @codingStandardsIgnoreEnd
