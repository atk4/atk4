/* Welcome to Agile Toolkit JS framework. This file implements fat checkbox. */

/* (c) Agile Technologies Limtied */
//
// The following HTML structure should be used:
//
// <input type="checkbox">     <!-- binding to this element -->
//

$.widget("ui.fat_checkbox", {
	_create: function(){
		var self=this;

		self.img=$('<div class="fat-checkbox"/>');
		//self.img=$('<img height="20" width="20" class="fat-checkbox" src="blah"/>');

		self.img.insertAfter(self.element.hide());

		self.element.change(function(){
			self.updateClass();
		});

		// bind classes
		self.img.mousedown(function(ev){
			ev.preventDefault();
			self.updateClass(true);
			self.active=true;
		}).mouseup(function(ev){
			ev.preventDefault();
			if(self.active)self.element[0].checked=!self.element[0].checked;
			self.active=false;
			self.updateClass();
		}).mouseout(function(ev){
			self.active=false;
			self.updateClass();
		})
		;

		self.updateClass();
	},
	updateClass: function(active){
		var self=this;
		self.img.removeClass('checkbox-c');
		self.img.removeClass('checkbox-ca');
		self.img.removeClass('checkbox-a');
		self.img.removeClass('checkbox-n');

		if(this.element[0].checked){
			self.img.addClass(active?'checkbox-ca':'checkbox-c');
		}else{
			self.img.addClass(active?'checkbox-a':'checkbox-n');
		}

		console.log(this.img.attr('class'));
	}


});

