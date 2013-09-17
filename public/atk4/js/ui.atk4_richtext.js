/*
 * Implementation of really simple WYSIWYG editor, which mostly relies on Agile Toolkit for additional features
 * and provides only API, no visual features
 *
  $('textarea').richtext(); 
 */

$.widget("ui.atk4_richtext", {
   	options: {
   	},
	_create: function(){
      	this.elementHTML = $('<div/>').addClass('ui-widget ui-widget-content');
		this.element.wrap($('<div/>').addClass('atk4_richtext_html')).after(this.elementHTML).addClass('atk4_richtext').hide();
		this.elementHTML.attr('contenteditable',true).html(this._getSource());
       // this.elementHTML.css('height', '400px').css('overflow', 'scroll');

		var self=this;
		this.element.change(function(){

			self.elementHTML.html(self.element.val());
		});

		this.elementHTML.bind('paste',function(){ self._paste(); });
	},
	_getSource: function(){
		var s=this.element.val();
		if(!s)s='<p>Type text here...</p>';
		return s;
	},
	append: function(text){
		this.elementHTML.append(text);
	},
	_paste: function(){
		console.log(this.elementHTML.html());
		this.changeHTML();
	},
	toggleSource: function(){
		this.elementHTML.toggle();
		this.element.toggle();
	},
	clean: function(){
		this.elementHTML.find('span').children().unwrap();
	},
    destroy: function() {
       $.Widget.prototype.destroy.apply(this, arguments); // default destroy
       this.changeHTML();
       this.elementHTML.detach();
       this.element.unwrap().removeClass('atk4_richtext').show();
	   // todo - unbind change event
    },
	changeHTML: function(){
		var s=this.elementHTML.html();
		if(!s)s='<p>Type text here...</p>';
		this.element.val(s);
	},
	command: function(cmd,args){
		this.elementHTML.focus();
		window.document.execCommand(cmd,false,args);
	}
});
