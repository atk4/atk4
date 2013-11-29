<?php
class Controller_Compat42 extends AbstractController {

    function init() {
        parent::init();

        $this->api->compat_42=$this;
    }

    /** Forces Agile Toolkit to use 4.2-compatible stylesheets and templates */
    function useOldStyle(){

        $this->api->pathfinder->base_location->defineContents(array(
            'template'=>'atk4/compat/templates',
        ));
        $this->useOldTemplateTags();


        return $this;
    }

    /** Forces Agile Toolkit to use <?tag?> templates instead of {tag} */
    function useOldTemplateTags(){

        $this->api->setConfig(array('template'=>array(
            'ldelim'=>'<\?',
            'rdelim'=>'\?>',
        )));

        if($this->api->template) {
            // reload it

            $this->api->template->settings=array_merge(
                $this->api->template->getDefaultSettings(),
                $this->api->getConfig('template',array())
            );
            $this->api->template->reload();
        }
        return $this;
    }

    /** Forces Agile Toolkit to use SMLite instead of the new one */
    function useSMLite() {

        return $this;
    }

    /** Agile Toolkit 43 expects public/atk4 to point to atk4/public/atk4. If
     * you can't do that, oh well */
    function useNoPublic() {
        $this->api->pathfinder->base_location->defineContents(array(
            'public'=>'atk4/public/atk4',
            'js'=>'atk4/public/atk4/js',
        ));
        $this->api->pathfinder->base_location->setBaseURL($this->api->pm->base_url);
        return $this;
    }
}
