/* Welcome to Agile Toolkit JS framework. This is a main file which provides extensions on top of jQuery-UI */

/*
 @VERSION

 ATK4 Loader introduces a widget, which is used instead of default $('..').load() method.

 The new widget adds a number of useful features including:
  * verifying loaded content against session timeouts or errors
  * provides better ability to refresh content
  * handler support. What if loaded content contains unsaved data?
  */

/*
 In a complex use case you would be doing the following:

 1. For the element which you wish to be a selector, initialise this widget
 2. Set a current URL for that element during initialisation (base_url);
 3. Define what should happen if user clicks "cancel"
 4. Define what should happen if user finishes action successfuly inside
  4a. succesful action may pass arguments.


 $('#MyDiv')
   .atk4_loader({url: '/page.html'})
   .atk4_loader({})

 THE STRUCTURE OF THIS WIDGET MIGHT BE REWRITTEN

*/

$.widget('ui.atk4_loader', {

    options: {
        /*
        base_url will contain URL which will be used to refresh contents.
        */
        base_url: undefined,

        /*
        loading will be set to true, when contents of this widgets are being
        loaded.
        */
        loading: false,
        cogs: '<div id="banner-loader" class="atk-banner atk-cells atk-visible"><div class="atk-cell atk-align-center atk-valign-middle"><div class="atk-box atk-inline atk-size-zetta atk-banner-cogs"></div></div></div>',

        /*
        when we are loading URLs, we will automaticaly pass arguments to cut stuff out
        */
        cut_mode: 'page',
        cut: '1',
        history: false
    },

    /*
    Helper contains some extra thingies
    */
    helper: undefined,
    loader: undefined,

    showLoader: function(){
        if(!!this.loader) this.loader.show();
    },
    hideLoader: function(){
        if(!!this.loader) this.loader.hide();
    },

	_create: function(){

		var self=this;
		/*
		this.options.debug=true;
		this.options.anchoring=true;
		*/
        this.element.addClass('atk4_loader');

        if(this.options.url){
            this.base_url=this.options.url;
        }
        if(this.options.cut_object){
            this.cut_mode='object';
            this.cut=this.options.cut_object;
        }

        if(this.options.cogs){
            var l=$(this.options.cogs);
			l.prependTo(self.element);
            self.loader=l;
            self.hideLoader();
        }

        if(this.options.history){
            $(window).bind('popstate', function(event){
                var state = event.originalEvent.state;

                if (location.href != self.base_url && self.base_url) {
                    self.options.history=false;
                    self.loadURL(location.href,function(){
                        self.options.history=true;
                    });
                }
            });

        }

		if(this.options.debug){
			var d=$('<div style="z-index: 2000"/>');
			d.css({background:'#fe8',border: '1px solid black',position:'absolute',width:'100px',height:'50px'});

			$('<div/>').text('History: '+(this.options.history?'yes':'no')).appendTo(d);

			$('<a/>').attr('title','Canceled close').attr('href','javascript: void(0)').text('X').css({float:'right'})
				.click(function(){ $(this).closest('div').next().css({border:'0px'});$(this).closest('div').remove(); }).appendTo(d);
			d.append(' ');
			$('<a/>').attr('title','Reload this region').attr('href','javascript: void(0)').text('Reload')
				.click(function(){ self.reload()}).appendTo(d);
			d.append(' ');
			$('<a/>').attr('title','Show URL').attr('href','javascript: void(0)').text('URL')
				.click(function(){ alert(self.base_url)}).appendTo(d);
			d.append(' ');
			$('<a/>').attr('title','Attempt to remove').attr('href','javascript: void(0)').text('Remove')
				.click(function(){ self.remove()}).appendTo(d);
			d.append(' ');

			d.insertBefore(self.element);
			d.draggable();
			self.helper=d;
			self.element.css({border:'1px dashed green'});
		}
	},
	destroy: function(){
		var self=this;

		this.element.removeClass('atk4_loader');
		if(this.helper){
			this.helper.remove();
			this.helper=undefined;
		}
		if(this.loader){
			this.loader.remove();
			this.loader=undefined;
		}
	},


    /*
	 This function fetches block of HTML from the server and puts it
	 inside specified element. This function is very similar to
	 $('..').load('http://'), however it improves evaluation
	 of scripts supplied inside loaded chunk.
	*/
    _loadHTML: function(el, url, callback, reload){
		var self=this;

		// We preserve support for selectors in URL,
		// as compatibility with jQuery, however avoid
		// using this. ATK4 will gladly render part of
		// the page for you. (cut_object, cut_region, cut_page)
		var selector, off = url.indexOf(" ");
		if ( off >= 0 ) {
			selector = url.slice(off, url.length);
			url = url.slice(0, off);
		}

		// Before actual loading start, we call a method, which might want
		// to display loading indicator somewhere on the page.
		if(self.loading){
			$.univ().loadingInProgress();
			return false;
		}
		var m;

		self.loading=true;
        self.showLoader();
        $.atk4.get(url,null,function(res){
			/*
			if(res.substr(0,13)=='SESSION OVER:'){
				$.univ.dialogOK('Session over','Your session have been timed out',function(){ document.location='/'});
				return;
			}
			*/

            if(self.options.history)window.history.pushState({path: self.base_url}, 'foobar', self.base_url);

            var scripts=[], source=res;

            while((s=source.indexOf("<script"))>=0){
                s2=source.indexOf(">",s);
                e=source.indexOf("</script",s2);
                e2=source.indexOf(">",e);


                scripts.push(source.substring(s2+1,e));
                source=source.substring(0,s)+source.substring(e2+1);
            }

			m=el;
			//if(!(jQuery.browser.msie))m.hide();

			// Parse into Document
			var source=$('<div/>').append(source);
			var n=source.children();

			var oldid=el.attr('id');
			if(n.length==1 && (reload || (n.attr('id') && n.attr('id')==oldid))){
				el.removeAttr('id');
				// Only one child have been returned to us. We also checked ID's and they match
				// with existing element. In this case we will be copying contents of
				// provided element
				//n=n.contents();
				el.triggerHandler('remove');
				n.insertAfter(el);
				el.remove();
		   		// http://forum.jquery.com/topic/jquery-empty-does-not-destroy-ui-widgets-whereas-jquery-remove-does-using-ui-1-8-4
			}else{
				// otherwise we will be copying all the elements (including text)
				if(reload){
					console.error('Cannot reload content: ',reload,n[0],n[1],n[2]);
				}
				el.empty();
				n=source.contents();
				n.each(function(){
					$(this).remove().appendTo(el);
				});
			}

			el.atk4_loader({'base_url':url});

			/*
			*/

            for(var i in scripts){
				try{
					window.region=el;
					if(eval.call)eval.call(window,scripts[i]);else
					// IE-pain
					with(window)eval(scripts[i]);
				}catch(e){
					console.error("JS:",e,scripts[i]);
				}
            };

			if(callback)$.atk4(callback,true);
			$.atk4(function(){
				m.show();
				var f=m.find('form:first').find('input:visible,select:visible').eq(0);
                if(!f.hasClass('nofocus'))f.focus();
			});
		},function(){	// second callback, which is always called, when loading is completed
            self.hideLoader();
			self.loading=false;
            el.trigger('after_html_loaded');
		});
    },
	/*
	 This function is called before HTML loading is started. Redifine it
	 or bind to enhance it's functionality
	*/
	_loadingStart: function(){
		var self=this;
		if(false === self._trigger('loadingStart')){
			return false;
		}
	},

	reload: function(args){
		var url=this.base_url;
		if(args)this.base_url=$.atk4.addArgument(this.base_url,args);
		this.loadURL(this.base_url);
		this.base_url=url;
	},
	remove: function(){
		var self=this;
		self.helper && self.helper.css({background:'red'});
		//if(false === self._trigger('beforeclose')){
		if(self.element.find('.form_changed').length){
			if(!confirm('Changes on the form will be lost. Continue?'))return false;
		}
		self.element.find('.form_changed').removeClass('form_changed');
		return true;
	},
	setURL: function(url){
		var self=this;

		self.base_url=url;
	},
	loadURL: function(url,fn,strip_layer){
		/*
		 Function provided mainly for compatibility. It will load URL in the selector
		 and will set "fn" to fire off when loading is complete

		 Sometimes you would want to reload (atk4_reload) an element. This means,
		 you will receive the same element from AJAX. New element comes with the
		 same ID and sub-elements. What we have to do is copy children from the
		 received data into existing element.
		*/
		var self = this;

		if(self.loading){
			$.univ().loadingInProgress();
			return false;
		}

		//if(false === self._trigger('beforeclose')){
		if(self.element.find('.form_changed').length){
			if(!confirm('Changes on the form will be lost. Continue?'))return false;
		}
		// remove error messages
		$('#tiptip_holder').remove();
		self.base_url=url;
		url=$.atk4.addArgument(url,"cut_"+self.options.cut_mode+'='+self.options.cut);
		this._loadHTML(self.element,url,fn,strip_layer);
	}

});

$.extend($.ui.atk4_loader, {
	getter: 'remove'
});

$.fn.extend({
	atk4_load: function(url,fn){
        this.atk4_loader().atk4_loader('loadURL',url,fn);
	},
	atk4_reload: function(url,arg,fn){
        if(arg){
            $.each(arg,function(key,value){
                url=$.atk4.addArgument(url,key+'='+encodeURIComponent(value));
            });
        }
		this.atk4_loader()
			.atk4_loader('loadURL',url,fn,true);
	}
});
