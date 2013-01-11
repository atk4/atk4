<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class Menu_Light extends AbstractView {
    /*
       Sometimes you want to have a HTML-based menu, and all you need PHP to do is to highlight current page.
       It's not that simply always, sometimes there are sub-pages also. This class solves the problem.

       Lets build a structure which adds asterik next to page. First we need to specify that the object to be
       coppied around is an asterik. (in your case it might be a class name)

       <?current?>*<?/?>

       Next put self closing tags in form "current_" + page exactly where you want the current to appear

       services <?$current_services?>
       consulting <?$current_services_consulting?>
       mentoring <?$current_services_consulting?>
       products <?$current_products?>
       about <?$current_about?><?$current_contact?>

       If you go to page servcies, the asterisk will be placed next to "services". If you are on the page services/mentoring
       then asterik will appear next to services AND consulting lines. 

       Line with "about" will have asterik if you are either on 'about' or 'contact' page. That's all you need to know to use
       this.

       To add thhis to your application use $this->add('Menu_Light',null,'Menu');
     */
    function render(){
        $c=$this->template->get('current');
        $this->template->del('current');

        $parts=explode('_',$this->api->page);

        $matched=false;
        while($parts){
            $tag=implode('_',$parts);

            if($this->template->is_set('current_'.$tag)){
                $this->template->trySet('current_'.$tag,$c);
                $matched=true;
            }
            array_pop($parts);
        }

        if(!$matched){
            $this->template->set('current',$c);
        }
        parent::render();
    }
    function defaultTemplate(){
        return $this->spot;
    }
}
