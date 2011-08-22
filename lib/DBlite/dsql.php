<?php
/***********************************************************
  ..

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

if (!defined('DTP')) define('DTP','');

class DBlite_dsql  {
    var $db;

    var $my=array(null,null,null);
    var $saved=array(null,null,null);
    /*
     * Array containing arguments
     */
    var $args;
    var $debug;

    function __call($function,$args){
        /*
         * This call wrapper implements the following function:
         */
        if(substr($function,0,3)=='do_'){
            // do_select, do_insert combine functionality of generating insert/select and executing it
            $fnname = substr($function,3);
            $query = call_user_func_array(array($this,$fnname),$args);
            if(substr($function,3,3)=='get'){
                // we need those values saved, before we run our query
                $this->db->fatal("I do not know how to handle this function call :((((");
            }
            return $this->query($query);
        }
        if(substr($function,0,3)=='get'){
            $tmp=array($this->db->cursor,$this->db->found_rows);
            $this->db->cursor=$this->cursor;
            $this->db->found_rows=$this->found_rows;
            $result = call_user_func_array(array($this->db,$function),$args);
            list($this->db->cursor,$this->db->found_rows)=$tmp;
            return $result;
        }
        return $this->db->fatal("Call to undefined DQ::$function");
    }

    function fatal($msg,$lev=null){
        $this->db->fatal($msg,$lev+4);
    }

    function foundRows(){
        return $this->my[1];
    }
    ////// Speed-access functions
    function s(){
        /*
         * We are willing to preserve existing state of associated database.
         * That's why we save some of it's data in a local valiable, and
         * restore those after execution.
         */
        $this->saved=array($this->db->cursor,$this->db->found_rows,$this->db->calc_found_rows);
        list($this->db->cursor,$this->db->found_rows,$this->db->calc_found_rows) = $this->my;
    }
    function l($a=null){
        $this->my=array($this->db->cursor,$this->db->found_rows,$this->db->calc_found_rows);
        list($this->db->cursor,$this->db->found_rows,$this->db->calc_found_rows) = $this->saved;
        return $a;
    }
    function do_getHash($f=null){
        $this->s();
        try {
            return $this->l($this->db->getHash($this->select(),$f));
        }catch (SQLException $e){
            $this->debug()->select();
            throw $e;
        }
    }
    function do_getAll($f=null){
        $this->s();
        return $this->l($this->db->getAll($this->select(),$f));
    }
    function do_getAllHash($f=null){
        $this->s();
        return $this->l($this->db->getAllHash($this->select(),$f));
    }
    function do_getRow($f=null){
        $this->s();
        return $this->l($this->db->getRow($this->select(),$f));
    }
    function do_getOne(){
        $this->s();
        return $this->l($this->db->getOne($this->select()));
    }
    function do_getAssoc(){
        $this->s();
        return $this->l($this->db->getAssoc($this->select()));
    }
    function do_getColumn(){
        $this->s();
        return $this->l($this->db->getColumn($this->select()));
    }
    function do_select(){
        $this->s();
        $r=$this->db->query($this->select());
        return $this->l($this);
    }
    /*
     * temporary disabled, those do not work as they should
     function do_select(){ return $this->query($this->select()); }
     function do_delete(){ return $this->query($this->select()); }
     */

    function do_delete(){
        return $this->db->query($this->delete());
    }


    function do_insert(){ $this->__call('do_insert',array()); return $this->db->lastID(); }
    function do_replace(){ $this->__call('do_replace',array()); return $this->db->lastID(); }
    //function do_select(){ $this->__call('do_select',array()); return $this; }
    function query($str){
        $this->s();
        return $this->l($this->db->query($str));
    }
    function do_fetchRow($l=null){
        $this->s();
        return $this->l($this->db->fetchRow($l));
    }
    function do_fetchHash($l=null){
        $this->s();
        return $this->l($this->db->fetchHash($l));
    }

    function debug(){
        $this->debug=1;
        return $this;
    }
    /*
       function query($q,$param1=null){
       $this->db->query($q,$param1);
       $this->cursor=$this->db->cursor;
       $this->found_rows=$this->db->found_rows;
       }
     */

    ///////////////////// Dynamic SQL functions ////////////////
    function table($table){
        /*
         * Specify table for a query
         */
        $this->args['table']=DTP.$table;
        return $this;
    }
    function field($field,$table=null) {
        /*
         * Add new field to a query
         */
        if(isset($table)){
            $field=DTP.$table.'.'.$field;
        }
        if(!isset($this->args['fields']))$this->args['fields']=array();
        if(is_array($field)){
            $this->args['fields']=array_merge($this->args['fields'],$field);
        }else{
            $this->args['fields'][]=$field;
        }
        return $this;
    }
    function set($set,$val=array()) {
        /*
         * Set value for update. You can use this function in a several ways. First
         * of all you can just simply call:
         *  $this->set($field, $value);
         * which will result update of the field in a next query. 2nd form is
         * when you call
         *  $this->set($hash);
         * in which case all keys of hash will be set to apropritate values.
         *
         * Value will be quoted, if you want to avoid that - use one-argument form.
         *
         * You can use this with array too like:
         *  set(array(
         *   'a'=>'213',
         *   'b'=>'foobar',
         *   'password=password("foo")'     // one-argument way
         *   ));
         */
        if(is_array($set)){
            foreach($set as $_key=>$_val){
                if(is_numeric($_key)){
                    $this->set($_val);
                }elseif(is_null($_val)){
                    continue;
                }else{
                    $this->set($_key,$_val);
                }
            }
        }else{
            if($val===array()){
                if($set===null)return $this;
                // if 1 argument is specified and is not array, then use it
                // as-is
                $this->args['set'][]=$set;
            }else
                $this->args['set'][$set]=$this->escapeValue($val);
        }
        return $this;
    }

    /**
     * Support external access to args property
     * @param string $arg_type
     * @return array
     */
    public function getArgsList($args_type) {
        return isset($this->args[$args_type])?array_keys($this->args[$args_type]):array();
    }

    /**
     * Escape value for protect SQL injection and support complex strings
     * @param mixed $val
     * @return string
     */
    protected function escapeValue($val) {
        if(is_null($val)){
            $res = 'NULL';
        }else{
            // numeric values MUST be without quotas for the correct rounding
            if( (string)(float)$val === (string)$val)
                $res = $val;
            else
                $res = "'".$this->db->escape($val)."'";
        }

        return $res;
    }

    public function call_sql_function($function_name, $params) {
        //TODO: Complete this
    }

    function setDate($field='ts',$value=null){
        /**
         * Accepts any date format
         */
        if(is_null($value))$value=time();
        elseif(is_string($value))$value=strtotime($value);
        return $this->set($field,date('Y-m-d H:i:s',$value));
    }
    function where($where,$equals=false,$escape=true,$cond='where'){
        // Argument 3 only applies on cases when you are using "in" clause.
        // If you plan to pass sub-queries - use false
        if(!is_array($where)){
            if($equals!==false){
                if(is_null($equals)){
                    $where.=" is null";
                }else{
                    if(substr($where,-1,1)==' ')$where=substr($where,0,-1);
                    // let's see if there is a sign, so we don't put there anything
                    $c=substr($where,-1,1);
                    if($c=='<' || $c=='>' || $c=='='){
                        // no need to add sign, it's already there
                        $where.=" '".$this->db->escape($equals)."'";
                    }elseif(substr($where,-5,5)==' like'){
                        $where.=" '".$this->db->escape($equals)."'";
                    }elseif(substr($where,-3,3)==' in'){
                        if($escape){
                            if(is_string($equals) && strtolower(substr($equals,0,6))=='select'){
                                throw new BaseException("use 3rd argument if you pass sub-queries to where()");
                            }
                            if(is_array($equals)){
                                $eq=$equals;
                            }else{
                                $eq=explode(',',$equals);
                            }
                            $eq2=array();
                            foreach($eq as $eq3){
                                $eq2[]="'".$this->db->escape($eq3)."'";
                            }
                            $equals=join(',',$eq2);
                        }
                        $where.=" ($equals)";
                    }else{
                        if(is_array($equals)){
                            // adding all conditions as OR clause
                            $w=array();
                            foreach($equals as $e){
                                $w[]=$where." = '".$this->db->escape($e)."'";
                            }
                            $where=join(' OR ',$w);
                        }else
                            $where.=" = '".$this->db->escape($equals)."'";
                    }
                }
            }
            $where = array($where);
        }
        if(!isset($this->args[$cond]))$this->args[$cond]=array();
        $this->args[$cond] = array_merge($this->args[$cond], $where);
        return $this;
    }
    function clear_args($arg_name){
        unset($this->args[$arg_name]);
        return $this;
    }
    function having($having,$equals=false,$escape=true){
        return $this->where($having,$equals,$escape,'having');
    }
    function join ($table,$on,$type='inner'){
        $this->args['join'][$table]="$type join ".DTP.$table." on $on";
        return $this;
    }
    function order($order,$desc=null,$prepend=null){
        if(!$order)throw new SQLException("Empty order provided");
        $field=$order;
        if($desc)$order.=" desc";
        if($prepend && isset($this->args['order'])){
            array_unshift($this->args['order'], $order);
        }else{
            // existing ordering must be overwritten
            if(($index=$this->isArgSet('order',$field))!==false)unset($this->args['order'][$index]);
            $this->args['order'][]=$order;
        }
        return $this;
    }
    /**
     * Returns true if argument $option has been set for the $field in this query
     * I.e. isArgSet('where','id') returns true if where('id',$value) was called
     */
    function isArgSet($option,$field){
        if(!isset($this->args[$option]) || empty($this->args[$option]))return false;
        foreach($this->args[$option] as $index=>$arg){
            // option may contain field prefix and suffixes like 'desc'
            if(stripos($arg,$field)!==false)return $index;
        }
        return false;
    }
    function limit($cnt,$shift=0){
        $this->args['limit']=array(
                'cnt'=>$cnt,
                'shift'=>$shift
                );
        return $this;
    }
    function group($group,$prepend=null) {
        /*
         * Set group
         */
        if($prepend){
            array_unshift($this->args['group'], $group);
        }else{
            $this->args['group'][]=$group;
        }
        return $this;
    }
    /**
     * Returns true if specified $value already set for $param
     * E.g. paramExists('group',$field) returns true if grouping by $field was already set
     *
     * May not work for where or having, as they are implemented like shit
     */
    function paramExists($param,$value){
        if(!isset($this->args[$param]))return false;
        return array_search($value,$this->args[$param])!==false;
    }
    function select(){
        return $this->parseTemplate("select [options] [fields] from [table] [join] [where] [group] [having] [order] [limit]");
    }
    function update(){
        return $this->parseTemplate("update [table] set [set] [where]");
    }
    function insert(){
        if(isset($this->args['options']))foreach($this->args['options'] as $index=>$option){
            if($option=="SQL_CALC_FOUND_ROWS")$this->args['options'][$index]='';
        }
        return $this->parseTemplate("insert [options] into [table] ([set_fields]) values ([set_value])");
    }
    function replace(){
        return $this->parseTemplate("replace [options] into [table] ([set_fields]) values ([set_value])");
    }
    function delete(){
        return $this->parseTemplate("delete from [table] [where]");
    }
    function getArgs($required){
        /*
         * This function generates actual value for the arguments, which
         * will be placed into template
         */
        $args=array();
        if(isset($required['fields'])) {
            // comma separated fields, such as for select
            $fields=array();
            if(!is_array($this->args['fields'])){
                $this->fatal('Before generating query you should call $dq->field() at least once, otherwise I do not know what fields you need',2);
            }
            foreach($this->args['fields'] as $field) {
                $fields[]=$field;
            }
            $args['fields']=join(', ', $fields);
        }
        if(isset($required['options'])&&isset($this->args['options'])){
            $args['options']=join(' ',$this->args['options']);
        }

        if(isset($required['set'])) {
            $set = array();
            if(!$this->args['set']) {
                return $this->fatal('You should call $dq->set() before requesting update');
            }
            foreach($this->args['set'] as $key=>$val) {
                if(is_int($key)) {
                    $set[]="$val";
                }else{
                    $set[]="`$key`=$val";
                }
            }
            $args['set']=join(', ', $set);
        }

        if(isset($required['set_fields']) || isset($required['set_value'])) {
            $sf = $sv = array();
            if(!$this->args['set']) {
                return $this->fatal('You should call $dq->set() before requesting update',2);
            }
            foreach($this->args['set'] as $key=>$val) {
                if(is_numeric($key)){
                    list($sf[],$sv[])=explode('=',$val,2);
                    continue;
                }
                $sf[]="`$key`";
                $sv[]=$val;
            }
            $args['set_fields']=join(', ', $sf);
            $args['set_value']=join(', ', $sv);
        }

        if(isset($required['table'])) {
            $args['table']=$this->args['table'];
        }

        if(isset($required['join'])&&isset($this->args['join'])) {
            $args['join']=join(' ', $this->args['join']);
        }

        if(isset($required['where'])&&isset($this->args['where'])) {
            $args['where'] = "where (".join(') and (', $this->args['where']).")";
        }

        if(isset($required['having'])&&isset($this->args['having'])) {
            $args['having'] = "having (".join(') and (', $this->args['having']).")";
        }

        if(isset($required['order'])&&isset($this->args['order'])) {
            $args['order'] = "order by ".join(', ', $this->args['order']);
        }

        if(isset($required['group'])&&isset($this->args['group'])) {
            $args['group'] = "group by ".join(', ',$this->args['group']);
        }

        if(isset($required['limit'])&&isset($this->args['limit'])) {
            $args['limit'] = "limit ".$this->args['limit']['shift'].", ".$this->args['limit']['cnt'];
        }

        return $args;
    }
    function parseTemplate($template) {
        /*
         * When given query template, this method will get required arguments
         * for it and place them inside returning ready to use query.
         */
        $parts = explode('[', $template);
        $required = array();

        // 1st part is not a variable
        $result = array(array_shift($parts));
        foreach($parts as $part) {
            list($keyword, $rest)=explode(']', $part);
            $result[] = array($keyword); $required[$keyword]=true;
            $result[] = $rest;
        }
        // now parts array contains strings and array of string, let's request
        // for required arguments

        $dd='';
        $args = $this->getArgs($required);
        $dd.='<ul class="atk-sqldump">';

        // now when we know all data, let's assemble resulting string
        foreach($result as $key => $part) {
            if(is_array($part)) {
                $p=$part[0];
                if(isset($args[$p])){
                    $result[$key]=$args[$p];

                    if(isset($this->args[$p]) && $a=$this->args[$p]){
                        if($p=='set'){
                            foreach($a as $key=>&$val){
                                $val=$key.'='.$val;
                            }
                        }
                        if(is_array($a)){
                            sort($a);
                            $dd.="<li><b>".$part[0]."</b> <ul><li>"
                                .join('</li><li>',$a).'</li></ul>';
                        }else{
                            $dd.="<li><b>".$part[0]."</b> $a </li>";
                        }
                    }else $dd.="<li>".$args[$p]."</li>";
                }else{
                    $result[$key]=null;
                }
            }elseif($part=trim($part,' ()'))$dd.='<li><b>'.$part.'</b></li>';
        }
        $dd.="</ul>";
        if($this->debug){
            echo '<font color=blue>'.htmlentities(join('',$result)).'</font>'.$dd;
        }
        return join('', $result);
    }
    function calc_found_rows(){
        $this->option("SQL_CALC_FOUND_ROWS");
        $this->my[2]=true;
        return $this;
    }
    function option($option){
        if(!is_array($option))$option=array($option);
        if(!isset($this->args['options']))$this->args['options']=array();
        $this->args['options']=array_merge($this->args['options'],$option);
        return $this;
    }
}
