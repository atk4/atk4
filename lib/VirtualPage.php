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
 *          $page->add('Text')->set('ID='.$_GET['edit']);
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
     * Returns if the URL is requesting the page to be shown
     *
     * @param string $arg Optionally ask for specific argument
     *
     * @return boolean 
     */
    function isActive($arg = null)
    {
        if ($arg && isset($_GET[$this->name])) {
            return $_GET[$this->name] == $arg;
        }
        return @$_GET[$this->name];
    }

    /**
     * Bind owner's event (click by default) to a JavaScript chain
     * which would open a new frame (or dialog, depending on $type
     * property), and execute associated code inside it
     *
     * @param string $title Title of the frame
     * @param string $event JavaScript event
     *
     * @return VirtualPage $this
     */
    function bindEvent($title = '', $event = 'click')
    {
        $t=$this->type;
        $this->owner->js($event)->univ()->$t($title,$this->getURL());
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
                $page=$self->api->add(
                    $self->page_class,
                    null,
                    null,
                    $self->page_template
                );

                $self->api->cut($page);
                $self->api->stickyGET($self->name);
                call_user_func($method, $page);
                $self->api->stickyForget($self->name);
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

        $this->api->_removeElement($this->api->page_object->short_name);

        $this->api->page_object = $this->page = $this->api->add(
            $this->page_class,
            null,
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

        $grid->addColumn('template', $name, $buttontext?:$title)
            ->setTemplate(
                '<button type="button" class="pb_'.$name.'">'.
                htmlspecialchars(
                    $buttontext?:$title?:ucwords(
                        str_replace('_', ' ', $name)
                    )
                ).
                '</button>'
            );

        $grid->columns[$name]['thparam'].=' style="width: 40px; text-align: center"';

        $grid->js(true)->_selector('#'.$grid->name.' .pb_'.$name)->button();
        $t=$this->type;
        $grid->js('click')->_selector('#'.$grid->name.' .pb_'.$name)->univ()
            ->$t($title, array($this->getURL($name),
                $name=>$grid->js()->_selectorThis()->closest('tr')->attr('data-id')
            ), $this->frame_options);
        return $this;
    }
}
