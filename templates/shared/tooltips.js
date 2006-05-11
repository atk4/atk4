/*
originally written by paul sowden <paul@idontsmoke.co.uk> | http://idontsmoke.co.uk
modified and localized by alexander shurkayev <alshur@narod.ru> | http://htmlcoder.visions.ru
*/

window.onerror = null;
var tooltip_attr_name = "tooltip";
var tooltip_blank_text = "(откроется в новом окне)"; // текст для ссылок с target="_blank"
var tooltip_newline_entity = "  "; // укажите пустую строку (""), если не хотите использовать в tooltip'ах многострочность; ежели хотите, то укажите тот символ или символы, которые будут заменяться на перевод строки
var tooltip_max_width = 0; // максимальная ширина tooltip'а в пикселах; обнулите это значение, если ширина должна быть нелимитирована

window.onload = function(e){
	if (document.createElement) tooltip.d();
}

tooltip = {

	t: document.createElement("DIV"),
	c: null,
	g: false,

	m: function(e){
		if (tooltip.g){
			oCanvas = document.getElementsByTagName(
			(document.compatMode && document.compatMode == "CSS1Compat") ? "HTML" : "BODY"
			)[0];
			x = window.event ? event.clientX + oCanvas.scrollLeft : e.pageX;
			y = window.event ? event.clientY + oCanvas.scrollTop : e.pageY;
			tooltip.a(x, y);
		}
	},

	d: function(){
		tooltip.t.setAttribute("id", "tooltip");
		//tooltip.t.style.filter = "alpha(opacity=85)"; // buggy in ie5.0
		document.body.appendChild(tooltip.t);
		a = document.all ? document.all : document.getElementsByTagName("*");
		aLength = a.length;
		for (var i = 0; i < aLength; i++){

			//if (a[i].tagName == "A" || a[i].tagName == "BUTTON" || (a[i].tagName == "INPUT" && (a[i].type == "submit" || a[i].type == "button" || a[i].type == "reset"))) a[i].onclick = self.focus;

			if (!a[i]) continue;

			tooltip_title = a[i].getAttribute("title");
			tooltip_alt = a[i].getAttribute("alt");
			tooltip_blank = a[i].getAttribute("target") && a[i].getAttribute("target") == "_blank" && tooltip_blank_text;
			if (tooltip_title || tooltip_blank){
				a[i].setAttribute(tooltip_attr_name, tooltip_blank ? (tooltip_title ? tooltip_title + " " + tooltip_blank_text : tooltip_blank_text) : tooltip_title);
				if (a[i].getAttribute(tooltip_attr_name)){
					a[i].removeAttribute("title");
					if (tooltip_alt && a[i].complete) a[i].removeAttribute("alt");
					tooltip.l(a[i], "mouseover", tooltip.s);
					tooltip.l(a[i], "mouseout", tooltip.h);
				}
			}else if (tooltip_alt && a[i].complete){
				a[i].setAttribute(tooltip_attr_name, tooltip_alt);
				if (a[i].getAttribute(tooltip_attr_name)){
					a[i].removeAttribute("alt");
					tooltip.l(a[i], "mouseover", tooltip.s);
					tooltip.l(a[i], "mouseout", tooltip.h);
				}
			}
			if (!a[i].getAttribute(tooltip_attr_name) && tooltip_blank){
				//
			}
		}
		document.onmousemove = tooltip.m;
		window.onscroll = tooltip.h;
		tooltip.a(-99, -99);
	},

	s: function(e){
		d = window.event ? window.event.srcElement : e.currentTarget;
		if (!d.getAttribute(tooltip_attr_name)) return;
		s = d.getAttribute(tooltip_attr_name);
		if (tooltip_newline_entity){
			s = s.replace(/\&/g,"&amp;");
			s = s.replace(/\</g,"&lt;");
			s = s.replace(/\>/g,"&gt;");
			s = s.replace(eval("/" + tooltip_newline_entity + "/g"), "<br />");
			tooltip.t.innerHTML = s;
		}else{
			if (tooltip.t.firstChild) tooltip.t.removeChild(tooltip.t.firstChild);
			tooltip.t.appendChild(document.createTextNode(s));
			//tooltip.t.innerText = s;
		}
		tooltip.c = setTimeout("tooltip.t.style.visibility = 'visible';", 0);
		tooltip.g = true;
	},

	h: function(e){
		tooltip.t.style.visibility = "hidden";
		if (!tooltip_newline_entity && tooltip.t.firstChild) tooltip.t.removeChild(tooltip.t.firstChild);
		clearTimeout(tooltip.c);
		tooltip.g = false;
		tooltip.a(-99, -99);
	},

	l: function(o, e, a){
		if (o.addEventListener) o.addEventListener(e, a, false); // was true--Opera7b workaround!
		else if (o.attachEvent) o.attachEvent("on" + e, a);
			else return null;
	},

	a: function(x, y){
		oCanvas = document.getElementsByTagName(
		(document.compatMode && document.compatMode == "CSS1Compat") ? "HTML" : "BODY"
		)[0];

		w_width = window.innerWidth ? window.innerWidth + window.pageXOffset : oCanvas.clientWidth + oCanvas.scrollLeft;
		w_height = window.innerHeight ? window.innerHeight + window.pageYOffset : oCanvas.clientHeight + oCanvas.scrollTop;

		tooltip.t.style.width = "auto";

		t_width = window.event ? tooltip.t.clientWidth : tooltip.t.offsetWidth;
		t_height = window.event ? tooltip.t.clientHeight : tooltip.t.offsetHeight;

		if ((tooltip_max_width) && (t_width > tooltip_max_width)){
			tooltip.t.style.width = tooltip_max_width + "px";
			t_width = window.event ? tooltip.t.clientWidth : tooltip.t.offsetWidth;
		}

		t_extra_width = 7; // CSS padding + borderWidth;
		t_extra_height = 5; // CSS padding + borderWidth;

		tooltip.t.style.left = x + 8 + "px";
		tooltip.t.style.top = y + 8 + "px";

		while (x + t_width + t_extra_width > w_width){
			--x;
			tooltip.t.style.left = x + "px";
			t_width = window.event ? tooltip.t.clientWidth : tooltip.t.offsetWidth;
		}

		while (y + t_height + t_extra_height > w_height){
			--y;
			tooltip.t.style.top = y + "px";
			t_height = window.event ? tooltip.t.clientHeight : tooltip.t.offsetHeight;
		}
	}
}
