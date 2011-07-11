<?php
class Exception_DB extends BaseException {
    function addPDOException($e){
        return $this->addMoreInfo('pdo_error',$e->getMessage());
    }
}
