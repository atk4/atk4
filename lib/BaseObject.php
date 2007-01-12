<?php
trigger_error("BaseObject should not be used anymore. Use one of AbstractView, AbstractModel or AbstractController. Called from ".caller_lookup(3,true));
exit;
// Redefine this class if you want to globaly introduce some functionality for
// AbstractObject
class BaseObject extends AbstractView {
}
?>
