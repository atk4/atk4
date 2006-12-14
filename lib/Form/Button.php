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
    
    private $style = array();
    
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
                    '<input type="button" value="'.$this->label.'" name="'.$this->name.'" ' .
                    'id="'.$this->name.'" onclick="'.
                    (is_object($this->onclick)?$this->onclick->getString():$this->onclick).
                    '"' .
                    ((count($this->style)?'style="'.implode(';',$this->style).'"':'')).
					'>');
    }
    function setColor($color) {
    	$this->style[] = 'color: '.$color;
    	return $this;
    }
}
