<?php
/**
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 * Normal pages are added by Application and are tied to the certain URL as
 * dictated by the Application class. Sometimes, however, you would have a need
 * to create page within a page. That's when you are building your custom View
 * or add-on - you want that page.
 *
 * VirtualPage gives you a unique URL to the page where you can add objects.
 *
 * VirtualPage is intelligent enough to act differently depending on where you
 * add it.
 *
 * This way you can create popup on page load.
 *  $vp = $this->add('VirtualPage');
 *  $this->js(true)->univ()->frameURL('MyPopup',$vp->getURL());
 *  $vp->set(function($vp){
 *      $vp->add('LoremIpsum'); // <-- will appear within a frame
 *  });
 *
 * Here is how you can bind it to a button or other view through JS element.
 * Calling "bindEvent" will automatically tie in frameURL for you.
 *  $b=$page->add('Button')->set('Open popup');
 *  $b->add('VirtualPage')
 *      ->bindEvent('My Cool Title','click')
 *      ->set(function($page){
 *          $page->add('LoremIpsum');
 *      });
 *
 * This would add a button into a Grid which would show you row's ID.
 *  $grid->add('VirtualPage')
 *      ->addColumn('edit')
 *      ->set(function($page){
 *          $id = $_GET[$page->short_name.'_id'];
 *          $page->add('Text')->set('ID='.$id);
 *      });
 *
 * There are many other uses for VirtualPage, especially when you extract
 * it's URL.
 *
 *  $b = $page->add('Button')->set('Request new password');
 *  $b->js('click')->univ()->location(
 *      $this->add('VirtualPage')
 *          ->set(function($p){
 *              $p->add('Text')->set('Check your email for confirmation');
 *          })
 *          ->getURL()
 *  );
 *
 * (If you do this, be mindful of stickyGET arguments and don't call
 *   from inside form's submit code
 */
class VirtualPage extends AbstractController
{
    public $type='frameURL';
    public $page_template=null;
    public $page_class='Page';

    public $frame_options=null;

    protected $page;


    /**
     * Return the URL which would trigger execution of the associated
     * code within a separate page
     *
     * @param string $arg Argument to pass to the page
     *
     * @return URL object
     */
    function getURL($arg = 'true')
    {
        return $this->api->url(null, array($this->name => $arg));
    }

    /**
     * Returns if the URL is requesting the page to be shown.
     * If no parameter is passed, then return active page mode.
     *
     * @param string $arg Optionally ask for specific argument
     *
     * @return boolean|string
     */
    function isActive($arg = null)
    {
        if ($arg && isset($_GET[$this->name])) {
            return $_GET[$this->name] == $arg;
        }
        return isset($_GET[$this->name]) ? $_GET[$this->name] : false;
    }

    /**
     * Bind owner's event (click by default) to a JavaScript chain
     * which would open a new frame (or dialog, depending on $type
     * property), and execute associated code inside it
     *
     * @param string $title    Title of the frame
     * @param string $event    JavaScript event
     * @param string $selector Not all parent will respond to click but only a selector
     *
     * @return VirtualPage $this
     */
    function bindEvent($title = '', $event = 'click', $selector = null)
    {
        $t=$this->type;
        if (is_null($event)) $event = 'click';
        $this->owner->on($event, $selector)->univ()->$t($title,$this->getURL(),$this->frame_options);
        return $this;
    }

    /**
     * Associates code with the page. This code will be executed within
     * a brand new page when called by URL.
     *
     * @param callable $method_or_arg Optional argument
     * @param callable $method        function($page){ .. }
     *
     * @return VirtualPage $this
     */
    function set($method_or_arg, $method = null)
    {

        $method = is_callable($method_or_arg)?$method_or_arg:$method;
        $arg    = is_callable($method_or_arg)?null:$method_or_arg;

        $self=$this;

        if ($this->isActive($arg)) {
            $this->api->addHook('post-init', function () use ($method, $self) {
                $page = $self->getPage();
                $page->id=$_GET[$self->name.'_id'];
                $self->api->stickyGET($self->name.'_id');

                try {
                    call_user_func($method, $page, $self);
                } catch (Exception $e){
                    throw $e;
                    // exception occured possibly due to a nested page. We
                    // are already executing from post-init, so
                    // it's fine to ignore it.
                }

                //Imants: most likely forgetting is not needed, because we stop execution anyway
                //$self->api->stickyForget($self->name.'_id');
                //$self->api->stickyForget($self->name);
            });
            throw $this->exception('', 'StopInit');
        }
        return $this;
    }

    /**
     * Simply returns a page we can put stuff on. This page would
     * be displayed instead of regular page, so beware.
     *
     * @return Page page to be displayed
     */
    function getPage()
    {
        // Remove original page

        if ($this->page) {
            return $this->page;
        }

        $this->api->page_object->destroy(false);


        $this->api->page_object = $this->page = $this->api->add(
            $this->page_class,
            $this->name,
            null,
            $this->page_template
        );
        $this->api->stickyGET($this->name);
        return $this->page;
    }

    /**
     * Call this if you are adding this inside a grid.
     *
     * @param string $name       Field Name (must not contain spaces)
     * @param string $title      Header for the column
     * @param string $buttontext Text to put on the button
     * @param string $grid       Specify grid to use, other than $owner
     *
     * @return VirtualPage $this
     */
    function addColumn($name, $title = null, $buttontext = null, $grid = null)
    {
        if (!$grid) {
            $grid=$this->owner;
        }

        if(!is_array($buttontext)) {
            $buttontext = array();
        }
        if(!$buttontext['descr'])$buttontext['descr']=$title?:ucwords(str_replace('_', ' ', $name));

        $icon='';
        if($buttontext['icon']) {
            if($buttontext['icon'][0]!='<') {
                $icon.='<i class="icon-'.
                    $buttontext['icon'].'"></i>';
            }else{
                $icon.=$buttontext['icon'];
            }
            $icon.='&nbsp;';
        }

        $grid->addColumn('template', $name, $buttontext?:$title)
            ->setTemplate(
                '<button type="button" class="atk-button-small pb_'.$name.'">'.
                    $icon.$this->app->encodeHtmlChars($buttontext['descr']).
                '</button>'
            );

        $grid->columns[$name]['thparam'].=' style="width: 40px; text-align: center"';

        //$grid->js(true)->_selector('#'.$grid->name.' .pb_'.$name)->button();
        $t=$this->type;
        $grid->js('click')->_selector('#'.$grid->name.' .pb_'.$name)->univ()
            ->$t($title, array($this->getURL($name),
                $this->name.'_id'=>$grid->js()->_selectorThis()->closest('tr')->attr('data-id')
            ), $this->frame_options);
        return $this;
    }
}
