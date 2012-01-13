/* Welcome to Agile Toolkit JS framework. This file implements Uploader. */

// The following HTML structure should be used:
//
// <input type=file>     <!-- binding to this element -->
//

$.widget("ui.atk4_uploader", {
    options: {
		'flash': false,
		'iframe': false,
		'multiple': 1,
    },
	shown: true,

	_setChanged: function(){
		this.element.closest('form').addClass('form_changed');
	},


    _create: function(){
		var self=this;
		if(!this.options.form)this.options.form="#"+closest('form').parent().attr('id');
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
		var form_wrapper=$(this.options.form);
		var form=form_wrapper.find('form');
		var oa=form.attr('action');



		// add dynamically if it's missing
		var i=$('<div style="display: inline"/>');
		i.insertBefore(this.element);
		i[0].innerHTML='<iframe id="'+this.name+'_iframe" name="'+this.name+'_iframe" src="about:blank" style="width:0;height:0;border:0px solid black;"></iframe>';
			//insertBefore(this.element);

		var g=$('<div class="atk-loader" id="'+this.name+'_progress"><i></i>Uploading '+this.element.val()+'</div>').
		insertBefore(this.element);

		form
		.attr('action',oa+'&'+this.element.attr('name')+'_upload_action='+this.name)
		.attr('target',this.name+"_iframe");

		form_wrapper.atk4_form('submitPlain');

		form
		.removeAttr('target')
		.attr('action',oa)
		;

		// fool-proof way to clone element. Firefox will copy seelcted file, while safari will not
		var el=this.element.clone().attr('id',this.name+'_');

		// Silly firefox - copies uploaded file value
		el=el.wrap('<div/>').parent();
		el[0].innerHTML=el[0].innerHTML;
		el=el.find('input');

		el.insertAfter(this.element).atk4_uploader(this.options);

		var files=$("#"+this.element.attr('name')+"_files").find('.files-container').children('div').not('.template').length;
		if(files+1>=this.options.multiple)el.hide(); //does this work actually? I mean the el.hide()
		this.element.hide();
	},
	addFiles: function(data){
		// Uses template to populate rows in the table
		var tb=$("#"+this.element.attr('name')+"_files").find('.files-container');
		var self=this;
		var act=this.element.closest('form').attr('action');

		$.each(data,function(i,row){
			var tpl=tb.find('.template')
				.clone().attr('rel',row['id'])// <--easier to debug
				.removeClass('template')
				.show();
			$.each(row,function(key,val){
				tpl.find('[data-template='+key+']').text(val);
			});
			tpl.find('.delete_doc').click(function(ev){
                var tmp = this;
                $(this).univ().dialogConfirm('Confirmation required', 'Do you want to delete this file?', function(){
                    ev.preventDefault();
                    $(tmp).univ().ajaxec(act+'&'+
                        self.element.attr('name')+'_delete_action='+
                        $(tmp).closest('div').attr('rel') 
                    );
                });
			})
			tpl.find('.add_image').click(function(ev){
				ev.preventDefault();
				var url=act+'&view=true&'+self.element.attr('name')+'_save_action='+ $(this).closest('div').attr('rel');
				$('.atk4_richtext').atk4_richtext('append','<img src="'+url+'"/>');
			})
			tpl.find('.add_image_elrte').click(function(ev){
				ev.preventDefault();
				var url='/img/' + $(this).closest('div').attr('rel');
				$('.elrte_editor').elrte()[0].elrte.selection.insertText('<img src="'+url+'"/>');
			})
			tpl.find('.image_preview').each(function(){
				$(this).attr('src',act+'&view=true&'+
					self.element.attr('name')+'_save_action='+
					$(this).closest('div').attr('rel') 
				);
			})
			tpl.find('.view_doc').click(function(ev){
				ev.preventDefault();
				$(this).univ().newWindow(act+'&view=true&'+
					self.element.attr('name')+'_save_action='+
					$(this).closest('div').attr('rel') 
				);
			})
			tpl.find('.save_doc').click(function(ev){
				ev.preventDefault();
				$(this).univ().location(act+'&'+
					self.element.attr('name')+'_save_action='+
					$(this).closest('div').attr('rel') 
				);
			});
			tpl.appendTo(tb);
		});
		self.updateToken();
		var files=$("#"+this.element.attr('name')+"_files").find('.files-container').children('div').not('.template').length;
		if(files>=this.options.multiple)this.element.hide();

	},
	removeFiles: function(ids){
		var tb=$("#"+this.element.attr('name')+"_files").find('.files-container');
		var self=this;
		$.each(ids,function(junk,id){
			tb.find('[rel='+id+']').remove();
		});
		self.updateToken();
		this.element.show();
	},
	updateToken: function(){
		var tb=$("#"+this.element.attr('name')+"_files").find('.files-container');
		var ids=[];
		tb.find('div').not('.template').each(function(){
			ids.push($(this).attr('rel'));
		});
		$("#"+this.element.attr('name')+"_token").val(ids.join(','));
	},
	uploadComplete: function(data){
		// This method is called when iFrame upload is complete
		if(!data){
			console.error('File upload was completed but no action was defined.'); 
			return;
		}
		$('#'+this.name+'_progress').remove();
		this.element.trigger('upload');
		this.element.attr('disabled',false);
		this.addFiles([data]);
		//this.element.next('br').remove();
		this.element.remove();
		this._setChanged();
		//$('#'+this.name+'_token').val(data.id);
	},
	uploadFailed: function(message,debug){
		if(debug){
			$.univ().successMessage('Debug: '+$.univ().toJSON(debug));
		}
		$(this.options.form).atk4_form('fieldError',this.element,message);
		$('#'+this.name+'_progress').remove();
		this.element.next().show()
		this.element.remove();
	},
	completeSWF: function(a,b,c,d,e){
		var token={
			'fileInfo':c,
			'filename':d
		}
		try{
			$('#'+this.name+'_token').val($.univ().toJSON(token));
			this.element.after('File: '+token.fileInfo.name+' uploaded successfuly <br/>');
			this._setChanged();
		}catch(e){
			console.log(e);
		}
		console.log('hoho');
		return true;
	}

});

