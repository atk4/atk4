<?php // vim:ts=4:sw=4:et:fdm=marker
/***********************************************************
  Implementation of PDO-compatible dynamic queries

  Reference:
  http://agiletoolkit.org/doc/dq

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
class DB_dsql extends AbstractModel implements Iterator {
    /* Array with different type of data */
    public $args=array();

    /* List of PDO parametical arguments for a query */
    public $params=array();

    /* Statement, if query is done */
    public $stmt;

    /* Expression to use when converting to string */
    public $expr=null;

    public $main_table=null;

    public $main_query=null;  // points to main query for subqueries
    public $param_base='a';   // for un-linked subqueries, set a different param_base


    public $default_exception='Exception_DB';

    public $debug=false;


    // {{{ Generic stuff
    function _unique(&$array,$desired=null){
        $desired=preg_replace('/[^a-zA-Z0-9:]/','_',$desired);
        $desired=parent::_unique($array,$desired);
        return $desired;
    }
    function __toString(){
        return $this->toString();

        if($this->expr)return $this->parseTemplate($this->expr);
        return $this->select();
    }
    /* Escapes value by using parameter */
    function escape($val){
        if(is_array($val)){
            $out=array();
            foreach($val as $v){
                $out[]=$this->escape($v);
            }
            return $out;
        }
        $name=':'.$this->param_base;
        $name=$this->_unique($this->used_params,$name);
        $this->used_params[$name]=$val;
        $this->params[$name]=$val;
        return $name;
    }
    function paramBase($param_base){
        $this->param_base=$param_base;
        return $this;
    }
    /* Creates subquery */
    function dsql(){
        $d=$this->owner->dsql();
        $d->used_params = &$this->used_params;

        $d->main_query=$this->main_query?$this->main_query:$this;
        return $d;
    }
    /* Inserting one query into another and merging parameters */
    function consume($dsql){
        if((!$this->main_query && !$dsql->main_query) || ($dsql->main_query != $this && $dsql->main_query !=
                    $this->main_query)){
            // are we using any parameters
            if($dsql->params && $this->params
                    && $dsql->param_base == $this->param_base){
                // ought to have param clash!
                throw $this->exception('Subquery is not cloned from us, is using same param_base for parametrical variables,
                    therefore unable to consume')
                    ->addMoreInfo('our_parambase',$this->param_base)
                    ->addMoreInfo('their_parambase',$dsql->param_base)
                    ->addMoreInfo('our_params',$this->params)
                    ->addMoreInfo('their_params',$dsql->params)
                    ->addMoreInfo('our_expr',$this->expr)
                    ->addMoreInfo('their_expr',$dsql->expr)
                    ;
            }
        }
        $this->params=array_merge($this->params,$dsql->params);
        $ret='('.$dsql->__toString().')';
        return $ret;
    }
    /* Removes definition for argument type */
    function del($param){
        if($param=='limit'){
            unset($this->args['limit']);
            return $this;
        }
		$this->args[$param]=array();
        return $this;
    }
    function init(){
        parent::init();
        $this->del('fields')->del('table')->del('options');
    }
    // }}}

    // {{{ Dynamic Query Definition methods

    /* Sets template for toString() function */
    function expr($expr){
        $c=clone $this;
        return $c->useExpr($expr);
    }
    function useExpr($expr,$params=array()){
        $this->template=$expr;
        $this->params=$params;
        return $this;
    }
    // TODO: move this function
    function getField($fld){
        if($this->main_table===false)throw $this->exception('Cannot use getField() when multiple tables are queried');
        return $this->expr(
            $this->owner->bt($this->main_table).
            '.'.
            $this->owner->bt($fld)
        );

    }
    /** 
     * Specifies which table to use in this dynamic query. You may specify array to perform operation on multiple tables.
     *
     * Examples:
     *  $q->table('user');
     *  $q->table('user','u');
     *  $q->table('user')->table('salary')
     *  $q->table(array('user','salary'));
     *  $q->table(array('u'=>'user','s'=>'salary'));
     *
     * If you specify multiple tables, you still need to make sure to add proper "where" conditions. All the above examples
     * return $q (for chaining)
     *
     * You can also call table without arguments, which will return current table:
     *
     *  echo $q->table();
     *
     * If multiple tables are used, "false" is returned. Return is not quoted. Please avoid using table() without arguments 
     * as more tables may be dynamically added later.
     **/
	function table($table=undefined,$alias=undefined){
        if($table===undefined)return $this->main_table;

        if(is_array($table)){
            foreach($table as $alias=>$t){
                if(is_numeric($alias))$alias=undefined;
                $this->table($t,$alias);
            }
            return $this;
        }

        // main_table tracking allows us to 
        if($this->main_table===null)$this->main_table=$alias===undefined||!$alias?$table:$alias;
        elseif($this->main_table)$this->main_table=false;   // query from multiple tables

        $this->args['table'][]=array($table,$alias);
        return $this;
    }
    /** 
     * Adds new column to resulting select by querying $field. 
     *
     * Examples:
     *  $q->field('name');
     *
     * Second argument specifies table for regular fields
     *  $q->field('name','user');
     *  $q->field('name','user')->field('line1','address');
     *
     * Array as a first argument will specify mulitple fields, same as calling field() multiple times
     *  $q->field(array('name','surname'));
     *
     * Associative array will assume that "key" holds the alias. Value may be object.
     *  $q->field(array('alias'=>'name','alias2'=>surname'));
     *  $q->field(array('alias'=>$q->expr(..), 'alias2'=>$q->dsql()->.. ));
     *
     * You may use array with aliases together with table specifier.
     *  $q->field(array('alias'=>'name','alias2'=>surname'),'user');
     *
     * You can specify $q->expr() for calculated fields. Alias is mandatory.
     *  $q->field( $q->expr('2+2'),'alias');                // must always use alias
     *
     * You can use $q->dsql() for subqueries. Alias is mandatory.
     *  $q->field( $q->dsql()->table('x')... , 'alias');    // must always use alias
     *
     * Use with multiple tables
     */
	function field($field,$table=null,$alias=null) {
        if(is_array($field)){
            foreach($field as $f){
                $this->field($f,$table);
            }
            return $this;
        }

        if(is_object($field)){
            if(!$table)throw $this->exception('Specified expression without alias');
            $field=$this->consume($field);
            if($table)$field.=' as '.$this->owner->bt($table);
        }elseif(isset($table)){
			$field=
                $this->owner->bt($this->owner->table_prefix.$table).
                '.'.
                $this->owner->bt($field);
		}else{
            $field=$this->owner->bt($field);
        }


        $this->args['fields'][]=$field;
		return $this;
	}



    /** Returns template component [table] */
    function get_table(){
        $ret=array();
        foreach($this->args['table'] as $row){
            list($table,$alias)=$row;

            $table=$this->owner->bt($table);


            if($alias!==undefined && $alias)$table.=' '.$this->owner->bt($alias);

            $ret[]=$table;
        }
        return join(', ',$ret);
    }
    /** Returns template component [table_noalias] */
    function get_table_noalias(){
        $ret=array();
        foreach($this->args['table'] as $row){
            list($table,$alias)=$row;

            $table=$this->owner->bt($table);


            $ret[]=$table;
        }
        return join(', ',$ret);

	}
    /* Defines query option */
	function option($option){
		if(!is_array($option))$option=array($option);
		if(!isset($this->args['options']))$this->args['options']=array();
		$this->args['options']=array_merge($this->args['options'],$option);
		return $this;
	}
    function ignore(){
        $this->args['options_insert'][]='ignore';
        return $this;
    }
    /* Check if option was defined */
    function hasOption($option){
        return in_array($option,$this->args['options']);
    }
    /* Limit row result */
	function limit($cnt,$shift=0){
		$this->args['limit']=array(
				'cnt'=>$cnt,
				'shift'=>$shift
				);
		return $this;
	}
    /* Never pass variable as $field! (esp if user can control it) */
    function having($field,$cond=null,$value=null){
        return $this->where($field,$cond,$value,'having');
    }
    function where($field,$cond=undefined,$value=undefined,$type='where'){
        /*
           1. where('id',2);                    // equals
           2. where('id','>',2);                // explicit condition
           3. where(array('id',2),('id',3));    // or
           4. where('id',array(2,3));           // in
           5. where($dsql,4);                   // subquery
           6. where('id',$dsql);                // in subquery
           7. where('id=ord');                  // full statement. avoid
           */
        $ors=array();
        if(is_array($field)) foreach($field as $or){
            if(!is_array($or))throw $this->exception('OR syntax invalid')->addMoreInfo('arg',$field);
            $ors[]=$this->_where($or[0],@$or[1],@$or[2]);
        }else{
            $ors[]=$this->_where($field,$cond,$value);
        }
        $this->args[$type][]=implode(' or ',$ors);
        return $this;
    }
    function _where($field,$cond=undefined,$value=undefined){
        if(is_object($field)){
            if($cond===undefined && $value===undefined){
                $this->args[$cond][]=$field;
                return $this;
            }
        }else{
            if($cond===undefined && $value===undefined){
                return $this->expr($field);
            /*
                throw $this->exception('Use expression syntax with one-argument calls')
                    ->addMoreInfo('arg',$field);
             */
            }
        }


        if($value===undefined){
            $value=$cond;$cond=undefined;
        }

        /* guess condition as it might be in $field */
        if($cond===undefined && !is_object($field)){

            preg_match('/^([^ <>!=]*)([><!=]*|( *(not|is|in|like))*) *$/',$field,$matches);
            $field=$matches[1];
            $cond=$matches[2];
        }

        if(!$cond || $cond===undefined){
            if(is_array($value)){
                $cond='in';
            }else{
                $cond='=';
            }
        }

        if(is_object($field))$field=$this->consume($field);

        if(is_array($value)){
            $value='('.implode(',',$this->escape($value)).')';
        }elseif(is_object($value)){
            $value='('.$value.')';
        }else{
            $value=$this->escape($value);
        }

        $where=$this->owner->bt($field). ' '.trim($cond).  ' '. $value;

        return $where;
    }
	function join($table,$on,$type='inner'){
        if($type=='table'){
            return $this->table($table)
                ->where($this->expr($on));


                
        }
		$this->args['join'][$table]="$type join ".$table." on $on";
		return $this;
	}
    function set($field,$value=undefined){
        if(is_array($field)){
            foreach($field as $key=>$value){
                $this->set($key,$value);
            }
            return $this;
        }

        if($value===undefined)throw $this->exception('Specify value when calling set()');

        $value=$this->escape($value);

        $this->args['set_fields'][]=$field;
        $this->args['set_values'][]=$value;
        $this->args['set'][]=$field.'='.$value;
        return $this;
    }
    function order($order,$desc=null){// ,$prepend=null){
        if(!$order)throw new SQLException("Empty order provided");
        $field=$order;
        if($desc)$order.=" desc";
        if(!$this->args['order'])$this->args['order']=array();
        //if($prepend && isset($this->args['order'])){
            array_unshift($this->args['order'], $order);
        //}else{
            //// existing ordering must be overwritten
            //if(($index=$this->isArgSet('order',$field))!==false)unset($this->args['order'][$index]);
            //$this->args['order'][]=$order;
        //}
        return $this;
    }

    // }}}

    // {{{ Statement templates and interfaces

    /* Generates and returns SELECT statement */
	function select(){
		return $this->parseTemplate("select [options] [fields] [from] [table] [join] [where] [group] [having] [order] [limit]");
	}
	function insert(){
        return $this->parseTemplate("insert [options_insert] into [table_noalias] ([set_fields]) values ([set_values])");
	}
    function update(){
        return $this->parseTemplate("update [table_noalias] set [set] [where]");
    }
    function replace(){
        return $this->parseTemplate("replace [options_replace] into [table_noalias] ([set_fields]) values ([set_value])");
    }
    function delete(){
        return $this->parseTemplate("delete from [table_noalias] [where]");
    }

    // }}}

    // {{{ More complex query generations and specific cases

    function do_select(){
        try {
            return $this->stmt=$this->owner->query($q=$this->select(),$this->params);
        }catch(PDOException $e){
            throw $this->exception('SELECT statement failed')
                ->addPDOException($e)
                ->addMoreInfo('params',$this->params)
                ->addMoreInfo('query',$q);
        }
    }
    function do_insert(){
        try {
            $this->owner->query($q=$this->insert(),$this->params);
            return $this->owner->lastID();
        }catch(PDOException $e){
            throw $this->exception('INSERT statement failed')
                ->addPDOException($e)
                ->addMoreInfo('params',$this->params)
                ->addMoreInfo('query',$q);
        }
    }
    function do_update(){
        try {
            $this->owner->query($q=$this->update(),$this->params);
            return $this;
        }catch(PDOException $e){
            throw $this->exception('UPDATE statement failed')
                ->addPDOException($e)
                ->addMoreInfo('params',$this->params)
                ->addMoreInfo('query',$q);
        }
    }
    function do_replace(){
        try {
            $this->owner->query($q=$this->replace(),$this->params);
            return $this;
        }catch(PDOException $e){
            throw $this->exception('REPLACE statement failed')
                ->addPDOException($e)
                ->addMoreInfo('params',$this->params)
                ->addMoreInfo('query',$q);
        }
    }
    function do_delete(){
        try {
            $this->owner->query($q=$this->delete(),$this->params);
            return $this;
        }catch(PDOException $e){
            throw $this->exception('DELETE statement failed')
                ->addPDOException($e)
                ->addMoreInfo('params',$this->params)
                ->addMoreInfo('query',$q);
        }
    }
    function execute(){
        try {
            return $this->stmt=$this->owner->query($q=($this->expr?$this:$this->select()),$this->params);
        }catch(PDOException $e){
            throw $this->exception('SELECT or expression failed')
                ->addPDOException($e)
                ->addMoreInfo('params',$this->params)
                ->addMoreInfo('query',$q);
        }
    }
    function _execute($template){
        try {
            $this->stmt=$this->owner->query($this->parseTemplate($template),$this->params);
            return $this;
        }catch(PDOException $e){
            throw $this->exception('custom executeon failed')
                ->addPDOException($e)
                ->addMoreInfo('params',$this->params)
                ->addMoreInfo('template',$template);
        }
    }
    function describe($table){
        return $this->table($table)->_execute('describe [table]');
    }
    // }}}

    // {{{ Data fetching modes
    function get(){
        return $this->getAll();
    }
    function do_getOne(){ return $this->getOne(); }
    function getOne(){
        $res=$this->execute()->fetch();
        return $res[0];
    }
    function do_getAll(){ return $this->getAll(); }
    function getAll(){
        return $this->execute()->fetchAll();
    }


    function do_getRow(){ return $this->get(PDO::FETCH_NUM); }
    function getRow(){ return $this->get(PDO::FETCH_NUM); }
    function do_getHash(){ return $this->get(PDO::FETCH_ASSOC); }
    function getHash(){ return $this->get(PDO::FETCH_ASSOC); }

    function fetch(){
        if(!$this->stmt)$this->execute();
        return $this->fetch();
    }
    function fetchAll(){
        if(!$this->stmt)$this->execute();
        return $this->fetchAll();
    }
	function calc_found_rows(){
        // if not mysql return;
        return $this->option('SQL_CALC_FOUND_ROWS');
    }
    function foundRows(){
        if($this->hasOption('SQL_CALC_FOUND_ROWS')){
            return $this->owner->getOne('select found_rows()');
        }
        /* db-compatibl way: */
        $c=clone $this;
        $c->del('limit');
        $c->del('fields');
        $c->field('count(*)');
        return $c->do_getOne();
    }
    // }}}

    // {{{ Iterator support 
    public $data=false;
    function rewind(){
        unset($this->stmt);
        return $this;
    }
    function next(){
        return $this->data = $this->get();
    }
    function current(){
        return $this->data;
    }
    function key(){
        return $this->data['id'];
    }
    function valid(){
        return $this->data!==false;
    }
    // }}}


    // {{{ Query Generation heavy-duty generation code

    /* [private] Generates values for different tokens in the template */
	function getArgs($required){
		$args=array();

        // {{{ table name
		if(isset($required['table'])) {
            $args['table']=join(',',$this->args['table']);
        } // }}}

        // {{{ conditional "from"
		if(isset($required['from'])) {
            if($args['table']){
                $args['from']='from';
            } else {
                $args['from']='';
            }
        } // }}}

        // {{{ fields data
		if(isset($required['fields'])) {
			// comma separated fields, such as for select
			if(!$this->args['fields'])$this->args['fields']=array('*');
			$args['fields']=join(', ', $this->args['fields']);
		} // }}}

        // {{{ options (MySQL)
		if(isset($required['options'])&&isset($this->args['options'])){
			$args['options']=join(' ',$this->args['options']);
        } 
		if(isset($required['options_insert'])&&isset($this->args['options_insert'])){
			$args['options_insert']=join(' ',$this->args['options_insert']);
        } 
        // }}}

        // {{{ set (for updates)
		if(isset($required['set'])) {
			$set = array();
			if(!$this->args['set']) {
				throw $this->exception('You should call $dq->set() before requesting update');
			}
			$args['set']=join(', ', $this->args['set']);
		} // }}}

        // {{{ set (for insert)
        if(isset($required['set_fields'])){
            $args['set_fields']=join(', ',$this->args['set_fields']);
        }
        if(isset($required['set_values'])) {
            $args['set_values']=join(', ',$this->args['set_values']);
		} // }}}

        // {{{ joins to other tables
		if(isset($required['join'])&&isset($this->args['join'])) {
			$args['join']=join(' ', $this->args['join']);
		} // }}}

        // {{{ where clause
		if(isset($required['where'])&&isset($this->args['where'])) {
			$args['where'] = "where (".join(') and (', $this->args['where']).")";
		} // }}}

        // {{{ having clause
		if(isset($required['having'])&&isset($this->args['having'])) {
			$args['having'] = "having (".join(') and (', $this->args['having']).")";
		} // }}}

        // {{{ order clause
		if(isset($required['order'])&&isset($this->args['order'])) {
			$args['order'] = "order by ".join(', ', $this->args['order']);
		} // }}}

        // {{{ grouping
		if(isset($required['group'])&&isset($this->args['group'])) {
			$args['group'] = "group by ".join(', ',$this->args['group']);
		} // }}}

        // {{{ limit
		if(isset($required['limit'])&&isset($this->args['limit'])) {
			$args['limit'] = "limit ".$this->args['limit']['shift'].", ".$this->args['limit']['cnt'];
		} // }}}

        foreach($required as $key=>$junk){
            if(!$args[$key] && is_array($this->args[$key]))$args[$key]=join(', ',$this->args[$key]);
        }

		return $args;
	}
    /* Generic query builder funciton. Provided with template it will fill-in the data */


    function debug(){
        $this->debug=1;
        return $this;
    }


	function parseTemplate($template) {
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
    // }}}
}
