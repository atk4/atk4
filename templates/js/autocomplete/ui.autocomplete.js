/*
 * jQuery UI Autocomplete @VERSION
 *
 * Copyright (c) 2007, 2008 Dylan Verheul, Dan G. Switzer, Anjesh Tuladhar, Jörn Zaefferer
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI/Autocomplete
 *
 * Depends:
 *	ui.core.js
 */
(function($) {

$.widget("ui.autocomplete", {

	_init: function() {
		// TODO move these to instance properties; overwrite setData to update
		$.extend(this.options, {
			delay: this.options.delay != undefined ? this.options.delay : (this.options.url? this.options.ajaxDelay : this.options.localDelay),
			max: this.options.max != undefined ? this.options.max : (this.options.scroll? this.options.scrollMax : this.options.noScrollMax),
			highlight: this.options.highlight || function(value) { return value; }, // if highlight is set to false, replace it with a do-nothing function
			formatMatch: this.options.formatMatch || this.options.formatItem // if the formatMatch option is not specified, then use formatItem for backwards compatibility
		});

		// TODO clean up; try to remove these
		var input = this.element[0],
			options = this.options,
			// Create $ object for input element
			$input = $(input).attr("autocomplete", "off").addClass(options.inputClass),
			KEY = $.ui.keyCode,
			previousValue = "",
			cache = $.ui.autocomplete.cache(options),
			hasFocus = 1,
			config = {
				mouseDownOnSelect: false
			},
			timeout,
			blockSubmit,
			lastKeyPressCode,
			// TODO refactor select into its own widget? if not, try to merge with widget methods
			select = $.ui.autocomplete.select(options, input, selectCurrent, config);

		// TODO is there a more generic way for callbacks?
		if (options.result) {
			$input.bind('result.autocomplete', options.result);
		}

		// TODO verify
		// prevent form submit in opera when selecting with return key
		$.browser.opera && $(input.form).bind("submit.autocomplete", function() {
			if (blockSubmit) {
				blockSubmit = false;
				return false;
			}
		});

		// TODO verify
		// only opera doesn't trigger keydown multiple times while pressed, others don't work with keypress at all
		$input.bind(($.browser.opera ? "keypress" : "keydown") + ".autocomplete", function(event) {
			// a keypress means the input has focus
			// avoids issue where input had focus before the autocomplete was applied
			hasFocus = 1;
			// track last key pressed
			lastKeyPressCode = event.keyCode;
			switch(event.keyCode) {

				case KEY.UP:
					event.preventDefault();
					if ( select.visible() ) {
						select.prev();
					} else {
						onChange(0, true);
					}
					break;

				case KEY.DOWN:
					event.preventDefault();
					if ( select.visible() ) {
						select.next();
					} else {
						onChange(0, true);
					}
					break;

				case KEY.PAGE_UP:
					event.preventDefault();
					if ( select.visible() ) {
						select.pageUp();
					} else {
						onChange(0, true);
					}
					break;

				case KEY.PAGE_DOWN:
					event.preventDefault();
					if ( select.visible() ) {
						select.pageDown();
					} else {
						onChange(0, true);
					}
					break;

				// TODO remove multiple case
				// matches also semicolon
				case options.multiple && $.trim(options.multipleSeparator) == "," && KEY.COMMA:
				// TODO implement behaviour based on focus-option
				case KEY.ENTER:
				case KEY.TAB:
						// stop default to prevent a form submit, Opera needs special handling
					if( selectCurrent() ) {
						event.preventDefault();
						blockSubmit = true;
						return false;
					}
					break;

				case KEY.ESCAPE:
					select.hide();
					break;

				default:
					clearTimeout(timeout);
					timeout = setTimeout(onChange, options.delay);
					break;
			}
		})
		.bind('focus.autocomplete', function(){
			// track whether the field has focus, we shouldn't process any
			// results if the field no longer has focus
			hasFocus++;
		})
		.bind('blur.autocomplete', function() {
			hasFocus = 1;
			if (!config.mouseDownOnSelect) {
				hideResults();
			}
		})
		.bind('click.autocomplete', function() {
			// show select when clicking in a focused field
			if ( hasFocus++ > 1 && !select.visible() ) {
				console.log('select clicked');
				$input.select();

				//onChange(0, true);

				request('',receiveData,hideResultsNow);
			}
		}).bind("search.autocomplete", function() {
			// TODO why not just specifying both arguments?
			var fn = (arguments.length > 1) ? arguments[1] : null;
			function findValueCallback(q, data) {
				var result;
				if( data && data.length ) {
					for (var i=0; i < data.length; i++) {
						if( data[i].result.toLowerCase() == q.toLowerCase() ) {
							result = data[i];
							break;
						}
					}
				}
				if( typeof fn == "function" ) fn(result);
				else $input.trigger("result.autocomplete", result && [result.data, result.value]);
			}
			// TODO remove trimWords/multiple handling
			$.each(trimWords($input.val()), function(i, value) {
				request(value, findValueCallback, findValueCallback);
			});
		})
		// TODO replace with public instance method
		.bind("flushCache.autocomplete", function() {
			cache.flush();
		})
		// TODO replace with setData override
		.bind("setOptions.autocomplete", function() {
			$.extend(options, arguments[1]);
			// if we've updated the data, repopulate
			if ( "data" in arguments[1] )
				cache.populate();
		})
		// TODO replace with destroy override, if necessary at all
		.bind("unautocomplete", function() {
			// TODO rename select.unbind() to .remove(); thats what it does anyway
			select.unbind();
			$(input).unbind(".autocomplete");
			$(input.form).unbind(".autocomplete");
		});

		// Private methods
		// TODO move to instance method
		function selectCurrent() {
			var selected = select.selected();
			if( selected == 0 ) return false;

			var v = selected.result;
			previousValue = v;

			// TODO remove
			if ( options.multiple ) {
				var words = trimWords($input.val());
				if ( words.length > 1 ) {
					v = words.slice(0, words.length - 1).join( options.multipleSeparator ) + options.multipleSeparator + v;
				}
				v += options.multipleSeparator;
			}

			$input.val(v);
			hideResultsNow();
			$input.trigger("result.autocomplete", [selected.data, selected.value]);
			return true;
		};

		// TODO verify usefullness of skipPrevCheck; also try to get rid of obviously useless first argument
		// where onChange is used as event handler directly, wrap it instead and provide the right arguments
		function onChange(crap, skipPrevCheck) {
			if( lastKeyPressCode == KEY.DELETE ) {
				select.hide();
				return;
			}

			var currentValue = $input.val();

			if ( !skipPrevCheck && currentValue == previousValue )
				return;

			previousValue = currentValue;

			// TODO refactor; the request-response workflow is currently scattered over way too many places
			currentValue = lastWord(currentValue);
			if ( currentValue.length >= options.minChars) {
				$input.addClass(options.loadingClass);
				if (!options.matchCase)
					currentValue = currentValue.toLowerCase();
				request(currentValue, receiveData, hideResultsNow);
			} else {
				stopLoading();
				select.hide();
			}
		};

		// TODO without the multiple stuff, this shouldn't be necessary anymore
		function trimWords(value) {
			if (!value)
				return [""];
			if (!options.multiple)
				return [$.trim(value)];
			return $.map(value.split(options.multipleSeparator), function(word) {
				return $.trim(value).length ? $.trim(word) : null;
			});
		};

		// TODO should be abl to remove this, too
		function lastWord(value) {
			var words = trimWords(value);
			return words[words.length - 1];
		};

		// TODO simplify, get rid of comments, remove multiple stuff
		// fills in the input box w/the first match (assumed to be the best match)
		// q: the term entered
		// sValue: the first matching result
		function autoFill(q, sValue){
			// autofill in the complete box w/the first match as long as the user hasn't entered in more data
			// if the last user key pressed was backspace, don't autofill
			if( options.autoFill && (lastWord($input.val()).toLowerCase() == q.toLowerCase()) && lastKeyPressCode != $.ui.keyCode.BACKSPACE ) {
				// fill in the value (keep the case the user has typed)
				$input.val($input.val() + sValue.substring(lastWord(previousValue).length));
				// select the portion of the value not typed by the user (so the next character will erase)
				$.ui.autocomplete.selection(input, previousValue.length, previousValue.length + sValue.length);
			}
		};

		// TODO refactor: move to instance method, verify usefulness
		function hideResults() {
			clearTimeout(timeout);
			timeout = setTimeout(hideResultsNow, 200);
		};

		function hideResultsNow() {
			var wasVisible = select.visible();
			select.hide();
			clearTimeout(timeout);
			stopLoading();
			// TODO reimplement mustMatch
			if (options.mustMatch) {
				// call search and run callback
				$input.autocomplete("search", function (result){
						// if no value found, clear the input box
						if( !result ) {
							if (options.multiple) {
								var words = trimWords($input.val()).slice(0, -1);
								$input.val( words.join(options.multipleSeparator) + (words.length ? options.multipleSeparator : "") );
							}
							else {
								$input.val( "" );
								$input.trigger("result", null);
							}
						}
					}
				);
			}
			// TODO implement focus-option
			//if (wasVisible)
				// position cursor at end of input field
				//$.ui.autocomplete.selection(input, input.value.length, input.value.length);
		};

		// TODO refactor, move to source-related method(s)
		function receiveData(q, data) {
			if ( data && data.length && hasFocus ) {
				stopLoading();
				select.display(data, q);
				autoFill(q, data[0].value);
				select.show();
			} else {
				hideResultsNow();
			}
		};

		// TODO refactor, move to source-related method(s)
		function request(term, success, failure) {
			if (!options.matchCase)
				term = term.toLowerCase();
			var data = cache.load(term);
			// recieve the cached data
			if (data && data.length) {
				success(term, data);
			} // if an AJAX url has been supplied, try loading the data now
			else if( (typeof options.url == "string") && (options.url.length > 0) ){

				var extraParams = {
					timestamp: +new Date()
				};
				$.each(options.extraParams, function(key, param) {
					extraParams[key] = typeof param == "function" ? param(term) : param;
				});

				// TODO move to default implemention for remote source
				$.ajax({
					// try to leverage ajaxQueue plugin to abort previous requests
					mode: "abort",
					port: "autocomplete" + input.name,
					dataType: options.dataType,
					url: options.url,
					data: $.extend({
						q: lastWord(term),
						limit: options.max
					}, extraParams),
					success: function(data) {
						var parsed = options.parse && options.parse(data) || parse(data);
						cache.add(term, parsed);
						success(term, parsed);
					}
				});
			}

			// TODO refactor, in this case, most likely remove, to be replaced with custom source option
			else if (options.source && typeof options.source == 'function') {
				var resultData = options.source(term);
				var parsed = (options.parse) ? options.parse(resultData) : resultData;

				cache.add(term, parsed);
				success(term, parsed);
			// TODO verify
			} else {
				// if we have a failure, we need to empty the list -- this prevents the the [TAB] key from selecting the last successful match
				select.emptyList();
				failure(term);
			}
		};

		// TODO move to parse option; replace the default implementation and move this one to demos
		function parse(data) {
			var parsed = [];
			var rows = data.split("\n");
			for (var i=0; i < rows.length; i++) {
				var row = $.trim(rows[i]);
				if (row) {
					row = row.split("|");
					parsed[parsed.length] = {
						data: row,
						value: row[0],
						result: options.formatResult && options.formatResult(row, row[0]) || row[0]
					};
				}
			}
			return parsed;
		};

		function stopLoading() {
			$input.removeClass(options.loadingClass);
		};

	}, //End _init

	// TODO update to latest ui.core, most likely using _trigger instead
	_propagate: function(n, event) {
		$.ui.plugin.call(this, n, [event, this.ui()]);
		return this.element.triggerHandler(n == 'autocomplete' ? n : 'autocomplete'+n, [event, this.ui()], this.options[n]);
	},

	// TODO remove/replace
	ui: function(event) {
		return {
			options: this.options,
			element: this.element
		};
	},
	// TODO remove/replace: this should be an option
	result: function(handler) {
		return this.element.bind("result.autocomplete", handler);
	},
	// TODO verify API
	search: function(handler) {
		return this.element.trigger("search.autocomplete", [handler]);
	},
	// TODO refactor to just call the method instead of triggering an event
	flushCache: function() {
		return this.element.trigger("flushCache.autocomplete");
	},
	// TODO remove delegation to setOptions, implement it here directly instead
	setData: function(key, value){
		return this.element.trigger("setOptions.autocomplete", [{ key: value }]);
	},
	// TODO verify correctness, replace trigger("unautocomplete)
	destroy: function() {
		this.element
			.removeAttr('disabled')
			.removeClass('ui-autocomplete-input');
		return this.element.trigger("unautocomplete");
	},
	enable: function() {
		this.element
			.removeAttr('disabled')
			.removeClass('ui-autocomplete-disabled');
		this.disabled = false;
	},
	// TODO verify that disabled flag is respected
	disable: function() {
		this.element
			.attr('disabled', true)
			.addClass('ui-autocomplete-disabled');
		this.disabled = true;
	}
});

$.extend($.ui.autocomplete, {
	defaults: {
		// TODO remove these options; css classes are hardcoded
		inputClass: "ui-autocomplete-input",
		resultsClass: "ui-widget ui-widget-content ui-autocomplete-results",
		loadingClass: "ui-autocomplete-loading",
		minChars: 1,
		ajaxDelay: 400,
		localDelay: 10,
		// TODO replace these three with cacheMatch option
		matchCase: false,
		matchSubset: true,
		matchContains: false,
		// TODO verify usefulness
		cacheLength: 10,
		scrollMax: 500,
		noScrollMax: 10,
		mustMatch: false,
		// TODO replace
		extraParams: {},
		// TODO remove
		selectFirst: true,
		// TODO remove, replaced by parse/source
		formatItem: function(row) { return row[0]; },
		// TODO remove, replaced by parse/source
		formatMatch: null,
		// TODO replace with focus-option: "fill"
		autoFill: false,
		width: 0,
		// TODO remove
		multiple: false,
		// TODO remove
		multipleSeparator: ", ",
		// TODO verify usefulness of arguments
		highlight: function(value, term) {
			return value.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
		},
		scroll: true,
		scrollHeight: 180
	}
});

// TODO replace cache related options and matching with cacheMatch option (false = no caching, otherwise implementation to match terms)
$.ui.autocomplete.cache = function(options) {

	var data = {};
	var length = 0;

	function matchSubset(s, sub) {
		if (!options.matchCase)
			s = s.toLowerCase();
		var i = s.indexOf(sub);
		if (i == -1) return false;
		return i == 0 || options.matchContains;
	};

	function add(q, value) {
		if (length > options.cacheLength){
			flush();
		}
		if (!data[q]){
			length++;
		}
		data[q] = value;
	}

	function populate(){
		if( !options.data ) return false;
		// track the matches
		var stMatchSets = {},
			nullData = 0;

		// no url was specified, we need to adjust the cache length to make sure it fits the local data store
		if( !options.url ) options.cacheLength = 1;

		// track all options for minChars = 0
		stMatchSets[""] = [];

		// loop through the array and create a lookup structure
		for ( var i = 0, ol = options.data.length; i < ol; i++ ) {
			var rawValue = options.data[i];
			// if rawValue is a string, make an array otherwise just reference the array
			rawValue = (typeof rawValue == "string") ? [rawValue] : rawValue;

			var value = options.formatMatch(rawValue, i+1, options.data.length);
			if ( value === false )
				continue;

			var firstChar = value.charAt(0).toLowerCase();
			// if no lookup array for this character exists, look it up now
			if( !stMatchSets[firstChar] )
				stMatchSets[firstChar] = [];

			// if the match is a string
			var row = {
				value: value,
				data: rawValue,
				result: options.formatResult && options.formatResult(rawValue) || value
			};

			// push the current match into the set list
			stMatchSets[firstChar].push(row);

			// keep track of minChars zero items
			if ( nullData++ < options.max ) {
				stMatchSets[""].push(row);
			}
		};

		// add the data items to the cache
		$.each(stMatchSets, function(i, value) {
			// increase the cache size
			options.cacheLength++;
			// add to the cache
			add(i, value);
		});
	}

	// populate any existing data
	setTimeout(populate, 25);

	function flush(){
		data = {};
		length = 0;
	}

	return {
		flush: flush,
		add: add,
		populate: populate,
		load: function(q) {
			if (!options.cacheLength || !length)
				return null;
			/*
			 * if dealing w/local data and matchContains than we must make sure
			 * to loop through all the data collections looking for matches
			 */
			if( !options.url && options.matchContains ){
				// track all matches
				var csub = [];
				// loop through all the data grids for matches
				for( var k in data ){
					// don't search through the stMatchSets[""] (minChars: 0) cache
					// this prevents duplicates
					if( k.length > 0 ){
						var c = data[k];
						$.each(c, function(i, x) {
							// if we've got a match, add it to the array
							if (matchSubset(x.value, q)) {
								csub.push(x);
							}
						});
					}
				}
				return csub;
			} else
			// if the exact item exists, use it
			if (data[q]){
				return data[q];
			} else
			if (options.matchSubset) {
				for (var i = q.length - 1; i >= options.minChars; i--) {
					var c = data[q.substr(0, i)];
					if (c) {
						var csub = [];
						$.each(c, function(i, x) {
							if (matchSubset(x.value, q)) {
								csub[csub.length] = x;
							}
						});
						return csub;
					}
				}
			}
			return null;
		}
	};
};

// TODO refactor, probably move to autocomplete instance methods
$.ui.autocomplete.select = function (options, input, select, config) {
	var CLASSES = {
		DEFAULT: 'ui-autocomplete-state-default',
		ACTIVE: 'ui-autocomplete-state-active'
	};

	var listItems,
		active = -1,
		data,
		term = "",
		needsInit = true,
		element,
		list;

	// Create results
	function init() {
		// TODO just check if element is set instead
		if (!needsInit) return;
		element = $("<div/>")
		.hide()
		.addClass(options.resultsClass)
		// TODO is there a need to append somewhere else? eg. autocomplete in dialog
		.appendTo(document.body);

		list = $("<ul/>").appendTo(element).mouseover( function(event) {
			var e = target(event);
			if(e.nodeName && e.nodeName.toUpperCase() == 'LI') {
				active = $("li", list).removeClass(CLASSES.ACTIVE).index(e);
				$(e).addClass(CLASSES.ACTIVE);
			}
		}).click(function(event) {
			$(target(event)).addClass(CLASSES.ACTIVE);
			select();
			// TODO provide option to avoid setting focus again after selection? useful for cleanup-on-focus
			input.focus();
			return false;
		}).mousedown(function() {
			config.mouseDownOnSelect = true;
		}).mouseup(function() {
			config.mouseDownOnSelect = false;
		});

		if( options.width > 0 )
			element.css("width", options.width);

		needsInit = false;
	}

	// TODO replace with closest("li")?
	function target(event) {
		var element = event.target;
		while(element && element.tagName != "LI")
			element = element.parentNode;
		// more fun with IE, sometimes event.target is empty, just ignore it then
		if(!element)
			return [];
		return element;
	}

	function moveSelect(step) {
		listItems.slice(active, active + 1).removeClass(CLASSES.ACTIVE);
		movePosition(step);
		var activeItem = listItems.slice(active, active + 1).addClass(CLASSES.ACTIVE);
		if (options.scroll) {
			var offset = 0;
			listItems.slice(0, active).each(function() {
				offset += this.offsetHeight;
			});
			if ((offset + activeItem[0].offsetHeight - list.scrollTop()) > list[0].clientHeight) {
				list.scrollTop(offset + activeItem[0].offsetHeight - list.innerHeight());
			} else if (offset < list.scrollTop()) {
				list.scrollTop(offset);
			}
		}
	};

	function movePosition(step) {
		active += step;
		if (active < 0) {
			active = listItems.size() - 1;
		} else if (active >= listItems.size()) {
			active = 0;
		}
	}

	function limitNumberOfItems(available) {
		return options.max && options.max < available
			? options.max
			: available;
	}

	function fillList() {
		list.empty();
		var max = limitNumberOfItems(data.length);
		for (var i=0; i < max; i++) {
			if (!data[i])
				continue;
			var formatted = options.formatItem(data[i].data, i+1, max, data[i].value, term);
			if ( formatted === false )
				continue;
			var li = $("<li/>")
				.html( options.highlight(formatted, term) )
				.addClass(i%2 == 0 ? "ui-autocomplete-even" : "ui-autocomplete-odd")
				.addClass(CLASSES.DEFAULT)
				.appendTo(list)[0];
			$.data(li, "ui-autocomplete-data", data[i]);
		}
		listItems = list.find("li");
		if ( options.selectFirst ) {
			listItems.slice(0, 1).addClass(CLASSES.ACTIVE);
			active = 0;
		}
		// apply bgiframe if available
		if ( $.fn.bgiframe )
			list.bgiframe();
	}

	return {
		display: function(d, q) {
			init();
			data = d;
			term = q;
			fillList();
		},
		next: function() {
			moveSelect(1);
		},
		prev: function() {
			moveSelect(-1);
		},
		pageUp: function() {
			if (active != 0 && active - 8 < 0) {
				moveSelect( -active );
			} else {
				moveSelect(-8);
			}
		},
		pageDown: function() {
			if (active != listItems.size() - 1 && active + 8 > listItems.size()) {
				moveSelect( listItems.size() - 1 - active );
			} else {
				moveSelect(8);
			}
		},
		hide: function() {
			element && element.hide();
			listItems && listItems.removeClass(CLASSES.ACTIVE)
			active = -1;
			$(input).triggerHandler("autocompletehide", [{}, { options: options }], options["hide"]);
		},
		visible : function() {
			return element && element.is(":visible");
		},
		current: function() {
			return this.visible() && (listItems.filter("." + CLASSES.ACTIVE)[0] || options.selectFirst && listItems[0]);
		},
		// TODO refactor, too much going on here
		show: function() {
			var offset = $(input).offset();
			// TODO replace top/left with positionTo, with configurable offset; important for collision detection
			element.css({
				width: typeof options.width == "string" || options.width > 0 ? options.width : $(input).width(),
				top: offset.top + input.offsetHeight,
				left: offset.left
			}).show();

			if(options.scroll) {
				list.scrollTop(0);
				list.css({
					maxHeight: options.scrollHeight,
					overflow: 'auto'
				});

				if($.browser.msie && typeof document.body.style.maxHeight === "undefined") {
					var listHeight = 0;
					listItems.each(function() {
						listHeight += this.offsetHeight;
					});
					var scrollbarsVisible = listHeight > options.scrollHeight;
					list.css('height', scrollbarsVisible ? options.scrollHeight : listHeight );
					if (!scrollbarsVisible) {
						// IE doesn't recalculate width when scrollbar disappears
						listItems.width( list.width() - parseInt(listItems.css("padding-left")) - parseInt(listItems.css("padding-right")) );
					}
				}

			}

			$(input).triggerHandler("autocompleteshow", [{}, { options: options }], options["show"]);

		},
		selected: function() {
			var selected = listItems && listItems.filter("." + CLASSES.ACTIVE).removeClass(CLASSES.ACTIVE);
			return selected && selected.length && $.data(selected[0], "ui-autocomplete-data");
		},
		emptyList: function (){
			list && list.empty();
		},
		unbind: function() {
			element && element.remove();
		}
	};
};

// TODO this should be a generic utility; where else is it of interest?
$.ui.autocomplete.selection = function(field, start, end) {
	if( field.createTextRange ){
		var selRange = field.createTextRange();
		selRange.collapse(true);
		selRange.moveStart("character", start);
		selRange.moveEnd("character", end);
		selRange.select();
	} else if( field.setSelectionRange ){
		field.setSelectionRange(start, end);
	} else {
		if( field.selectionStart ){
			field.selectionStart = start;
			field.selectionEnd = end;
		}
	}
	field.focus();
};

})(jQuery);
