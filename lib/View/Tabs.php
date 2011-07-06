<?php
/***********************************************************
  Implementation of Generic Tabs. Use class "View_jUITabs"
  or better yet - simply "Tabs" instead.

  Reference:
  http://agiletoolkit.org/doc/ref

 **ATK4*****************************************************
 This file is part of Agile Toolkit 4 
 http://agiletoolkit.org

 (c) 2008-2011 Agile Technologies Ireland Limited
 Distributed under Affero General Public License v3

 If you are using this file in YOUR web software, you
 must make your make source code for YOUR web software
 public.

 See LICENSE.txt for more information

 You can obtain non-public copy of Agile Toolkit 4 at
 http://agiletoolkit.org/commercial

 *****************************************************ATK4**/
class View_Tabs extends View {
    function setController($c){
        parent::setController($c);
        if($this->getController()){
            // add tabs from controller
            $data=$this->getController()->getRows(array('id','name','content'));
            foreach($data as $row){
                $t=$this->addTab($row['id'],$row['name']);
                $t->set($row['content']);
            }
        }
        return $this;
    }
}
