<?php
/**
 * Implementation of a Expression fields in SQL_Model.
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
 */
class Field_Expression extends Field
{
    public $expr = null;

    public function editable($x = undefined)
    {
        return $x === undefined ? false : $this;
    }
    
    public function calculated($x = undefined)
    {
        return $x === undefined ? true : $this;
    }
    
    /** specify DSQL, String or function($model, $dsql, $this_field) */
    public function set($expr = null)
    {
        $this->expr = $expr;

        return $this;
    }
    
    public function getExpr()
    {
        if (!is_string($this->expr) && is_callable($this->expr)) {
            $q = call_user_func($this->expr, $this->owner, $this->owner->dsql(), $this);
            if ($q instanceof DB_dsql) {
                return $q;
            }
            //if($q instanceof DB_dsql)$q=$q->render();
            return $this->owner->dsql()->expr($q);
        }

        if ($this->expr instanceof DB_dsql) {
            return $this->expr;
        }

        return $this->owner->dsql()->expr($this->expr);
    }
    
    public function updateSelectQuery($select)
    {
        $expr = $this->expr;
        if (!is_string($expr) && is_callable($expr)) {
            $expr = call_user_func($expr, $this->owner, $select, $this);
        }
        if ($expr instanceof DB_dsql) {
            return $select->field($expr, $this->short_name);
        }

        return $select->field($select->expr($expr), $this->short_name);
    }

    public function updateInsertQuery($insert)
    {
        return $this;
    }
    
    public function updateModifyQuery($insert)
    {
        return $this;
    }
}
