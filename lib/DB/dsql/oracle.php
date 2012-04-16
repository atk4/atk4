<?php
class DB_dsql_oracle extends DB_dsql {
    function limit($cnt,$shift=0){
        $cnt+=$shift;
        $this->where('NUM_ROWS>=',$shift);
        $this->where('NUM_ROWS<',$cnt);
        return $this;
    }
    function render_limit(){
        return '';
    }
}
