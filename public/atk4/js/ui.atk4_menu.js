/* Welcome to Agile Toolkit JS framework. This file implements multi-level menu. */

// The following HTML structure should be used:
//
// <ul>     <!-- binding to this element -->
//   <li class="current">
//      <a href="link.html">label</a>
//      <div>   <!-- Optional. Will be displayed only if <li> is current -->
//        ...
//          <a href="otherlink.html">
//
// TODO: implement soloction of currently loaded page

$.widget("ui.atk4_menu", {

	options: {
		target: '#Content',
		submenus: false,
		cut_object: false
	},

    _create: function(){
        var self=this;

		self.element.addClass('atk4_menu');

		this.element.find('a').click(function(e){
			e.preventDefault();
			
			if(self.options.submenus){
				if(self.options.submenu_aciton){
					self.options.submenu_aciton();
				}else{
					$(this).closest('ul,.menu,.header_subnav').
					    find('.submenu-active').
						removeClass('submenu-active').
						addClass('submenu').hide();
				}
			}
				
			var url=$(this).attr('href');

			if($(this).attr('data-hash')){
				// TODO: implement hash adding / functionality / clicking.
			}


			/*
			*/


			// Cutting object as we show content
			if(self.options.cut_object)
				url={ 0: url, cut_object: self.options.cut_object, cut_page: null };

			var t=this;
			$(self.options.target).atk4_load(url,function(){
				self.element.find('.current').removeClass('current');
				$(t).addClass('current');
			});
        });

		// check anchor and load proper page..

/*
        $('.atk4-link').atk4_click(function(url){
            //$.atk4.page(url);
			$('#Content').atk4_load2(url);
		});
*/

//		this.element.find('a').eq(0).click();

/*
		var h=window.location.hash;
		if(!h){
			var a=document.location.pathname.substr(1).replace('/','|').replace('.html','');
			h='#'+a;
			h=h.replace('|','/').substr(1)+'.html';
			var el=this.element.find('a[href='+h+']');
			if(el.length){
				var fn=$.atk4.readyLast;
				el.closest('li').find('a').eq(0).click();
				el.addClass('current').parent().addClass('current');
				//menu.click(el,h,fn);
			}
		}else{
			h=h.replace('|','/').substr(1)+'.html';
			this.openPage(h);
		}
*/
    },

	openPage: function(h,fn){
		menu=this;
		console.log('looking for ',h);
		var el=this.element.find('a[href='+h+']');
		if(el.length){
			if(!fn)fn=$.atk4.readyLast;
			$.atk4.readyLast=undefined;
			el.closest('li').find('a').eq(0).click();
			menu.click(el,h,fn);
		}
	},

    // Main menu clicking function
    click: function(el,url,fn){
        if(el.hasClass('current')){
			this.onClick(el,url,fn);
			return;
		}

        // First - we need to find adjustent links on same level and diactivate them
        el.parent().parent().find('.current').each(function(){
            // We need to find links on same level and diactivate them
            var next=$(this).removeClass('current').filter('a').next();
            if(next.is('div'))next.hide();
        });
        el.addClass('current').parent().addClass('current');
        var next=el.addClass('current').next();

        if(next.is('div'))next.show();

        this.onClick(el,url,fn);
    },

    onClick: function(el,url,fn){
        if(this.options['callback']){
            this.options['callback'].call(el,url,fn);
        }else{
			if(el.hasClass('nav_item'))return;
            if(url!='#')$('#Content').atk4_load(url,fn);
        }
    }
});

