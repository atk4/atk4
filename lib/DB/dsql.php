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
class DB_dsql extends AbstractModel {
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

    // {{{ Generic stuff
    function _unique(&$array,$desired=null){
        $desired=preg_replace('/[^a-zA-Z0-9:]/','_',$desired);
        $desired=parent::_unique($array,$desired);
        return $desired;
    }
    function __toString(){
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
        $name=$this->_unique($this->params,$name);
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
        $d->params = &$this->params;
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
                        therefore unable to consume');
            }
            $this->params=array_merge($this->params,$dsql->params);
        }
        $ret='('.$dsql.')';
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
    function useExpr($expr){
        $this->expr=$expr;
        return $this;
    }
    /* Specifies table to use for this dynamic query */
	function table($table){
        if(is_array($table)){
            foreach($table as $t){
                $this->table($t);
            }
            return $this;
        }
		$this->args['table'][]=
            $this->owner->bt($this->owner->table_prefix.$table);

        if(!$this->main_table)$this->main_table=$this->owner->table_prefix.$table;

		return $this;
	}
    /* Defines query option */
	function option($option){
		if(!is_array($option))$option=array($option);
		if(!isset($this->args['options']))$this->args['options']=array();
		$this->args['options']=array_merge($this->args['options'],$option);
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
    /* Remove result limit */
    function unLimit(){
        $this->unset($this->args['limit']);
        return $this;
    }
    function args($args){
        if(is_array($args)){
            foreach($args as $arg){
                $this->args['args'][]=$this->escape($arg);
            }
        }else{
            $this->args['args'][]=$this->escape($args);
        }
        return $this;
    }
    /* Adds one (string) or several fields (array) to the query. If $field is object, $table is alias */
	function field($field,$table=null,$alias=null) {
        if(is_array($field)){
            foreach($field as $f){
                $this->field($f,$table);
            }
            return $this;
        }

        if(is_object($field)){
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
    /* Never pass variable as $field! (esp if user can control it) */
    function having($field,$cond=null,$value=null){
        return $this->where($field,$cond,$value,'having');
    }
    function where($field,$cond=null,$value=null,$type='where'){
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
    function _where($field,$cond=null,$value=null){
        if(is_object($field)){
            if(is_null($cond) && is_null($value)){
                $this->args[$cond][]=$field;
                return $this;
            }
        }else{
            if(is_null($cond) && is_null($value)){
                throw $this->exception('Use expression syntax with one-argument calls')
                    ->addMoreInfo('arg',$field);
            }
        }


        if($value===null){
            $value=$cond;$cond=null;
        }

        /* guess condition as it might be in $field */
        if($cond===null && !is_object($field)){

            preg_match('/^([^ <>!=]*)([><!=]*|( *(not|in|like))*) *$/',$field,$matches);
            $field=$matches[1];
            $cond=$matches[2];
        }

        if(!$cond){
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
		$this->args['join'][$table]="$type join ".DTP.$table." on $on";
		return $this;
	}

    // }}}

    // {{{ Statement templates and interfaces

    /* Generates and returns SELECT statement */
	function select(){
		return $this->parseTemplate("select [options] [fields] [from] [table] [join] [where] [group] [having] [order] [limit]");
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
    function execute(){
        try {
            return $this->stmt=$this->owner->query($q=($this->expr?$this:$this->select()),$this->params);
        }catch(PDOException $e){
            throw $this->exception('custom statement failed')
                ->addPDOException($e)
                ->addMoreInfo('params',$this->params)
                ->addMoreInfo('query',$q);
        }
    }
    function get($mode){
        return $this->execute()->fetch($mode);
    }
    function do_getOne(){ return $this->getOne(); }
    function getOne(){
        $res=$this->execute()->fetch();
        return $res[0];
    }
    function do_getAll(){ return $this->getAll(); }
    function getAll(){
        return $this->execute()->fetchAll(PDO::FETCH_ASSOC);

        $data=array();
        foreach($this->execute() as $row){
            $data[]=$row;
        }
        return $data;
    }


    function do_getRow(){ return $this->get(PDO::FETCH_NUM); }
    function getRow(){ return $this->get(PDO::FETCH_NUM); }
    function do_getHash(){ return $this->get(PDO::FETCH_ASSOC); }
    function getHash(){ return $this->get(PDO::FETCH_ASSOC); }

    function fetch(){
        if($this->stmt)return $this->stmt->fetch();
        return $this->execute()->fetch();
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
		} // }}}

        // {{{ set (for updates)
		if(isset($required['set'])) {
			$set = array();
			if(!$this->args['set']) {
				return $this->fatal('You should call $dq->set() before requesting update');
			}
            /*
			foreach($this->args['set'] as $key=>$val) {
				if(is_int($key)) {
					$set[]="$val";
				}else{
					$set[]="`$key`=$val";
				}
			}
            */
			$args['set']=join(', ', $args['set']);
		} // }}}

        // {{{ set (for instert)
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
        /*
		if($this->debug){
			echo '<font color=blue>'.htmlentities(join('',$result)).'</font>'.$dd;
		}
        */
		return join('', $result);
	}
    // }}}
}
