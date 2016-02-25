<?php
/**
 * Undocumented.
 */
class Exception_DB extends BaseException
{
    public function addPDOException($e)
    {
        return $this->addMoreInfo('pdo_error', $e->getMessage());
    }
}
