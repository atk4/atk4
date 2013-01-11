<?php
/***********************************************************
  Implementation of Generic Tabs. Use class "View_jUITabs"
  or better yet - simply "Tabs" instead.

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
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
