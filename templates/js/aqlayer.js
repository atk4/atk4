/* aqLayer v1.0 - Creates a styled layer that attaches to the current element.
Copyright (C) 2008 Paul Pham <http://aquaron.com/~jquery/aqLayer>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
(function($){
$.fn.aqLayer = function(options) {
	var opts = $.extend({ 
		attach: 'nw', offsetX: 0, offsetY: 0, 
		show: false, opacity: .9,
		closeBtn: false, closeImg: 'http://tinyurl.com/6ba6nl',
		noClose: false, 
		clone: false,
		layerCss: { 
			zIndex: 2, display: 'none', width: '400px', position: 'absolute',
			background: 'gray' 
		},
		topCss: { 
			height: '5px', 
		},
		botCss: { 
			height: '5px', 
		},
		midCss: { 
			color: '#222', 
			padding: '2px', margin: '2px', 
			float: 'none', clear: 'both', textAlign: 'left',
		}
	}, options);

	return this.each(function() {
		if ($('div',this).hasClass('aqLayer')) {
			if (typeof options == 'string') {
			if (options == 'hide') {
				$(this).find('.aqLayer').fadeOut();
				return false;
			} else if (options != 'show')
				$(this).find('.aqLayer .mid').html(options);

			$(this).find('.aqLayer').show();
			return false;
			}

			$(this).find('.aqLayer').show();
			return false;
		}

		obj = $(this);
		var html = null;

		if (opts.clone) {
			html = obj.html();
			obj.empty().show();
		}

		$('<div class="aqLayer"><div class="top"><\/div><div class="mid"><\/div><div class="bot"><\/div><\/div>')
			.appendTo(obj);

		var $layer = $('.aqLayer', obj);
		$layer.find('.top').css(opts.topCss);
		$layer.find('.bot').css(opts.botCss);
		$layer.find('.mid').css(opts.midCss);
		
		var pos = obj.position();
		if (opts.position)
			pos = opts.position;

		var y = (opts.attach.match(/n/) ? 0 
			: (opts.attach.match(/s/) ? obj.height()
			: Math.round(obj.height()/2)));

		var x = (opts.attach.match(/w/) ? 0 
			: (opts.attach.match(/e/) ? obj.width()
			: Math.round(obj.width()/2)));

		if (obj.css('position') != 'absolute') {
			x += pos.left;
			y += pos.top;
		}

		if (typeof options == 'string') $layer.find('.mid').html(options);
		if (html) $layer.find('.mid').html(html);

		$layer.css({
			opacity: opts.opacity,
			position: 'absolute',
			left: x + opts.offsetX + 'px',
			top:  y + opts.offsetY + 'px'
		}).css(opts.layerCss);

		if (opts.closeBtn || opts.noClose) {
			if (opts.closeBtn) {
			$('.top',$layer).append('<div style="position:absolute;top:1px;right:4px"><img src="'+opts.closeImg+'"><\/div>');
			$('.top img',$layer).click(function(){$layer.fadeOut()});
			}

			if(typeof jQuery.fn.draggable != 'undefined')
			$layer.draggable();
		} else
			$layer.click(function(){$(this).fadeOut()});

		if (opts.show || typeof options == 'string') $layer.show();
	});
};
})(jQuery);