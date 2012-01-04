<?php
class DB_dsql_pgsql extends DB_dsql {
    public $bt='"';

    /** Use extended postgresql syntax for fetching ID */
    function insert(){
        parent::insert();
        $this->template.=" returning id";
        return $this;
    }
    function render_limit(){
        if($this->args['limit']){
            return 'limit '.
                (int)$this->args['limit']['cnt'].
                ' offset '.
                (int)$this->args['limit']['shift'];
        }
    }
}
