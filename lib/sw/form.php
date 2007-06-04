<?php
/* class integration tool */
class sw_form extends sw_component {
    function render(){
        if ($this->template){
            $this->template->trySet("form_id", $this->short_name);
        }
        if ($this->fields){
            /* look for values and errors */
            $id = $this->short_name;
            if ($errors = $_SESSION[$id]){
                $prev_gp = unserialize(urldecode($_SESSION[$id . "gp"]));
                unset($_SESSION[$id]);
                unset($_SESSION[$id . "gp"]);
                foreach ($errors as $field => $error_str){
                    $this->template->trySet($field . "_error", $error_str);
                }
                foreach ($prev_gp as $field => $value){
                    $value = $this->form_encode($value);
                    $this->template->trySet($field, $value);
                }
            }
        }
        parent::render();
    }
    function form_encode($str){
        if (get_magic_quotes_gpc()){
            $str = stripslashes($str);
        }
        $str = preg_replace("/(\")/", "&#034;", $str);
        $str = preg_replace("/(\\\)/", "&#092;", $str);
        return $str;
    }
    function is_submitted(){
        $id = $this->short_name;
        if (($_GET["form_id"] == $id) || ($_POST["form_id"] == $id)){
            return true;
        } else {
            return false;
        }
    }
    function store_gp(){
        $_SESSION[$this->short_name . "gp"] = urlencode(serialize($_GET["form_id"]?$_GET:$_POST));
    }

}
