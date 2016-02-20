<?php
/**
 * Undocumented.
 */
class TMail_Template extends SMlite
{
    public $template_type = 'mail';
    public function getDefaultSettings()
    {
        /*
         * This function specifies default settings for SMlite. Use
         * 2nd argument for constructor to redefine those settings.
         *
         * A small note why I decided on .html extension. I want to
         * point out that template files are and should be valid HTML
         * documents. With .html extension those files will be properly
         * rendered inside web browser, properly understood inside text
         * editor or will be properly treated with wysiwyg html editors.
         */
        return array(
                // by separating them with ':'
                'ldelim' => '{',                // tag delimiter
                'rdelim' => '}',
                'extension' => '.html',          // template file extension
                );
    }

    public function init()
    {
        parent::init();
    }
}
