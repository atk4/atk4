<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * Implementation of a Expression fields in SQL_Model
 * @link http://agiletoolkit.org/doc/model/table/expression
 *
 * Field_Expression implements ability to specify expressions inside your
 * SQL_Model and have them appear as a read-only fields.
 *
 * Use:
 * $model->addExpression('age','year(now())-year(birthdate)');
 * // Below example achieves the same,but it prefixes age with proper table name
 * $model->addExpression('age',
 *      'year(now())-year('.$model->getElement('adatege').')');
 *
 * $model->addExpression('row_counter',$this->db->dsql()->useExpr('@x := @x+1');
 *
 * // using custom controller to format field
 * $myctl=$this->add('Controller_MiscMySQL');
 * $model->addExpression('date_formatted',array($myctl,'dateExpr1'));
 *
 * or
 * // or by using closure and extra argument
 * $myctl=$this->add('Controller_MiscMySQL');
 * $model->addExpression('date_formatted',
 *      function($model,$query) uses $myctl {
 *          return $myctl->dateExpr2($query,'date_raw');
 *      });
 *
 * NOTE: MiscMySQL is fictional controller.
 *
 *
 * @license See https://github.com/atk4/atk4/blob/master/LICENSE
 *
 */
class Field_Expression extends Field {
    public $expr=null;
    function editable($x=undefined){
        return $x===undefined?false:$this;
    }
    function calculated($x=undefined){
        return $x===undefined?true:$this;
    }
    /** specify DSQL, String or function($model, $dsql, $this_field) */
    function set($expr=null){
        $this->expr=$expr;
        return $this;
    }
    function getExpr(){
        if(!is_string($this->expr) && is_callable($this->expr)){
            $q = call_user_func($this->expr,$this->owner,$this->owner->dsql(),$this);
            if($q instanceof DB_dsql) return $q;
            //if($q instanceof DB_dsql)$q=$q->render();
            return $this->owner->dsql()->expr($q);
        }

        if($this->expr instanceof DB_dsql)return $this->expr;

        return $this->owner->dsql()->expr($this->expr);
    }
    function updateSelectQuery($select){
        $expr=$this->expr;
        if(!is_string($expr) && is_callable($expr))$expr=call_user_func($expr,$this->owner,$select,$this);
        if($expr instanceof DB_dsql){
            return $select->field($expr,$this->short_name);
        }
        return $select->field($select->expr($expr), $this->short_name);

    }
    function updateInsertQuery($insert){
        return $this;
    }
    function updateModifyQuery($insert){
        return $this;
    }

}
