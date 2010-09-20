$.ui.tabs.prototype.load=function(index) {
		var self = this, o = this.options, a = this.anchors.eq(index)[0], url = $.data(a, 'load.tabs');

		this.abort();

		// not remote or from cache
		if (!url || this.element.queue("tabs").length !== 0 && $.data(a, 'cache.tabs')) {
			this.element.dequeue("tabs");
			return;
		}

		// load remote from here on
		//this.lis.eq(index).addClass('ui-state-processing');
		// this breaks in cases when loading fails

		if (o.spinner) {
			var span = $('span', a);
			span.data('label.tabs', span.html()).html(o.spinner);
		}

		$(self._sanitizeSelector(a.hash)).atk4_load(url,function(){
				self._cleanup();

				if (o.cache) {
					$.data(a, 'cache.tabs', true); // if loaded once do not load them again
				}

				window.location.hash=self.anchors[index].hash;

				// callbacks
				self._trigger('load', null, self._ui(self.anchors[index], self.panels[index]));
				try {
					o.ajaxOptions.success(r, s);
				}
				catch (e) {}

		});

		// last, so that load event is fired before show...
		self.element.dequeue("tabs");

		return this;
	}
