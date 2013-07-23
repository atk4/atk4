(function( $ ){
//plugin buttonset vertical
$.fn.buttonsetv = function() {
  $(':radio, :checkbox, button', this).wrap('<div style="margin: 0; padding: 0"/>');
  $(this).buttonset();
  $('button:first', this).removeClass('ui-corner-left').addClass('ui-corner-top');
  $('button:last', this).removeClass('ui-corner-right').addClass('ui-corner-bottom');
  mw = 0; // max witdh
  $('button', this).each(function(index){
     w = $(this).width();
     if (w > mw) mw = w;
  })
  $('button', this).each(function(index){
    $(this).width(mw);
  })
};
})( jQuery );