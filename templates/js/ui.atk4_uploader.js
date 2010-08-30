/* Welcome to Agile Toolkit JS framework. This file implements Uploader. */

// The following HTML structure should be used:
//
// <input type=file>     <!-- binding to this element -->
//

$.widget("ui.atk4_uploader", {
    options: {
		'flash': false,
		'iframe': false,
    },
	shown: true,

    _init: function(){
		var self=this;
		this.name=this.element.attr('id');

		if(this.options.flash){
			this.initSWF();
		}

		if(this.options.iframe){
			this.element.change(function(){
				self.upload();
			})
		}


	},

	// we supply different upload techniques, and can change later based on browser support.
	initSWF: function(){
		var uploader=this;
		uploader.element.hide();	// do not show while loading..
		uploader.element.after('<input type="hidden" id="'+uploader.name+'_token">');

		$.atk4.includeCSS('/amodules3/templates/js/uploadify/uploadify.css');
		$.atk4.includeJS('/amodules3/templates/js/uploadify/swfobject.js');
		$.atk4.includeJS('/amodules3/templates/js/uploadify/jquery.uploadify.v2.1.0.min.js');

		$.atk4(function(){
			uploader.element.uploadify(i={
				'uploader':'/amodules3/templates/js/uploadify/uploadify.swf',
				'script': '/upload/',
				'scriptAccess': 'always',
				'buttonText':'Upload new',
				'auto': true,
				'fileDataName': 'Default',
				'sizeLimit': uploader.options.size_limit,
				'onComplete': function(){ return uploader.completeSWF.apply(uploader,arguments)},
				'cancelImg': '/amodules3/templates/js/uploadify/cancel.png'
			});
			console.log(i);
		});

	},
	upload: function(){
		var f=this.element.closest('form');
		var oa=f.attr('action');


		var i=$('<iframe id="'+this.name+'_iframe" src="about:blank" style="width:0;height:0;border:0px solid black;"></iframe>').
		insertBefore(this.element);


		f
		.attr('action',oa+'&'+this.element.attr('name')+'_upload_action='+this.name)
		.attr('target',this.name+"_iframe")
		.atk4_form('submitPlain',this.name+"_iframe")
		.removeAttr('target')
		.attr('action',oa)
		;

		this.element.clone().attr('id',this.name+'_').insertAfter(this.element).atk4_uploader(this.options);
		this.element.attr('disabled',true);
		$('<br/>').insertAfter(this.element);
	},
	uploadComplete: function(data){
		// This method is called when iFrame upload is complete
		//$('#'+this.name+'_progress').remove();
		//$('#'+this.name+'_iframe').remove();
		this.element.trigger('upload');
		this.element.attr('disabled',false);
		$('<b>Upload of '+this.element.val()+' successful!</b>').insertBefore(this.element);
		//this.element.next('br').remove();
		this.element.remove();
	},
	uploadFailed: function(message){
		this.element.next('br').remove();
		this.element.remove();
		alert(message);
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

