<?
/**
 * Displays flexible button
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Form_Button extends AbstractView {
    public $label;

    public $onclick='';
    function setLabel($_label){
        $this->label=$_label;
        return $this;
    }
    function setNoSave(){
        // Field value will not be saved into defined source (such as database)
        $this->no_save=true;
        return $this;
    }
    function render(){
        $this->output(
                    '<input type="button" value="'.$this->label.'" onclick="'.
                    (is_object($this->onclick)?$this->onclick->getString():$this->onclick).
                    '">');
    }
}
