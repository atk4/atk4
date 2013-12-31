/**
  Welcome to Agile Toolkit JS framework.

  This is ATK4 Initialisation Script.
*/

require! ['jquery', 'jquery-ui']

$.atk4 = function(readycheck,lastcall) {
    @._onReady()
}


$.extend $.atk4,{
verison: "3.0",

_onReady: function() { alert 'We are using RequireJS now.' }
}
