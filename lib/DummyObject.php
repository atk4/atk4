<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
/**
 * Simple class which does not do anything
 * Any call to any method of this object returns itself.
 * Useful for access control implementation
 * 
 * Created by *Camper* on 21.05.2008
 */
class DummyObject extends AbstractObject{
	public function __call($function, $args){
		// we should not do anything
		return $this;
	}
}
