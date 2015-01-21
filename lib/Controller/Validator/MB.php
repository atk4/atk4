<?php
namespace romaninsh\validation;

/**
 * Validator rules for multi-byte Functions
 *
 * $model->add('Controller_Validator_MB',array(
 *         'encoding'=>'UTF-8'))
 *      ->is();
 *
 * org
 *
 * $model->validator->add('Controller_Validator_MB',array(
 *         'encoding'=>'UTF-8'))
 *      ->is();
 *
 * Q: Why not implement Multi-byte support inside basic validator?
 * A: For the same reason why PHP's strlen() is not supporting multibyte.
 */
class Controller_Validator_MB extends Controller_Validator_Advanced {
    // TODO: Multibyte stuff: refactor to a better place??
    public $encoding='UTF-8';
    public $is_mb = false; // Is the PHP5 multibyte lib available?

    function mb_str_to_lower($a)
    {
        return ($this->is_mb) ? mb_strtolower($a, $this->encoding) : strtolower($a);
    }

    function mb_str_to_upper($a)
    {
        return ($this->is_mb) ? mb_strtoupper($a, $this->encoding) : strtoupper($a);
    }

    function mb_str_to_upper_words($a)
    {
        if ($this->is_mb)
        {
            return mb_convert_case($value, MB_CASE_TITLE, $this->encoding);
        }

        return ucwords(strtolower($value));

    }

    function mb_truncate($a, $len, $append = '...')
    {
        if ($this->is_mb)
        {
            return mb_substr($value, 0, $len, $this->encoding) . $append;
        }

        substr($value, 0, $limit).$end;
    }




    function rule_len($a)
    {
         return mb_strlen($a, $this->encoding);
    }

}
