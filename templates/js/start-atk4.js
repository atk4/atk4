/*
 Welcome to Agile Toolkit JS framework.

 This is a main file which provides core functionality.

 ATK4 Initialisation Script.

 Usage:

 // in $api->init():

  $this->add('jUI');
  $this->js(true)->_load('start-atk4');
*/

;jQuery.atk4||(function($){

/*

 $.atk4 is a function, which acts as an enhanced onReady handler.
 Syntax:

$(function(){

 $.atk4.includeJS('js/mylib.js');

 $.atk4(funciton(){
	mylib();
 });

})


*/
$.atk4 = function(readycheck,lastcall){
    return $.atk4._onReady(readycheck,lastcall);
};

/*
 This is initial support for univ chain. Univ chain is a part of ATK4 framework, and you
 can optionally extend it by adding your own functions
*/
$.univ = function(){
	var ignore=true;
	$.univ.ignore=false;
	return $.univ;
};
$.univ._import=function(name,fn){
	$.univ[name]=function(){
		var ret;
		if(!$.univ.ignore){
			ret=fn.apply($.univ,arguments);
		}
		return ret?ret:$.univ;
	}
}

/*
 ATK4 Library initialisation
*/

$.extend($.atk4,{
    verison: "2.0",

	// Is this a production environment?
	production: false,

	/* It's posible that we might get called multiple times. Be aware */
    initialised: false,

	///////////////////////// ERROR HANDLING //////////////////////////

	/*
	 We store the knowledge whether this is production environment
	 or not. We also provide a way how user will be notified about
	 critical JS errors
	*/
	/*
	 Something has gone wrong. We should display error to the user.
	*/
	errorMessage: function(msg,moreinfo){
		if(this.production){
			console.log('Surpressed user error in production environment',text);
			return;
		}

        w=window.open(null,null,'height=400,width=700,location=no,menubar=no,scrollbars=yes,status=no,titlebar=no,toolbar=no');
        if(w){
            w.document.write('<h2>JavaScript Error: '+e+'</h2>');
            w.document.write(response_text);
            w.document.write('<center><input type=button onclick="window.close()" value="Close"></center>');
        }else{
            showMessage("JavaScript Error: "+e+"\n"+response_text);
        }
	},
	/*
	 This function is used to display success messages. This should probably be
	 redefined and should make appear pretty for the user

	 Set 2nd argument to true, if it's a system message, which most likely will
	 not be interesting to the user on production environment
	*/
	successMessage: function(msg,system){
		if(system && this.production)return;
		console.log('Success: ',msg);
	},


	//////////////////////////////// AJAX ///////////////////////////////////
	/*
	 This is lowest-level AJAX function. It will attepmt to get document from
	 the server and perform some very basic validation. For instance it will
	 check if server's session is expired or if it returns error.

	 Other modules of ATK4 should rely on this function. If you want to
	 load your own AJAX, use $('..').atk4_load() instead.

	 NOTE: get does not assume regarding the type of returned data.
	*/
    loading: 0,	// How many files are currently being requested

	// readyList contains array of functions which must be executed
	// after loading is complete. Note that some of those functions
	// may request more files to be loaded.
    _readyList: [],

	// readyLast is a function which will be executed when all the
	// files are completed. It is used to turn off loading indicator
	_readyLast: undefined,

	// Server-side implements session timeout
	_refreshTimeout: function(){
		if(document.session_timeout){
			if(document.session_timeout_timer1)clearTimeout(document.session_timeout_timer1);
			if(document.session_timeout_timer2)clearTimeout(document.session_timeout_timer2);

			document.session_timeout_timer1=setTimeout(function(){
				if($.univ && $.univ().successMessage){
					$.univ().successMessage('Your session will expire in 1 minute due to lack of activity');
				}

			},(document.session_timeout-1)*60*1000);

			document.session_timeout_timer2=setTimeout(function(){
				if($.univ()){
					$.univ().dialogOK('Session timeout','You have been inactive for '+document.session_timeout+' minutes. You will need to log-in again',function(){ document.location='/' });
				}else{
					alert('Your session have expired');document.location='/';
				}
			},(document.session_timeout)*60*1000);
		}
	},

	// If url is an object {..} then it's passed to ajax as 1st argument


	get: function(url, data, callback, load_end_callback, post){
        var self=this;
		if($.isFunction(data)){
			// data argument may be ommitted
            callback=data; data=null;
        }
		var timeout=setTimeout(function(){
			self._stillLoading(url);
		},2000);

		if(typeof(url)=="object" && url[0])url=$.atk4.addArgument(url);
		if(typeof(url)=="string")url={url:url};

		// Another file is being loaded.
        this.loading++;
        return $.ajax($.extend({
            type: post?"POST":"GET",
			dataType: 'html',
            data: data,
			// We tell the backend that we will verify output for "TIMEOUT" output
			beforeSend: function(xhr){xhr.setRequestHeader('X-ATK4-Timeout', 'true');},

            success: function(res){
					clearTimeout(timeout);
					$.atk4._refreshTimeout();
					load_end_callback && load_end_callback();
					$.atk4._checkSession(res) && callback && callback(res);
		            if(!--$.atk4.loading)$.atk4._readyExec();
				},
            error: function(a,b,c){
					clearTimeout(timeout);
					$.atk4._refreshTimeout();
					load_end_callback && load_end_callback();
					$.atk4._ajaxError(url,a,b,c);
		            if(!--$.atk4.loading)$.atk4._readyExec();
					// kill readycheck handlers by not reducing
					// the counter.
				}
        },url));
    },
	_stillLoading: function(url){
		if(this.loading){
			console.log('Slow loading of: ',url,'remaining:',this.loading);
			$('#loading_screen1,#loading_screen2').fadeIn('fast');
			$.atk4(function(){
				$('#loading_screen1,#loading_screen2').stop().hide();
			});
		}
	},
	/*
	 Use $.atk4.prototype to redeclare below 2 functions to your liking.
	*/
    _ajaxError: function(url,a,b,c){
		console.error("Failed to load file: ",url," (",a,b,c,")");
    },
    _checkSession: function(text){
		// TODO: use proper session handling instead
        if(text.substr(0,7)=="ERROR: "){
			var msg=text.substr(7);
			alert(msg);
			return false;
		}
        if($.trim(text)=="SESSION TIMEOUT"){
            alert('session has timed out');
            document.location="/";
            return false;
        }
        return true;
    },
	/*
	 queues function to be executed when loading are complete.
	 If "lastcall" is specified as true, then function will be
	 executed after everything else. Only one function can be
	 specified as lastCall.

	 If nothing is being loaded, then functions are executed
	 immediatelly
	*/
    _onReady: function(fn,lastcall){
		if(lastcall){
			if(!this.loading){
				fn.call(document);
			}else{
				if(this._readyLast){
					var prev=this._readyLast;
					// call both functions if one is already there
					this._readyLast=function(){
						prev();
						fn();
					}
				}else{
					this._readyLast=fn;
				}
			}
			return;
		}
        if(!this.loading){
            fn.call(document);
        }else{
            this._readyList.push(fn);
        }
    },
	/*
	 if _onReady functions were not executed immediatelly, then
	 this function will be called at the end and will execute them
	 all in order. If any of the functions will start loading
	 more files, execution will terminate and will be resumed
	 after all files are loaded again.
	*/
    _readyExec: function(){
        while(this._readyList.length){
            fn=this._readyList.shift();
            fn.call(document);

			// We are loading more data, resume after
			if($.atk4.loading)return;
        }
		if(this._readyLast){
			var x=this._readyLast;
			this._readyLast=undefined;
			x.call(document);
		}
    },

	//////////////////// Dynamic Includes (CSS and JS) ////////////////

	/*
	 Based on get() we add number of functions to dynamically load
	 JS and CSS files.
	*/


	// Lists of files we have already loaded. This is to ensure we do
	// not include JS and CSS files more than once
    _includes: {},

	// Loads javascript file and evals it
    includeJS: function(url,nocache){

		// Perhaps file is already included. We do not to load it twice
        if(this._isIncluded(url) && !nocache)return;

		// Continue with loading
        this.get(url,function(code){
            //$.globalEval(code);
            try{
                eval(code);
            }catch(e){
				// For non-production we better try to expose faulty code
				// through browser JS parser
				if(String(e).indexOf("Parse error")){
					if($.atk4.production){
						console.log("Eval failed for "+url);
					}else{
						console.error("Eval failed for "+url+", trying to include directly for debugging");
	                   $.atk4._evalJS(url)
					}
                }else console.error("Eval error: "+e);
            }
        });
    },
	// Use browser to natively include JS
    _evalJS: function(url,clean) {
		// remove previously evaled piece of code
        var old = document.getElementById('atk4_eval_clean');
        if (old != null) {
            old.parentNode.removeChild(old);
            delete old;
        }
        var head = document.getElementsByTagName("head")[0];
        var script = document.createElement('script');
        if(clean){
            script.id = 'atk4_eval_clean';
        }
        script.type = 'text/javascript';
        script.src = url;
        head.appendChild(script);
    },
	/*
	 This function will dynamically load CSS file.
	 Also relative URLs like url(../images) will not break.
	*/
    includeCSS: function(url){
		if(this._isIncluded(url))return;

		$("<link>", {
			rel: "stylesheet",
			type: "text/css",
			href: url
		}).appendTo("head");
    },
    _isIncluded: function(url){
        if(this._includes[url])return true;
        this._includes[url]=true;
        return false;
    },

	//////////////////////////// MISC //////////////////////////////////

	/*
	 Utility function. When you give it an URL, and argument, it will
	 append argument to the URL.

	 TODO: this function is incomplete. It should also check if argument is
	 already in the URL and handle that properly.

	 See also: http://api.jquery.com/jQuery.param/
	*/
	addArgument: function(url,a,b){
		if(typeof(url)=='object'){
			if(url[0]){
				var u=url[0];
				delete(url[0]);
				$.each(url,function(_a,_b){
					u=$.atk4.addArgument(u,_a,_b);
				});
				url=u;
			}
		}
		if(typeof(a)=='undefined')return url;
		if(b)a+='='+encodeURIComponent(b);
        return url+(url.indexOf('?')==-1?'?':'&')+a;
	}

});

})(jQuery);


// we use console.log a lot. It is handy in WebKit and Firebug, but
// would produce error in other browers. If method is not present,
// we define a blank one to avoid errors.
if(!window.console){
	window.console={
		log: function(){
		},
		error: function(){
		}
	}
}
