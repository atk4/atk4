<?php

class Controller_Compat42 extends AbstractController
{
    public function init()
    {
        parent::init();

        $this->app->compat_42 = $this;
    }

    /** Forces Agile Toolkit to use 4.2-compatible stylesheets and templates */
    public function useOldStyle()
    {
        $this->app->pathfinder->base_location->defineContents(array(
            'template' => 'atk4/compat/templates',
        ));
        $this->useOldTemplateTags();

        return $this;
    }

    /** Forces Agile Toolkit to use <?tag?> templates instead of {tag} */
    public function useOldTemplateTags()
    {
        $this->app->setConfig(array('template' => array(
            'ldelim' => '<\?',
            'rdelim' => '\?>',
        )));

        if ($this->app->template) {
            // reload it

            $this->app->template->settings = array_merge(
                $this->app->template->getDefaultSettings(),
                $this->app->getConfig('template', array())
            );
            $this->app->template->reload();
        }

        return $this;
    }

    /** Forces Agile Toolkit to use SMLite instead of the new one */
    public function useSMLite()
    {
        return $this;
    }

    /** Agile Toolkit 43 expects public/atk4 to point to atk4/public/atk4. If
     * you can't do that, oh well */
    public function useNoPublic()
    {
        $this->app->pathfinder->base_location->defineContents(array(
            'public' => 'atk4/public/atk4',
            'js' => 'atk4/public/atk4/js',
        ));
        $this->app->pathfinder->base_location->setBaseURL($this->app->pm->base_url);

        return $this;
    }
}
