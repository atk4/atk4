<?php
class Exception_Installer extends BaseException {
    function getSolution()
    {
        return ['Restart installer',dirname(dirname($this->app->pm->base_path)).'?step=login'];
    }

}
