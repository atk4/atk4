<?php
/**
 * PathFinder_Exception is generated when some files are requested but are
 * not found in their designated place.
 *
 * @obsolete Avoid using this, use Exception_PathFinder instead
 */
class PathFinder_Exception extends BaseException
{
    /**
     * {inheritdoc}
     */
    function __construct($type, $filename, $attempted_locations, $message=null)
    {
        parent::__construct("Unable to include file");
        $this->addMoreInfo('file',$filename);
        if ($message) {
            $this->addMoreInfo('message',$message);
        $this->addMoreInfo('type',$type);
        $this->addMoreInfo('attempted_locations',$attempted_locations);
    }
}
