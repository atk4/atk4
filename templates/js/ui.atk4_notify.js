/*
 * ATK Notification UI Widget
 *
*/

(function($){

$.widget('ui.atk4_notify', {

	// Few different ways to display a message
	//
	
	_defineBehaviour: function(message){
		// When click button is present - use it
		if(message.find('.close').length){
			message.find('.close').click(function(){
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
			message.stop(true);
			message.css({opacity:1});
			message.find('.close').show();
		});

		message.mouseleave(function(){
			message.animate({opacity:1},this.getTimeout(message)).fadeOut(500,function(){
				message.remove();
			});
			message.find('.close').hide();
		});

	},
	_insertMessage: function(message){
		/* 
		 * Add message into container
		 */
		this.element.prepend(message);
		message.slideDown(200).animate({opacity:1},this._getTimeout(message)).fadeOut(500,function(){
			message.remove();
		});
	},

	_getTimeout: function(message){
		// TODO: based on length of the message return different values
		return 4000;
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
