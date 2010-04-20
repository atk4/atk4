/* Welcome to Agile Toolkit JS framework. This file implements Uploader. */

// The following HTML structure should be used:
//
// <input type=file>     <!-- binding to this element -->
//

$.widget("ui.atk4_uploader", {
    options: {
    },
	shown: true,
	autocomplete: null,
    _init: function(options){
	console.log(options);
        $.extend(this.options,this.default_options,options);
		this.name=this.element.attr('id');

		this.initSWF();


	},

	// we supply different upload techniques, and can change later based on browser support.
	initSWF: function(){
		var uploader=this;
		uploader.element.hide();	// do not show while loading..
		uploader.element.after('<input type="hidden" id="'+uploader.name+'_token">');

		$.atk4.includeCSS('templates/js/uploadify/uploadify.css');
		$.atk4.includeJS('templates/js/uploadify/swfobject.js');
		$.atk4.includeJS('templates/js/uploadify/jquery.uploadify.v2.1.0.min.js');

		$.atk4(function(){
			uploader.element.uploadify(i={
				'uploader':'templates/js/uploadify/uploadify.swf',
				'script': '/upload/',
				'scriptAccess': 'always',
				'buttonText':'Upload new',
				'auto': true,
				'fileDataName': 'Default',
				'sizeLimit': uploader.options.size_limit,
				'onComplete': function(){ return uploader.completeSWF.apply(uploader,arguments)},
				'cancelImg': 'templates/js/uploadify/cancel.png'
			});
			console.log(i);
		});

	},
	completeSWF: function(a,b,c,d,e){
		console.log('yo');
		var token={
			'fileInfo':c,
			'filename':d
		}
		try{
			$('#'+this.name+'_token').val($.univ().toJSON(token));
			this.element.after('File: '+token.fileInfo.name+' uploaded successfuly <br/>');
		}catch(e){
			console.log(e);
		}
		console.log('hoho');
		return true;
	}

});

