<?php
/***********************************************************
  ..

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
/*
 * Created on 10.04.2006 by *Camper*
 */
class SQLException extends BaseException { // used if DBlite error is occured
    function __construct($last_query='', $message=null, $func=null,$shift=1){
        $last_query=htmlentities($last_query);
        $mysql_error = mysql_error();

        $cause = preg_replace('/.*near \'(.*)\' at line .*/','\1',$mysql_error);
        if($cause!=$mysql_error){
            $last_query=str_replace($cause,"<font color=blue><b>".$cause."</b></font>",$last_query);
        }
        list($message)=explode('select',$message);
        $msg = '<p>'.$message.'</p>';

        if ($mysql_error)
            $msg .= ($last_query == ''?"":"<b>Last query:</b> <div style='border: 1px solid black'>".$last_query."</div>")
                ."<b>MySQL error:</b> <div style='border: 1px solid black'><font color=red>".$mysql_error."</font></div>"
                //."</div><small><address>DBlite v".$this->version."</address></small>\n"
                ;
        parent::__construct($msg, $func, $shift);
    }

    function getHTML($message=null){
        $html='';
        $html.= '<h2>'.get_class($this).(isset($message)?': '.$message:'').'</h2>';
        $html.= '<p>' . $this->getMessage() . '</p>';
        $html.= '<p><font color=blue>' . $this->getMyFile() . ':' . $this->getMyLine() . '</font></p>';
        $html.=$this->getDetailedHTML();
        $html.= backtrace($this->shift+1,$this->getMyTrace());
        return $html;
    }
}
