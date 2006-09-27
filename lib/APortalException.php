<?
class APortalException extends BaseException {
    var $related_obj;
    function __construct($msg,$related_obj=null){
        parent::__construct($msg);
        $this->related_obj = $related_obj;
    }
    function getAdditionalMessage(){
        return "Related object: ID: ".$this->related_obj->id.", Type: ".$this->related_obj->type.", Name: ".$this->related_obj->name;
    }
}
