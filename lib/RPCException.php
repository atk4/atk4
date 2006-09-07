<?
class RPCException extends BaseException {
}


class OldRPCExeption extends RPCException  {
	public $fileRPC;
	public $lineRPC;
	function __construct($msg,$func=null,$shift=1,$code=0,$fileRPC=null,$lineRPC=null){
        parent::__construct($msg,$func=null,$shift=1,$code=0);
        if(!is_null($fileRPC)) $this->fileRPC;
        if(!is_null($lineRPC)) $this->lineRPC;
        return; 
    }
    function getMyFile(){ return $this->fileRPC; }
    function getMyLine(){ return $this->lineRPC; }
}