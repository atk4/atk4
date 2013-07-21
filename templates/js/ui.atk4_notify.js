/**
 * ATK Notification UI Widget
 */

(function($){

$.widget('ui.atk4_notify', {

    /**
     * Configuration
     */
    options: {
        
        // Set function here how to show/hide a message
        show: function(){ this.fadeIn() },
        hide: function(){ this.fadeOut() },
        
        // Timeout in miliseconds
        min_timeout: 3000,
        inc_timeout: 25,
        
        // Close button
        closable: true,
        close_text: 'Hide this message'
    },

    /**
     * Display success message
     * 
     * @param string text Message text to show
     */
    successMessage: function(text) {
        this.message(text, true);
    },

    /**
     * Display error message
     * 
     * @param string text Message text to show
     */
    errorMessage: function(text) {
        this.message(text, false);
    },

    /**
     * Display message
     * 
     * @param string text Message text to show
     * @param boolean success Show success message if true, error message otherwise
     */
    message: function(text, success) {
        var html = $(
            '<div class="atk-notification ui-state-'+(success?'highlight':'error')+' ui-corner-all">'+
                '<div class="atk-notification-text">'+
                    '<i class="ui-icon ui-icon-'+(success?'info':'alert')+'"></i>'+
                    '<span>'+text+'</span>'+
                '</div>'+
                (this.options.closable
                    ? '<i title="'+this.options.close_text+'" class="ui-icon ui-icon-closethick"></i>'
                    : ''
                ) +
            '</div>');
        
        this.messageHTML(html);
    },

    /**
     * Prepare message object and show it
     * 
     * @param jQuery message Message to show
     */
    messageHTML: function(message) {
        this._defineBehaviour(message);
        this._customiseMessage(message);
        this._insertMessage(message);
    },

    /**
     * Define behaviour of message object
     * 
     * @param jQuery message Message object
     */
    _defineBehaviour: function(message) {
        var self = this;
        
        // When close button is present - use it
        if (this.options.closable) {
            message.find('.ui-icon-closethick').click(function() {
                message.stop();
                self.options.hide.call(message);
            });
        } else {
            // otherwise, the whole message disappears on click
            message.click(function() {
                message.stop();
                self.options.hide.call(message);
            });
        }
    },

    /**
     * Redefine this to add some custom markup or actions for your messages
     * 
     * @param jQuery message Message object
     */
    _customiseMessage: function(message) {
        // message.addClass('light-gray');
    },

    /**
     * Add message object into container
     * 
     * @param jQuery message Message object
     */
    _insertMessage: function(message) {
        message.hide();
        this.element.prepend(message);
        
        this.options.show.call(message);
        message.delay(this._getTimeout(message));
        this.options.hide.call(message);
        
        message.hide(0, function() {
            message.remove();
        });
    },

    /**
     * Return time in ms for how long we should show message.
     * 
     * @param jQuery message Message object
     */
    _getTimeout: function(message) {
        return this.options.min_timeout +
               message.text().length * this.options.inc_timeout;
    }
});

})($);
