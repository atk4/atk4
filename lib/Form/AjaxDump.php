<?
/**
 * Displays submit button
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Form_AjaxDump extends View {
    function render(){
        $this->owner->template_chunks['form']->append('form_buttons',
		'<input type="button" value="Dump" onclick="dump_'.$this->owner->name.'(\''.$this->name.'\')">');
    }
}
