<?php
/**
 * Stops initialisation process. For example if we are sure than no more objects needs to be added
 *  on the page.
 */
class Exception_StopInit extends BaseException{
	function __construct(){
		parent::__construct('This exception must be ignored in API');
	}
}
