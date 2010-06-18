/*
 * ATK Notification UI Widget
 *
*/

(function($){

$.widget('ui.atk4_notify', {

	// Few different ways to display a message
	options: {
		show: function(){ return this.fadeIn() },
		hide: function(){ return this.fadeOut() }
	},
	
	_defineBehaviour: function(message){
		// When click button is present - use it
		var self=this;
		if(message.find('.close').length){
			message.find('.close').hide().click(function(){
				message.fadeOut(500,function(){
					message.remove();
				});
			});
		}else{
			// otherwise, the whole message disapears on click
			message.click(function(){
				message.fadeOut(500,function(){
					message.remove();
				});
			});
		}

		message.mouseenter(function(){
			console.log('entered');
			message.stop(true);
			message.css({'data-delay':1});
			message.find('.close').show();
		});

		message.mouseleave(function(){
			message.find('.close').hide();
			message.delay(self._getTimeout(message));
			self.options.hide.call(message);
			message.hide(0,function(){
				message.remove();
			});
		});

	},
	_insertMessage: function(message){
		/* 
		 * Add message into container
		 */
		message.hide();
		this.element.prepend(message);
		this.options.show.call(message);
		message.delay(this._getTimeout(message));
		this.options.hide.call(message);
		message.hide(0,function(){
			message.remove();
		});
	},

	_getTimeout: function(message){
		var r=3000+message.text().length*25;
		return r;
	},
	_customiseMessage: function(message){
		/*
		 * redefine this to add some custom markup for your messages
		 */
		message.addClass('light-gray');
	},
	messageHTML: function(message){
		this._customiseMessage(message);
		this._defineBehaviour(message);
		this._insertMessage(message);
	},
	message: function(text,icon){
		/*
		 * This display a message which you would commonly use on successful operation completion.
		 */
		var html=$('<div class="growl"><i class="icon"></i><span>Sample Text</span><b></b></div>');
		if(!icon)icon='success';
		html.find('i').addClass('icon-'+icon);
		html.find('span').text(text);
		html.find('b').addClass('close');

		this.messageHTML(html);
	},
	successMessage: function(text){
		this.message(text,'success');
	}
	
});

})($);
