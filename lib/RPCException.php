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
class RPCException extends BaseException {
	public $fileRPC;
	public $lineRPC;
	function __construct($msg,$code=0,$fileRPC=null,$lineRPC=null){
		parent::__construct($msg,$code);
		if(!is_null($fileRPC))
			$this->fileRPC=$fileRPC;
		else
			$this->fileRPC=$this->getFile();

		if(!is_null($lineRPC))
			$this->lineRPC=$lineRPC;
		else
			$this->lineRPC=$this->getLine();
		return;
	}
	function getMyFile(){ return $this->fileRPC; }
	function getMyLine(){ return $this->lineRPC; }
}
