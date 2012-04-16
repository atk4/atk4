<?php // vim:ts=4:sw=4:et:fdm=marker
/**
 * Implementation of a Expression fields in Model_Table
 * @link http://agiletoolkit.org/doc/model/table/expression
 *
 * Field_Expression implements ability to specify expressions inside your Model_Table and have them appear as a read-only 
 * fields.
 *
 * Use:
 * $model->addExpression('age','year(now())-year(birthdate)');  
 * // Below example achieves the same, but it prefixes age with proper table name
 * $model->addExpression('age','year(now())-year('.$model->getElement('adatege')->getField().')');
 *
 * $model->addExpression('row_counter',$this->api->db->dsql()->useExpr('@x := @x+1');
 *
 * $myctl=$this->add('Controller_MiscMySQL');   // using custom controller to format field
 * $model->addExpression('date_formatted',array($myctl,'dateExpr1'));
 *
 * or 
 * $myctl=$this->add('Controller_MiscMySQL');   // or by using closure and extra argument
 * $model->addExpression('date_formatted',function($model,$query) uses $myctl { return $myctl->dateExpr2($query,'date_raw'); });
 *
 * NOTE: MiscMySQL is fictional controller.
 *
 *
 * @license See http://agiletoolkit.org/about/license
 * 
**/
class Field_Expression extends Field {
    public $expr=null;
    function editable($x=undefined){
        return $x===undefined?false:$this;
    }
    function calculated($x=undefined){
        return $x===undefined?true:$this;
    }
    /** specify DSQL, String or funciton($master_dsql,$this) */
    function set($expr=null){
        $this->expr=$expr;
        return $this;
    }
    function updateSelectQuery($select){
        $expr=$this->expr;
        if(is_callable($expr))$expr=call_user_func($expr,$this->owner,$select,$this);
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
