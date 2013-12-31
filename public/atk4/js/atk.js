 /**
  Welcome to Agile Toolkit JS framework.

  This is ATK4 Initialisation Script.
*/

require(['jquery', 'jquery-ui'], function() {

$.atk4 = function(readycheck,lastcall) {
    this._onReady()
}


$.extend($.atk4,{
verison: "3.0",

_onReady: function() { alert('We are using RequireJS now.') }
})
 })