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
	
	/*
	 base_url will contain URL which will be used to refresh contents.
	*/
	base_url: undefined,
	
	/*
	 loading will be set to true, when contents of this widgets are being
	 loaded.
	*/
	loading: false,
	
	/*
	 when we are loading URLs, we will automaticaly pass arguments to cut stuff out
	*/
	cut_mode: 'page',
	cut: '1',
	
	/*
	 Helper contains some extra thingies
	*/
	helper: undefined,
	
	_init: function(){
		
		var self=this;
		/*
		this.options.debug=true;
		this.options.anchoring=true;
		*/
		
		this.element.addClass('atk4_loader');
		 
		 console.log("OPTioNS:",this.options);
		if(this.options.debug){
			var d=$('<div/>');
			d.css({background:'#fe8',border: '1px solid black',position:'absolute',width:'100px',height:'50px'});
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
			$('<a/>').attr('title','Successful close').attr('href','javascript: void(0)').text('OK')
				.click(function(){ self.successClose()}).appendTo(d);
			d.append(' ');
			$('<a/>').attr('title','Canceled close').attr('href','javascript: void(0)').text('Cancel')
				.click(function(){ self.cancelClose()}).appendTo(d);
				
			d.insertBefore(self.element);
			d.draggable();
			self.helper=d;
			self.element.css({border:'1px dashed green'});
		}
		console.log('init done');
	},
	destroy: function(){
		var self=this;
		if(self.parent_loader){
			self.parent_loader.unbind('atk4_loaderbeforeclose.'+self.element.attr('id'));
		}
		this.element.removeClass('atk4_loader');
		if(this.helper){
			this.helper.remove();
			this.helper=undefined;
		}
		//$.Widget.prototype.destroy.apply( this, arguments );

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
		//
		// Consider using beforeClose event, which will not be used
		// on initial load.
		//if(false===self._loadingRegion()){
			//console.log('Page loading was canceled loadingStart trigger');
//		}
		if(self.loading){
			$.univ().loadingInProgress();
			return false;
		}
		
		self.loading=true;
        $.atk4.get(url,function(res){
			self.loading=false;
			/*
			if(res.substr(0,13)=='SESSION OVER:'){
				$.univ.dialogOK('Session over','Your session have been timed out',function(){ document.location='/'});
				return;
			}
			*/
            var scripts=[], source=res;


            while((s=source.indexOf("<script"))>=0){
                s2=source.indexOf(">",s);
                e=source.indexOf("</script",s2);
                e2=source.indexOf(">",e);


                scripts.push(source.substring(s2+1,e));
                source=source.substring(0,s)+source.substring(e2+1);
            }

			var m=el;
			m.hide();
			
			// Parse into Document
			var n=$('<div/>').append(source).children();
			
			//console.log('New content: ',n[0]);
			
//			console.log("Load check", el.attr('id'),' vs ',n.attr('id'));
			
			if(n.length==1 && (reload || (n.attr('id') && n.attr('id')==el.attr('id')))){
				console.log('RELOAD');
				n=n.children();
			}else if(reload){
				console.error('Cannot reload content: ',reload,n[0],n[1],n[2]);
				
			}
			el.empty();
			
			n.each(function(){
//				console.log('iterating through ',this);
				$(this).remove().appendTo(el);
			});
			
			
			/*
			if(reload){
				el.replaceWith(selector?$('<div/>').append(source).find(selector):source);
			}else{
				el.html(selector?$('<div/>').append(source).find(selector):source);
			}
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
			m.show();
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
	
	reload: function(){
		console.log('reload');
		this.loadURL(this.base_url);
	},
	remove: function(){
		var self=this;
		console.log('called REMOVE');
		self.helper && self.helper.css({background:'red'});
		if(false === self._trigger('beforeclose')){
			self.helper.css({background:'#fe8'});
			return false;
		}
		return true;
	},
	successClose: function(){
		console.log('successClose');
	},
	cancelClose: function(){
		console.log('cancelClose');
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
		
		console.log('triggering beforeClose',self.element[0]);
		self.helper && self.helper.css({background:'red'});
		if(false === self._trigger('beforeclose')){
			self.helper.css({background:'#fe8'});
			return false;
		}
		self.helper && self.helper.css({background:'#fe8'});
		self.base_url=url;
		
		url=$.atk4.addArgument(url,"cut_"+self.cut_mode+'='+self.cut);
		this._loadHTML(self.element,url,fn,strip_layer);
		console.log('loading complete for ',self.element[0]);
	}
	
});

$.extend($.ui.atk4_loader, {
	getter: 'remove'
});

$.fn.extend({
	atk4_load: function(url,fn){
		this.atk4_loader()
			.atk4_loader('loadURL',url,fn)
			;
	},
	atk4_reload: function(url,arg,fn){
        if(arg){
            $.each(arg,function(key,value){
                url=$.atk4.addArgument(url,key+'='+value);
            });
        }
		this.atk4_loader()
			.atk4_loader('loadURL',url,fn,true)
			;
	}
});
