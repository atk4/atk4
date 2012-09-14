<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Implementation of PDO-compatible dynamic queries
 * @link http://agiletoolkit.org/doc/dsql
 *
 * Use: 
 *  $this->api->dbConnect();
 *  $query = $this->api->db->dsql();
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class DB_dsql extends AbstractModel implements Iterator {
    /** Data accumulated by calling Definition methods, which is then used when rendering */
    public $args=array();

    /** List of PDO parametical arguments for a query. Used only during rendering. */
    public $params=array();

    /** Manually-specified params */
    public $extra_params=array();

    /** PDO Statement, if query is prepared. Used by iterator */
    public $stmt=null;

    /** Expression to use when converting to string */
    public $template=null;

    /** You can switch mode with select(), insert(), update() commands. Mode is initialized to "select" by default */
    public $mode=null;

    /** Used to determine main table. */
    public $main_table=null;

    /** If no fields are defined, this field is used */
    public $default_field='*';

    public $default_exception='Exception_DB';

    /** call $q->debug() to turn on debugging. */
    public $debug=false;

    /** prefix for all parameteric variables: a, a_2, a_3, etc */
    public $param_base='a';

    public $sql_templates=array(
        'select'=>"select [options] [field] [from] [table] [join] [where] [group] [having] [order] [limit]",
        'insert'=>"insert [options_insert] into [table_noalias] ([set_fields]) values ([set_values])",
        'replace'=>"replace [options_replace] into [table_noalias] ([set_fields]) values ([set_value])",
        'update'=>"update [table_noalias] set [set] [where]",
        'delete'=>"delete from  [table_noalias] [where]",
        'truncate'=>'truncate table [table_noalias]'
    );
    /** required for non-id based tables */
    public $id_field;

    // {{{ Generic stuff
    function _unique(&$array,$desired=null){
        $desired=preg_replace('/[^a-zA-Z0-9:]/','_',$desired);
        $desired=parent::_unique($array,$desired);
        return $desired;
    }
    function __clone(){
        $this->stmt=null;
    }
    function __toString(){
        try {
            return $this->render();
        }catch(Exception $e){
            return "Exception: ".$e->getText();
        }

        return $this->toString();

        if($this->expr)return $this->parseTemplate($this->expr);
        return $this->select();
    }
    /** Explicitly sets template to your query. Remember to change $this->mode if you switch this */
    function template($template){
        $this->template=$template;
        return $this;
    }
    /** Change prefix for parametric values. Useful if you are combining multiple queries. */
    function paramBase($param_base){
        $this->param_base=$param_base;
        return $this;
    }
    /** Create new dsql object which can then be used as sub-query. */
    function dsql(){
        return $this->owner->dsql(get_class($this));
    }
    /** Converts value into parameter and returns reference. Use only during query rendering. */
    function escape($val){
        if($val===undefined)return '';
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
    /** Recursively renders sub-query or expression, combining parameters */
    function consume($dsql,$tick=true){
        if($dsql===undefined)return '';
        if($dsql===null)return '';
        if(is_object($dsql) && $dsql instanceof Field)return $dsql->getExpr();
        if(!is_object($dsql) || !$dsql instanceof DB_dsql)return $tick?$this->bt($dsql):$dsql;
        $dsql->params = &$this->params;
        $ret = $dsql->_render();
        if($dsql->mode=='select')$ret='('.$ret.')';
        unset($dsql->params);$dsql->params=array();
        return $ret;
    }
    /** Defines a custom template variable. */
    function setCustom($template,$value){
        $this->args['custom'][$template]=$value;
        return $this;
    }
    /** Removes definition for argument. $q->del('where') */
    function del($args){
		$this->args[$args]=array();
        return $this;
    }
    /** Removes all definitions. Start from scratch */
    function reset(){
        $this->args=array();
    }
    // }}}

    // {{{ Dynamic Query Definition methods

    // {{{ Generic methods
    /** Returns new dynamic query and initializes it to use specific template. */
    function expr($expr,$params=array()){
        return $this->dsql()->useExpr($expr,$params);
    }
    /** Shortcut to produce expression which concatinates "where" clauses with "OR" operator */
    function orExpr(){
        return $this->expr('([orwhere])');
    }
    /** @private Change template and bind parameters for existing query */
    function useExpr($expr,$params=array()){
        $this->template=$expr;
        $this->extra_params=$params;
        return $this;
    }
    /** Return expression containing a properly escaped field. Use make subquery condition reference parent query */
    function getField($fld){
        if($this->main_table===false)throw $this->exception('Cannot use getField() when multiple tables are queried');
        return $this->expr(
            $this->bt($this->main_table).
            '.'.
            $this->bt($fld)
        );
    }
    // }}}
    // {{{ table()
    /** 
     * Specifies which table to use in this dynamic query. You may specify array to perform operation on multiple tables.
     *
     * @link http://agiletoolkit.org/doc/dsql/where
     *
     * Examples:
     *  $q->table('user');
     *  $q->table('user','u');
     *  $q->table('user')->table('salary')
     *  $q->table(array('user','salary'));
     *  $q->table(array('user','salary'),'user');
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
    /** Returns template component [table] */
    function render_table(){
        $ret=array();
        if(!is_array($this->args['table']))return;
        foreach($this->args['table'] as $row){
            list($table,$alias)=$row;

            $table=$this->bt($table);


            if($alias!==undefined && $alias)$table.=' '.$this->bt($alias);

            $ret[]=$table;
        }
        return join(',',$ret);
    }
    /** Conditionally returns "from", only if table is specified */
    function render_from(){
        if($this->args['table'])return 'from';
        return '';
    }
    /** Returns template component [table_noalias] */
    function render_table_noalias(){
        $ret=array();
        foreach($this->args['table'] as $row){
            list($table,$alias)=$row;

            $table=$this->bt($table);


            $ret[]=$table;
        }
        return join(', ',$ret);
	}
    // }}}
    // {{{ field()
    /** 
     * Adds new column to resulting select by querying $field. 
     * @link http://agiletoolkit.org/doc/dsql/field
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
     */
	function field($field,$table=null,$alias=null) {
        if(is_array($field)){
            foreach($field as $alias=>$f){
                if(is_numeric($alias))$alias=null;
                $this->field($f,$table,$alias);
            }
            return $this;
        }elseif(is_string($field)){
            $field=explode(',',$field);
            if(count($field)>1){
                foreach($field as $f){
                    $this->field($f,$table,$alias);
                }
                return $this;
            }
            $field=$field[0];
        }

        if(is_object($field)){
            //if(!$table)throw $this->exception('Specified expression without alias')
            //    ->addMoreInfo('expr',$field);
            $alias=$table;$table=null;
        }
        $this->args['fields'][]=array($field,$table,$alias);
		return $this;
	}
    function render_field(){
        $result=array();
        if(!$this->args['fields']){
            //if($this->main_table)return '*.'.$this->main_table;
            return (string)$this->default_field;
        }
        foreach($this->args['fields'] as $row){
            list($field,$table,$alias)=$row;
            if($alias==$field)$alias=undefined;
            /**/$this->api->pr->start('dsql/render/field/consume');
            $field=$this->consume($field);
            /**/$this->api->pr->stop();
            if(!$field){
                $field=$table;
                $table=undefined;
            }
            if($table && $table!==undefined)$field=$this->bt($table).'.'.$field;
            if($alias && $alias!==undefined)$field.=' '.$this->bt($alias);
            $result[]=$field;
        }
        return join(',',$result);
    }
    // }}}
    // {{{ where() and having()
    /** 
     * Adds condition to your query
     * @link http://agiletoolkit.org/doc/dsql/where
     *
     * Examples:
     *  $q->where('id',1);
     *
     * Second argument specifies table for regular fields
     *  $q->where('id>','1');
     *  $q->where('id','>',1);
     *
     * You may use expressions
     *  $q->where($q->expr('a=b'));
     *  $q->where('date>',$q->expr('now()'));
     *  $q->where($q->expr('length(password)'),'>',5);
     *
     * Finally, subqueries can also be used
     *  $q->where('foo',$q->dsql()->table('foo')->field('name'));
     *
     * To specify OR conditions
     *  $q->where($q->orExpr()->where('a',1)->where('b',1));
     *
     * you can also use the shortcut:
     * 
     *  $q->where(array('a is null','b is null'));
     */
    function where($field,$cond=undefined,$value=undefined,$kind='where'){
        if(is_array($field)){
            // or conditions
            $or=$this->orExpr();
            foreach($field as $row){
                if(is_array($row)){
                    $or->where($row[0],
                        isset($row[1])?$row[1]:undefined,
                        isset($row[2])?$row[2]:undefined);
                }elseif(is_object($row)){
                    $or->where($row);
                }else{
                    $or->where($or->expr($row));
                }
            }
            $field=$or;
            $this->api->x=1;
        }


        if(is_string($field) && !preg_match('/^[.a-zA-Z0-9_]*$/',$field)){
            // field contains non-alphanumeric values. Look for condition
            preg_match('/^([^ <>!=]*)([><!=]*|( *(not|is|in|like))*) *$/',$field,$matches);
            $value=$cond;
            $cond=$matches[2];
            if(!$cond){

                // IF COMPAT
                $matches[1]=$this->expr($field);
                if($value && $value!==undefined)$cond='=';else $cond=undefined;
                //throw $this->exception('Field is specified incorrectly or condition is not supported')
                    //->addMoreInfo('field',$field);
            }
            $field=$matches[1];
        }

        //if($value==undefined && !is_object($field) && !is_object($cond))throw $this->exception('value is not specified');

        $this->args[$kind][]=array($field,$cond,$value);
        return $this;
    }
    function having($field,$cond=undefined,$value=undefined){
        return $this->where($field,$cond,$value,'having');
    }
    function _render_where($kind){
        $ret=array();
        foreach($this->args[$kind] as $row){
            list($field,$cond,$value)=$row;

            if(is_object($field)){
                // if first argument is object, condition must be explicitly specified
                $field=$this->consume($field);
            }else{
                list($table,$field)=explode('.',$field,2);
                if($field){
                    $field=$this->bt($table).'.'.$this->bt($field);
                }else{
                    $field=$this->bt($table);
                }
            }

            if($value===undefined && $cond===undefined){
                $r=$field;
                $ret[]=$r;
                continue;
            }

            if($value===undefined){
                $value=$cond;
                $cond='=';
                if(is_array($value))$cond='in';
                if(is_object($value) && @$value->mode=='select')$cond='in';
            }else{
                $cond=trim($cond);
            }


            if($cond=='in' && is_string($value)){
                $value=explode(',',$value);
            }

            if(is_array($value)){
                $v=array();
                foreach($value as $vv){
                    $v[]=$this->escape($vv);
                }
                $value='('.join(',',$v).')';
                $cond='in';
                $r=$this->consume($field).' '.$cond.' '.$value;
                $ret[]=$r;
                continue;
            }

            if(is_object($value))$value=$this->consume($value);else$value=$this->escape($value);

            $r=$field.' '.$cond.' '.$value;
            $ret[]=$r;
        }
        return $ret;
    }
    function render_where(){
        if(!$this->args['where'])return;
        return 'where '.join(' and ',$this->_render_where('where'));
    }
    function render_orwhere(){
        if(!$this->args['where'])return;
        return join(' or ',$this->_render_where('where'));
    }
    function render_having(){
        if(!$this->args['having'])return;
        return 'having '.join(' and ',$this->_render_where('having'));
    }
    // }}}
    // {{{ join()
    /** 
     * Adds condition to your query
     * @link http://agiletoolkit.org/doc/dsql/join
     *
     * Examples:
     *  $q->join('address');
     *  $q->join('address.user_id');
     *  $q->join(array('a'=>'address'));
     *
     * Second argument may specify the field of the master table
     *  $q->join('address.code','code');
     *  $q->join('address.code','user.code');
     *
     * Third argument may specify which kind of join to use.
     *  $q->join('address',null,'left');
     *  $q->join('address.code','user.code','inner');
     *
     * Using array syntax you can join multiple tables too
     *  $q->join(array('a'=>'address','p'=>'portfolio'));
     */
	function join($foreign_table, $master_field=null, $join_kind=null, $_foreign_alias=null){
        // Compatibility mode
        if(isset($this->api->compat)){
            if(strpos($foreign_table,' ')){
                list($foreign_table,$alias)=explode(' ',$foreign_table);
                $foreign_table=array($alias=>$foreign_table);
            }
            if(strpos($master_field,'=')){
                $master_field=$this->expr($master_field);
            }
        }

        // If array - add recursively
        if(is_array($foreign_table)){
            foreach ($foreign_table as $alias=>$foreign){
                if(is_numeric($alias))$alias=null;

                $this->join($foreign,$master_field,$join_kind,$alias);
            }
            return $this;
        }
        $j=array();

        // Split and deduce fields
        list($f1,$f2)=explode('.',$foreign_table,2);

        if(is_object($master_field)){
            $j['expr']=$master_field;
        }else{
            // Split and deduce primary table
            if(is_null($master_field)){
                list($m1,$m2)=array(null,null);
            }else{
                list($m1,$m2)=explode('.',$master_field,2);
            }
            if(is_null($m2)){
                $m2=$m1; $m1=null;
            }
            if(is_null($m1))$m1=$this->main_table;

            // Identify fields we use for joins
            if(is_null($f2) && is_null($m2))$m2=$f1.'_id';
            if(is_null($m2))$m2='id';
            $j['m1']=$m1;
            $j['m2']=$m2;
        }
        $j['f1']=$f1;
        if(is_null($f2))$f2='id';
        $j['f2']=$f2;

        $j['t']=$join_kind?:'left';
        $j['fa']=$_foreign_alias;

        $this->args['join'][]=$j;
        return $this;
    }
    function render_join(){
        if(!$this->args['join'])return '';
        $joins=array();
        foreach($this->args['join'] as $j){
            $jj='';

            $jj.=$j['t'].' join ';

            $jj.=$this->bt($j['f1']);

            if(!is_null($j['fa']))$jj.=' as '.$this->bt($j['fa']);

            $jj.=' on ';

            if($j['expr']){
                $jj.=$this->consume($j['expr']);
            }else{
                $jj.=
                    $this->bt($j['fa']?:$j['f1']).'.'.
                    $this->bt($j['f2']).' = '.
                    $this->bt($j['m1']).'.'.
                    $this->bt($j['m2']);
            }
            $joins[]=$jj;
        }
        return implode(' ',$joins);
    }
    // }}}
    // {{{ group()
    function group($option){
        return $this->_setArray($option,'group');
    }
    function render_group(){
        if(!$this->args['group'])return'';
        $x=array();
        foreach($this->args['group'] as $arg){
            $x[]=$this->bt($arg);
        }
        return 'group by '.implode(', ',$x);
    }
    // }}}
    // {{{ order()
    function order($order,$desc=null){// ,$prepend=null){
        if(is_object($order))$order='('.$order.')';
        if($desc)$order.=' desc';
        return $this->_setArray($order,'order');
    }

    function render_order(){
        if(!$this->args['order'])return'';
        $x=array();
        foreach($this->args['order'] as $arg){
            if(substr($arg,-5)==' desc'){
                $x[]=$this->bt(substr($arg,0,-5)).' desc';
            }else{
                $x[]=$this->bt($arg);
            }
        }
        return 'order by '.implode(', ',$x);
    }

    // }}}
    // {{{ option() and args()
    /** Defines query option */
    function option($option){
        return $this->_setArray($option,'options');
    }
    function render_options(){
        return @implode(' ',$this->args['options']);
    }
    function option_insert($option){
        return $this->_setArray($option,'options_insert');
    }
    function render_options_insert(){
        if(!$this->args['options_insert'])return '';
        return implode(' ',$this->args['options_insert']);
    }
    // }}}
    // {{{  args()
    /** set arguments for call() */
    function args($args){
        return $this->_setArray($args,'args',false);
    }
    function render_args(){
        $x=array();
        foreach($this->args['args'] as $arg){
            $x[]=is_object($arg)?
                $this->consume($arg):
                $this->escape($arg);
        }
        return implode(', ',$x);
    }
    function ignore(){
        $this->args['options_insert'][]='ignore';
        return $this;
    }
    /** Check if option was defined */
    function hasOption($option){
        return @in_array($option,$this->args['options']);
    }
    function hasInsertOption($option){
        return @in_array($option,$this->args['options_insert']);
    }
    // }}}
    // {{{ limit()
    /** Limit row result */
    function limit($cnt,$shift=0){
        $this->args['limit']=array(
            'cnt'=>$cnt,
            'shift'=>$shift
        );
        return $this;
    }
    function render_limit(){
        if($this->args['limit']){
            return 'limit '.
                (int)$this->args['limit']['shift'].
                ', '.
                (int)$this->args['limit']['cnt'];
        }
    }
    // }}}
    // {{{ set()
    function set($field,$value=undefined){
        if($value===false){
            throw $this->exception('Value "false" is not supported by SQL');
        }
        if(is_array($field)){
            foreach($field as $key=>$value){
                $this->set($key,$value);
            }
            return $this;
        }

        if($value===undefined)throw $this->exception('Specify value when calling set()');

        $this->args['set'][$field]=$value;
        return $this;
    }
    function render_set(){
        $x=array();
        if($this->args['set'])foreach($this->args['set'] as $field=>$value){

            if(is_object($field))$field=$this->consume($field);else$field=$this->bt($field);
            if(is_object($value))$value=$this->consume($value);else$value=$this->escape($value);

            $x[]=$field.'='.$value;
        }
        return join(', ',$x);
    }
    function render_set_fields(){
        $x=array();
        if($this->args['set'])foreach($this->args['set'] as $field=>$value){

            if(is_object($field))$field=$this->consume($field);else$field=$this->bt($field);

            $x[]=$field;
        }
        return join(',',$x);
    }
    function render_set_values(){
        $x=array();
        if($this->args['set'])foreach($this->args['set'] as $field=>$value){

            if(is_object($value))$value=$this->consume($value);else$value=$this->escape($value);

            $x[]=$value;
        }
        return join(',',$x);
    }
    // }}}
    // {{{ MISC
    /** Backticks will be added around all fields. Set this to '' if you prefer cleaner queries */
    public $bt='`';
    function bt($s){
        if(is_array($s)){
            $out=array();
            foreach($s as $ss){
                $out[]=$this->bt($ss);
            }
            return $out;
        }

        if(!$this->bt
        || is_object($s)
        || $s=='*'
        || strpos($s,'.')!==false
        || strpos($s,'(')!==false
        || strpos($s,$this->bt)!==false
        )return $s;

        return $this->bt.$s.$this->bt;
    }
    /* Defines query option */
    function _setArray($values,$name,$parse_commas=true){
        if(is_string($values) && $parse_commas && strpos($values,','))$values=explode(',',$values);
        if(!is_array($values))$values=array($values);
        if(!isset($this->args[$name]))$this->args[$name]=array();
        $this->args[$name]=array_merge($this->args[$name],$values);
        return $this;
    }
    // }}}

    // }}}

    // {{{ Statement templates and interfaces

    /** Switches to select mode (which is default) for this query */
    function SQLTemplate($mode){
        $this->mode=$mode;
        $this->template=$this->sql_templates[$mode];
        return $this;
    }
    /** Switches to call mode. Use with args() */
    function call($fx,$args=null){
        $this->mode='call';
        $this->args['fx']=$fx;
        if(!is_null($args)){
            $this->args($args);
        }
        $this->template="call [fx]([args])";
        return $this;
    }
    function fx($fx,$args=null){
        $this->mode='fx';
        $this->args['fx']=$fx;
        if(!is_null($args)){
            $this->args($args);
        }
        $this->template="[fx]([args])";
        return $this;
    }
    function render_fx(){
        return $this->args['fx'];
    }
    function sum($arg=null){
        return $this->expr('sum([sum])')->setCustom('sum',$this->bt($arg));
    }
    function count($arg=null){
        if(is_null($arg))$arg='*';
        return $this->expr('count([count])')->setCustom('count',$this->bt($arg));
    }
    // }}}

    // {{{ More complex query generations and specific cases

    /** Executes any query query */
    function execute(){
        try {
            /**/$this->api->pr->start('dsql/execute/render');
            $q=$this->render();
            /**/$this->api->pr->next('dsql/execute/query');
            $this->stmt=$this->owner->query($q,$this->params);
            $this->template=$this->mode=null;
            /**/$this->api->pr->stop();
            return $this;
        }catch(PDOException $e){
            throw $this->exception('Database Query Failed')
                ->addPDOException($e)
                ->addMoreInfo('mode',$this->mode)
                ->addMoreInfo('params',$this->params)
                ->addMoreInfo('query',$q)
                ->addMoreInfo('template',$this->template)
                ;
        }
    }
    /** Executes select query. */
    function select(){
        return $this->SQLTemplate('select')->execute();
    }
    /** Executes insert query. Returns ID of new record. */
    function insert(){
        $this->SQLTemplate('insert')->execute();
        return 
            $this->hasInsertOption('ignore')?null:
            $this->owner->lastID();
    }
    /** Inserts multiple rows of data. Uses ignore option. */
    function insertAll($array){
        $ids=array();
        foreach($array as $hash){
            $ids[]=$this->del('set')->set($hash)->insert();
        }
        return $ids;
    }
    /** Executes update query */
    function update(){
        return $this->SQLTemplate('update')->execute();
    }
    /** Executes replace query */
    function replace(){
        return $this->SQLTemplate('replace')->execute();
    }
    /** Executes delete query */
    function delete(){
        return $this->SQLTemplate('delete')->execute();
    }
    /** Executes truncate */
    function truncate(){
        return $this->SQLTemplate('truncate')->execute();
    }

    /** @obsolete, use select() */
    function do_select(){
        return $this->select();
    }
    /** @obsolete, use insert() */
    function do_insert(){
        return $this->insert();
    }
    /** @obsolete, use update() */
    function do_update(){
        return $this->update();
    }
    /** @obsolete, use replace() */
    function do_replace(){
        return $this->replace();
    }
    // }}}

    // {{{ Data fetching modes
    /** Will execute DSQL query and return all results inside array of hashes */
    function get(){
        if(!$this->stmt)$this->execute();
        $res=$this->stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->rewind();
        $this->stmt=null;
        return $res;
    }
    /** Will execute DSQL query and return first column of a first row */
    function getOne(){
        $res=$this->getRow();
        $this->rewind();
        $this->stmt=null;
        return $res[0];
    }
    /** Will execute DSQL query and return first row as array (not hash) */
    function getRow(){ 
        return $this->fetch(PDO::FETCH_NUM);
    }
    /** Will execute DSQL query and return first row as hash (column=>value) */
    function getHash(){ 
        return $this->fetch(PDO::FETCH_ASSOC);
    }
    /** Will execute the query (if it's not executed already) and return first row */
    function fetch($mode=PDO::FETCH_ASSOC){
        if(!$this->stmt)$this->execute();
        return $this->stmt->fetch($mode);
    }
    // {{{ Obsolete functions 
    /** @obsolete. Use get() */
    function fetchAll(){
        return $this->get();
    }
    /** @obsolete. Use getQne() */
    function do_getOne(){
        return $this->getOne(); 
    }
    /** @obsolete. Use get() */
    function do_getAllHash(){ 
        return $this->get(); 
    }
    function do_getAll(){ 
        return $this->get(); 
    }
    /** @obsolete. Use get() */
    function getAll(){
        return $this->get();
    }
    /** @obsolete. Use getRow() */
    function do_getRow(){ 
        return $this->getRow();
    }
    /** @obsolete. Use getHash() */
    function do_getHash(){ 
        return $this->getHash(); 
    }
    // }}}


    /** Sets flag to hint SQL (if supported) to prepare total number of columns. Use foundRows() to read this afterwards */
    function calc_found_rows(){
        // add option for row calculation if supported
        return $this;
    }
    /** After fetching data, call this to find out how many rows there were in total. Call calc_found_rows() for better performance */
    function foundRows(){
        if($this->hasOption('SQL_CALC_FOUND_ROWS')){
            return $this->owner->getOne('select found_rows()');
        }
        /* db-compatibl way: */
        $c=clone $this;
        $c->del('limit');
        $c->del('fields');
        $c->field('count(*)');
        return $c->getOne();
    }
    // }}}

    // {{{ Iterator support 
    public $data=false;
    public $_iterating=false;
    public $preexec=false;
    function preexec(){
        $this->execute();
        $this->preexec=true;
        return $this;
    }
    function rewind(){
        if($this->_iterating){
            $this->stmt=null;
            $this->_iterating=false;
        }
        $this->_iterating=true;
        return $this;
    }
    function next(){
        $this->data = $this->fetch();
        return $this;
    }
    function current(){
        return $this->data;
    }
    function key(){
        return $this->data[$this->id_field];
    }
    function valid(){
        if(!$this->stmt || $this->preexec){
            $this->preexec=false;
            $this->data = $this->fetch();
        }
        return (boolean)$this->data;
    }
    // }}}

    // {{{ Rendering
    /** Will set a flag which will output query (echo) as it is being rendered. */
    function debug(){
        $this->debug=1;
        return $this;
    }
    function getDebugQuery($r=null){
        if(!$r)$r=$this->_render();
        $d=$r;
        $pp=array();
        $d=preg_replace('/`([^`]*)`/','`<font color="black">\1</font>`',$d);
        foreach(array_reverse($this->params) as $key=>$val){
            if(is_string($val))$d=preg_replace('/'.$key.'([^_]|$)/','"<font color="green">'.htmlspecialchars(addslashes($val)).'</font>"\1',$d);
            elseif(is_null($val))$d=preg_replace('/'.$key.'([^_]|$)/','<font color="black">NULL</font>\1',$d);
            elseif(is_numeric($val))$d=preg_replace('/'.$key.'([^_]|$)/','<font color="red">'.$val.'</font>\1',$d);
            else$d=preg_replace('/'.$key.'([^_]|$)/',$val.'\1',$d);

            $pp[]=$key;
        }
        return "<font color='blue'>".$d."</font> <font color='gray'>[".join(', ',$pp)."]</font><br/>";
    }
    /** Converts query into string format. This will contain parametric references */
    function render(){
        $this->params=$this->extra_params;
        $r=$this->_render();
        if($this->debug){
            echo $this->getDebugQuery($r);
        }
        return $r;
    }
    function _render(){
        /**/$this->api->pr->start('dsql/render');
        if(!$this->template)$this->SQLTemplate('select');
        $self=$this;
        $res= preg_replace_callback('/\[([a-z0-9_]*)\]/',function($matches) use($self){
            /**/$self->api->pr->next('dsql/render/'.$matches[1],true);
            $fx='render_'.$matches[1];
            if($self->hasMethod($fx))return $self->$fx();
            elseif(isset($self->args['custom'][$matches[1]]))return $self->consume($self->args['custom'][$matches[1]]);
            else return $matches[0];
        },$this->template);
        /**/$this->api->pr->stop(null,true);
        return $res;
    }
    // }}}
}
