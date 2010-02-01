/* Welcome to Agile Toolkit JS framework. This is a main file which provides extensions on top of jQuery-UI */

// do not allow user to navigate away: http://msdn.microsoft.com/en-us/magazine/dd898316.aspx
// DHTML style guide: http://dev.aol.com/dhtml_style_guide

;jQuery.atk4||(function($){
    
$.atk4 = function(readycheck,lastcall){
    return $.atk4.ready(readycheck,lastcall);
};

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



$.extend($.atk4,{
    verison: "1.1",


    currentPage: "",
    base: "",

    initialised: false,

    // Initialisation of $.atk subsystem. Call this with options.
    init: function(options){
        if(this.initialised)return;
        this.initialised=true;
		if(options && options['base-url'])this.base=options['base-url'];
        region=$(document);
    },

    get: function(url, data, callback){
        if($.isFunction(data)){
            callback=data; data=null;
        }
        return $.ajax({
            type: "GET",
            url: url,
            data: data,
            success: function(res){ $.atk4.checkSession(res) && callback(res); },
            error: function(a,b,c){ $.atk4.ajaxError(url,a,b,c); }
        });
    },
	addArgument: function(url,a){
        return url+(url.indexOf('?')==-1?'?':'&')+a;
	},
    ajaxError: function(url,a,b,c){
        console.error("Failed to load file: ",url," (",a,b,c,")");
    },
    checkSession: function(text){
        if($.trim(text)=="SESSION TIMEOUT"){
            alert('session has timed out');
            // insert redirect here
            return false;
        }
        return true;
    },

    // $.atk4.load - replaces default jQuery loading method
    load: function(el, url, callback, reload){
        region=el;

		var selector, off = url.indexOf(" ");
		if ( off >= 0 ) {
			selector = url.slice(off, url.length);
			url = url.slice(0, off);
		}

		$.atk4.loading=setTimeout(function(){ 
			if($.atk4.loading)$('#loading_screen1,#loading_screen2').fadeIn('fast');
		},2000);
        this.get(url,function(res){
			if($.atk4.loading){
				clearTimeout($.atk4.loading);
				$('#loading_screen1,#loading_screen2').fadeOut('fast');
				$.atk4.loading=null;
			}
			if(res.substr(0,13)=='SESSION OVER:'){
				$.univ.dialogOK('Session over','Your session have been timed out',function(){ document.location='/'});
				return;
			}
            var scripts=[], source=res;


            while((s=source.indexOf("<script"))>=0){
                s2=source.indexOf(">",s);
                e=source.indexOf("</script",s2);
                e2=source.indexOf(">",e);


                scripts.push(source.substring(s2+1,e));
                source=source.substring(0,s)+source.substring(e2+1);
            }

			if(reload){
				el.replaceWith(selector?$('<div/>').append(source).find(selector):source);
			}else{
				el.html(selector?$('<div/>').append(source).find(selector):source);
			}

            for(var i in scripts){
				try{
					eval(scripts[i]);
				}catch(e){
					console.error("JS:",e,scripts[i]);
				}
            };

			if(callback)$.atk4(callback,true);
       });
    },
    click: function(el,fn){
	   	el.click(function(ev){
			ev.preventDefault();
			fn.call(this,this.href);
        });
    },


    inProgress: 0,
    readyList: [],
	readyLast: undefined,
    includes: {},
    includeJS: function(url){
        if(this.base)url=this.base+url;
        // Create tag
        if(this.isIncluded(url))return;

        this.inProgress++;
        this.get(url,function(code){
            //$.globalEval(code);
            try{
                eval(code);
            }catch(e){
                if(String(e).indexOf("Parse error")){
                    //console.error("Eval failed for "+url+", trying to include");
                    $.atk4.evalJS(url)
                    // kill readycheck handlers by not reducing inProgress counter
                    return;

                }else log.error("Eval error: "+e);
            }

            if(!--$.atk4.inProgress)$.atk4.readyExec();
        });

    },
    evalJS: function(url,clean) {
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
    
    ready: function(fn,lastcall){
		if(lastcall){
			if(!this.inProgress){
				fn.call(document);
			}else{
				this.readyLast=fn;
			}
			return;
		}
        if(!this.inProgress){
            fn.call(document);
        }else{
            this.readyList.push(fn);
        }
    },
    readyExec: function(){
        while(this.readyList.length){
            fn=this.readyList.shift();
            fn.call(document);

			// We are loading more data, resume after
			if($.atk4.inProgress)return;
        }
		if(this.readyLast){
			this.readyLast.call(document);
			this.readyLast=undefined;
		}
    },
    includeCSS: function(url){
        if(this.base)url=this.base+url;
        // Create tag
        if(this.isIncluded(url))return;

        $.get(url,function(code){
            var cssTag = document.createElement('style');
            cssTag.setAttribute('type', 'text/css');
			if(cssTag.styleSheet){ // IE quirk
				cssTag.styleSheet.cssText=code;
			}else{	 // compliant browsers
            	var t = document.createTextNode(code);
				cssTag.appendChild(t);
			}
            var headerTag = document.getElementsByTagName('head')[0];
            headerTag.appendChild(cssTag);
        });
    },
    isIncluded: function(url){
        if(this.includes[url])return true;
        this.includes[url]=true;
        return false;
    },
    production: false,
    exceptionHandler: function(o,e){
        var c=[];
        for(i in e){
            c.push(i+": ".e[i]);
        }
        log.error('exception by ',o,c.join("\n"));
    },




    // Problem during JS evaluation. Pop up a message
    evalProblem: function(e,response_text){
        //while some browsers prevents popup we better use alert
        w=window.open(null,null,'height=400,width=700,location=no,menubar=no,scrollbars=yes,status=no,titlebar=no,toolbar=no');
        if(w){
            w.document.write('<h2>Error in AJAX response: '+e+'</h2>');
            w.document.write(response_text);
            w.document.write('<center><input type=button onclick="window.close()" value="Close"></center>');
        }else{
            showMessage("Error in AJAX response: "+e+"\n"+response_text);
        }
    }
});

$.fn.extend({
    atk4_load: function(url,fn){
        $.atk4.load(this,url,fn);
    },
    atk4_reload: function(url,arg,fn){
		if(arg){
			$.each(arg,function(key,value){
				url=$.atk4.addArgument(url,key+'='+value);
			});
		}
        $.atk4.load(this,url,fn,true);
    },
    atk4_click: function(url){
        $.atk4.click(this,url);
    }
});


})(jQuery);


if(!window.console){
	window.console={
		log: function(){
			// console / firebug is not present. Ignore all debug messages
		}
	}
}


// 1. Core: Initialise checks and add our core functions
//
// 1-atk4_load: Load page for matched region. You may use 
