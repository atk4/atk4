<?php
/**
 * Undocumented.
 */
class Exception_Installer extends BaseException
{
    public function getSolution()
    {
        return array('Restart installer' => dirname(dirname($this->app->pm->base_path)).'?step=login');
    }
}
