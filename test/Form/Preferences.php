<?
/**
 * This is the description for the Class
 *
 * @author		Romans <romans@adevel.com>
 * @copyright	See file COPYING
 * @version		$Id$
 */
class Form_Preferences extends Form {
    function init(){
        // Those are usual fields.
        $this->add('Form_Field_Line','SMTP Server','smtp_server');
        // You can set some properties on those fields, for example
        // reload_on_change is defined in Form_Field.php
        $this->add('Form_Field_Checkbox','Use SSL','smtp_ssl',array('reload_on_change'));
        // Form_Field does not define 'conditin' property, however this
        // property is handled by Form.
        $this->add('Form_Field_Line','SSL Username','smtp_ssl_username',array('condition'=>'smtp_ssl=Y'));
        // If some property is not handled by Form, it's passed up to the
        // application class.
        $this->add('Form_Field_Line','SSL Password','smtp_ssl_password',array('condition'=>'smtp_ssl=Y'));

        // dynamic SQL is a flexible object which can be used for queries,
        // updates deletes and other SQL actions. In this particular example we
        // just specify table name, however we can as well add some 'where'
        // limitations which would be enforced to both loading and saving of
        // records.
        $d = $this->api->dsql()->table('preferences');

        // Those are action handlers. When initialized they hook up to
        // specified hook-spot and will do their action. When hook-spot 'init'
        // is reached, data of this form will be loaded from Sql by using
        // pre-defined dsql object
        $this->add('Action_Load_Sql', 'init', $d);
        // Hook-spot 'submited' is only reached if the contents of the form was
        // sent back to the system by browser. You can hook some validations
        // after data is loaded
        $this->add('Action_Load_Form', 'submited');
        // After one hook is executed, next hook is executed. In this case all
        // data will be first loaded from form, then saved into Sql
        $this->add('Action_Save_Sql', 'submited', $d);

        // We can generate action with a button. In this example Save would
        // produce 'submited' action and apropritate hooks will be executed,
        // however you can add another button such as [duplicate]. Then you
        // will need to specify manual hook here which would call a method of
        // this or other object.
        $this->add('Form_Button', 'Save', 'submited');
        // While 'submited' will be handled by the actions above, 'back' action
        // is still unhandled. Therefore the call will be passed up to parent
        // object, in this case it's Menu.
        $this->add('Form_Button', 'Cancel', 'back');
    }
}
