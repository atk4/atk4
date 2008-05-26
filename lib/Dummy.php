<?php
/**
 * Simple class which does not do anything
 * Any call to any method of this object returns itself.
 * Useful for access control implementation
 * 
 * Created by *Camper* on 21.05.2008
 */
class Dummy extends AbstractObject{
	public function __call($function, $args){
		// we should not do anything
		return $this;
	}
}