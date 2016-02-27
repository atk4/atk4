Changes in 4.3.1 to 4.3.2
===

https://github.com/atk4/atk4/compare/4.3.1...release/4.3.2

New Functionality:

 - included View_Console. Previously was only available in Sandbox.
 - extend Controller_Grid_Format to build your own formatters.
 - Added App_TestSuite for building your own test-suites
 - Added Button::onClickConsole(callback) that will execute callback inside a console in a pop-up.

CRUD and Grid:

 - added CRUD::addRef() which will automatically allow editing of related model
 - Grid: add support for passing closure as column type

Agile CSS:

 - updated 0.911 to 0.912
 - added compact.css theme

Models:

 - added support for containsOne / containsMany
 - Customise fields from model `model->addField('name')->onField(function($f){ $f->addClass('atk-swatch-red'); );`
 - added atomic( function($m){} ) method. if error happens inside call-back, everything is rolled back.
 - added Field_Callback: `$model->add('Field_Callback','age')->set(function(){ return rand(10,20); });`
 - improve support for boolean with enum(['Y','N']);


Misc:

 - Lister can use iterators that return objects and not hashes (will use properties as fields)
 - Page::addCrumbReverse added, which is handy to use in subPages
 - App::title and page::title are respected and used as `<title>`
 - Adopted Controller_Tester for non-visual tests
 - URL::isCurrent() can tell if the URL points to the current page.
 - Menu::setModel() allow you to source items from a model
 - Improved look of default log-in box
 - Paginator improve prev/next links
 - View_ModelDetails::setModel support 2nd argument to specify actual fields
 - Menu::addItem() allows 2nd argument to be JS chain
 - successMessage and errorMessage can now contain timer to hide (2nd argument)
 - Lister, Grid: Use $this['name'] inside formatRow() to access $current_row easier
 - Integrate validator: Field->validate(rule) and Form_Field->validate(rule)
 - improve PathFinder_Location::setCDN(url)
 - Improve compatibility with PHPUnit
 - Pass defaults through constructor
 - Allow first argument of add method to contain array: add(['Menu', ['swatch'=>'ink']);
 - Allow styling of Tabs
 - Use GiTemplate in format_template and format_link
 
  

Changes in 4.3.0 to 4.3.1
====

https://github.com/atk4/atk4/compare/4.3.0...4.3.1

This release only contains minor cleanups.



Major Changes 4.2 to 4.3
====
Note: for diff see: https://github.com/atk4/atk4/compare/4.2.4...4.3.0

Development of 4.3 branch has begun in early 2013. The first stable version has been out 2 years later after a lot of testing and changes. Scroll below if you are looking for changes between different 4.2.X releases.


Changed license from Affero GPL to MIT
--------------------------------------
Probably one of the most anticipated changes in the past - allowing you to use Agile Toolkit freely in both open-source, personal and commercial products. We have decided to focus on developing add-on, education and enterprise platforms around Agile Toolkit framework, but keep the core product free for all.

In addition to the framework, Documentation (Book) is also distributed under terms of MIT.

Any contribution to Agile Toolkit will be also included and distributed under MIT license, however the intellectual property will be transferred to Agile Toolkit Limited who owns and maintains the rest of the framework.

Comes with Installer
--------------------
Starting with 4.3 Agile Toolkit is distributed with a "Sandbox" - our extension to Agile Toolkit that will help you install Agile Toolkit, but more importantly, enable you to use add-ons from your Agile Toolkit platform. This includes both open-source and commercial extensions.

Agile Platform enables developers to distribute high-quality extensions and products, but they will only work in conjunction with the Sandbox.

If you are looking to run pure MIT framework, you should "request" it through Composer or clone it from Github.


Introduced Agile CSS Framework
------------------------------
Agile Toolkit 4.2 and prior have always used their own CSS framework. Starting from 4.3 the CSS has undergone a complete rewrite and has been released as a separate product, also under MIT license. http://css.agiletoolkit.org/.

The new CSS framework is designed for building any UI both simple and complex. It is included with 4.3 version (although old templates are also found in "compat" folder).

Additionally we rewrote all the templates into JADE, which are compiled and bundled. All the view (forms, buttons etc) templates Agile Toolkit are updated.

Rewrite of Template Engine
---------------------------

GiTemplate class is here to replace SMLite. While it offers the same functionality and generally has a same implementation, it significantly improves parse speed by relying on recursive regular expressions. 

You can also use new template through array_access:

```
$this->template['title'] = 'My Title Here';
```

The 4.3.0 still has SMLite included and it's used for some of the minor things like format_link and Mail templates, however in a next few minor releases it will be replaced with GiTemplate.


New Template Tag Format
-----------------------
All the templates have changed to use `{$tag}` format instead of `<?$tag?>` that has been used in the past. Script is included to modify all your existing templates to the new ones.

New Controller_Data
-------------------
The latest versions of 4.2 have introduced support for generic Model and use of generic Controller_Data. This allows you to implement 3rd party data storage systems. The support however is quite limited and not very usable.

In 4.3 Controller_Data is fully re-implemented. The good news is that the new Controller_Data format is much more powerful and simpler to implement. A lot of functionality has been moved into Model.

There is also a concept of "Capabilities" which allows your data source to implement only what's needed and the rest of framework can recognize those limitations.


New Declarative Validator
-------------------------
In the past Agile Toolkit documentation would instruct you to create your own validation inside model hook or form submission handler. Now Agile Toolkit bundles a powerful validator.

The 4.3.0 version includes Controller_Validatior, but later versions will integrate it into forms and models.

A typical use would be `$this->addField('phone')->validate('required|phone|len|>5')` which actually adds 3 checks on your field: makes it mandatory, requests it contain a valid phone number and asks it to be more than 5 characters long.

New Pattern Router
------------------

New Pattern Router class allows you to define and use URLs where part of URL will be converted into variable. Example would be `shop/view/294` which is routed to page_shop_view and passes id=294 as argument.

Added MySQL Migrator
---------------------

Controller_Migrator_MySQL now allows you to automate processing of database migration scripts from inside PHP.

Added View_Popover
------------------
The new widget can be used to bind pop-over on your buttons and menu items. It will display a box with loadable contents on your page which can contain either form or menu.

Added View_Console
------------------
A new view allowing you to execute process in Agile Toolkit or in command-line and stream output to the browser. This View uses Server Side Notifications to update browser about the progress of your project.


Added App_REST
--------------
Agile Toolkit can now be used for creating REST APIs. The new Application class allow you to define interfaces that can easily be linked with models and give access to all or part of their data-set. Several features are included such as authentication, error codes etc.


Autoloader and Composer Compatibility
-------------------------------------
Agile Toolkit is compatible with Composer and is using new auto-loading techniques. This allows you to easily define "atk4" as dependency in any project and also consume 3rd party add-ons in your ATK project with ease.

Cleaning code and PSR-2 compatibility
-------------------------------------
We have put a lot of time to clean up our source files and make them compatible with PSR-2 standard.

New pattern for substituting text with arrays
---------------------------------------------
Many Views in 4.3 support a new way to pass arguments ot them. 

```
$this->add('Button')->set(['Hello','icon'=>'check']);
```
The pattern is based around the new array style [''] and will treat first element of the array as backward-compatible text, while other arguments denote arguments. This pattern is super-readable and has been widely adopted across all framework.

PathFinder Changes
-------------------
Version of 4.2 in Agile Toolkit would normally be installed into "webroot". There was a "way" to create a "public" folder inside your project class to keep framework files outside of your webroot. In 4.3 the way to use "public" class like that has became the standard. The framework works well if you don't use "public" folder yet. 

AJAX Callback enhancements
--------------------------
The default way to handle form submission has changed in 4.3. Now you should use closure.

```
$form->onSubmit(function($f){ $f->save(); return "Thank you"; });
```

The benefit here is that exception thrown inside closure are properly handled by form. You can also return string to indicate success message, or `$f->error()` to indicate error. Finally you can return JS chain which will be executed.

Added View::on()
----------------
jQuery has added on() method to handle events more efficiently. Agile Toolkit has also added `on()` method for Views in 4.3. In a basic form you could do:

```
$lister->on('click','.do-action')->hide();
```

and if your lister contains multiple elements with class=do-action, you should be able to interact with them individually. The functionality of this method goes much further and even allows you to define a PHP callback and interact with bundled data:

```
$lister->on('click','.do-action', function($j, $data) {
    return $j->data('cnt', $data['cnt']+1)
       ->univ()
       ->alert('Count was '.$data['cnt'])
    ); 
});
```
The code above not only will receive data-cnt of the element, but will increase and set it back through a targeted chain.

Implemented Vertical and Horizontal Menus
-----------------------------------------
New CSS framework enables all sort of new Views. Agile Toolkit bundles new implementation for Vertical / Horizontal menus. When you download and use ATK you get one menu right out of the box in your "Admin" section.

The new View is based on Menu add-on: https://github.com/atk4/menu

http://book.agiletoolkit.org/views/menu.html


Added support for Layouts
--------------------------
Previously boilerplate HTML and application layout was stored in `shared.html` file. Now the boilerplate HTML is inside `html.html` and you can define and use one of several layouts using `add('Layout_X')` method inside your application. Layout is a view containing global items on your page such as menu, footer, header. 

Admin comes with a default layout ('Fluid') and frontend is using Layout_Centered by default, but you can easily add your own layouts.


Other changes in 4.2.4 to 4.3.0
-------------------------------

Core:
 
 - ApiWEB, ApiFrontend, etc are now renamed into App/Web, App/Frontend etc respectively.
 - A standard property of all objects $this->api renamed into $this->app. $api remains for compatibility.
 - univ.js is split up into 2 libraries univ_basic.js and univ_jui.js. All jQuery UI related functionality is moved to second file.
 - Changed the way how unique names are generated for objects ($this->short_name)
 - CRUD will always initialize $form / $grid properties to Dummy object instead of leaving them null, so don't write `if($crud->form)` anymore, it will always be true. Use `$crud->isEditing()` !!
 - updated jQuery and jQuery UI versions

Experimental features:

 - implemented Controller_Data_SQL - future replacement for SQL_Model. Lacks in functionality, but works with "Model" class. 
 - added runTests() method for built-in object tests

Misc:
 
 - learn($key, $default) uses one default parameter instead of accepting 3 possible values
 - exception($message) requires you to always use a message
 - allow exception chaining exception::by(other exception)
 - exception now supports ->setCode(123)
 - standard exception to contain HTTP-compatible codes, such as may $model->load() throw exception with 404 code
 - App/Installer - now has $show_intro support


Grid and CRUD:

 - Grid - rename format_real into format_float (because PHP uses floats)
 - Grid - added format_object
 - Grid - added support for GrandTotals
 - CRUD - cleanup on allow_add, allow_del, allow_edit
 - CRUD - add support for editing modes other than "add" or "edit"
 - CRUD - now has $grid_options and $form_options

Models:

 - supply array to addCondition to implement OR
 - Page: implemented addBreadCrumb
 
  
Fixes:

 - memorization of url during login
 - fixed problem new hook handler is added while hook is executed
 - fix Checkbox field compatibility with type('boolean')->enum(['Y','N']);
 - Fix #587 (paginator), 
 - addCondition('foo', [1,2]) will not set a defaultValue for field 'foo'
 - When exception is caught in command-line will use exception's code as exit code
 - CLI improve error message output
 - Improve support for hierarchical models
 - format_link accepts 'id_value' attribute
 - Controller_Data_REST: allow to define how PUT/POST are used
 - Exception::addAction(['foo'=>'bar'], $recommendation) allows to specify recommendation and multiple actions
 - model beforeInsert() receives reference to insert data, not a copy, so you can change
 - DSQL: Allow sub-selects used instead of table
 - improve Controller_Data_Array
 - Grid::addPaginator 3rd argument can specify class of paginator to use
 - Sticky argument handling in Virtual Pages
 - Fix for nested virtual pages
 - Fixes in ProcessIO
 - Improving localization, _() in more objects
 - Model_Table renamed to SQL_Model
 - Form implements ArrayAccess. Use $f['name'] in onSubmit callback.
 - Order added middleof functionality
 - Added Auth password encryption support for password_hash

Agile CSS

 - Make successMessage look nicer
 - Exceptions now use Agile CSS formatting
 - Improve look for View_Error, View_Success

Removed Obsolete:

 - Form_Field_Grouped
 - Paginator_Compat
 - Removed method RenderOnly
  

Cleanups:

 - made code more clean and compliant with PSR
 - added comments
 - added contributor agreement
 - cleaned up error display
 - exceptions can open documentation from backtrace files





Changes 4.2.3 to 4.2.4
====
Note: for diff see: https://github.com/atk4/atk4/compare/4.2.3...4.2.4

General:

- Added PSR-2 compliance to many core files. Will continue to improve other classes too.
- Added composer.json. Agile Toolkit can be installed through Composer now
- when calling setController, second argument can specify name or default options
- Security fixes in Logger
- added each() method (similar to jQuery) for model and dsql. Will execute specified callable for each row.
- added support for <?js?> into templates
- api->addSharedLocation() is now called (if defined) by PathFinder before any locations are initialized
- added ApiInstall for building installers. For now undocumented as it might still change. Use with care.
- improved error outputting. Removed obsolete code and better highlight line which produced error
- improvements in Tester
- upgrade to jQuery 1.8.3 and jQuery UI 1.9.2
- added tools/getjq which automates bungling of jquery and jquery ui (also updates PHP)
- remove ability to debug models through GET argument
- improved support for nested namespaces, added PSR-0 compliance for pathfinder
- prevent readonly field from erasing field value
- added VirtualPage, allowing you to create separate blank page and display that instead. Useful in popups.
- added PHPCS sniff configuration for Agile Toolkit style validation (tools/phpcs)
- sql migration scripts now can output things with "select 'blah';" during migrations
- bug-fixes as usual

CRUD:

- CRUD is refactored using VirtualPage and new button. Now much more extensible and faster.
- CRUD->addRef() allow to drill into model's hasMany() relation through crud inside expander. Uses VirtualPage
- CRUD->addFrame() is a handy way now to create pop-up with some UI. Uses VirtualPage
- CRUD now shows better labels on buttons and dialogs (Add User instead of Add)
- allow_add, allow_edit, etc are now protected. Don't change them directly, specify through add() options.

NoSQL:

- severely improved handling of NoSQL models
- added MemCache support
- added Session support 
- added loadBy and similar methods
- several bug fixes
- severily improved handling of NoSQL models
- added support for caching (addCache())

DSQL:

- group() supports expressions now
- casting DSQL to string executes getOne() instead of returning selecting
- above fix revelaed many minor bugs. fixed them.
- added dsql->fieldQuery(), similar to model->fieldQuery(). Will delete other fields and query only specified one
- dsql->sum() improved
- order() for DSQL and Models chaining rule changed. Last call to order() will now be the main sorting order. Calling order with multiple arguments behaves in same way
- expr("hello, [name]",array('name'=>'world')) is now properly supported. Currently does not escape, BUT WILL!

Model:

- will silently ignore incorrect join type (otherwise it's considered as table alias creating SQL errors)
- added Model_Table->tryDelete()
- added sorting and limit support for Model (non-relational)
- hasMany can now be aliased by specifying 4th argument, which is then used inside ref().

JS:

- changed scrolling behaviour and vertical sizing of frameURL / dialogURL
- fixed form's behaviors with shortened names
- improved icon-only button handling (uses text=false)
- checkboxes widget has 2 new methods - select_all and unselect_all

CSS and LESS:

- separated out mixing into atk4-mixins.less
- removed prefix (used to be 'ui-icon') from Button->setIcon(). 

Form:

- readonly fields are displayed with nl2br
- form->setLayout() also supports SMlite object (if you don't want to specify a file)

Views:

- see HTML produced by any view by calling view->debug()
- added Menu_jUI - jQuery-compatible menu
- added View_DropButton 
- added View_Flyout
- menu URL can be jQuery_Chain now
- Quicksearch and Grid cleaned up
- CompleteLister total counting changed. instead of $totals['row_count'] use $total_rows
- Filter improved
- Grid's multi-value fields will now show value instead of key.
- Cleaned up model type => form field type associations in MVCForm
- setProperty() is now obsolete, so use setAttr()

Application:

- added ability to specify a different Logger class through $logger_class property
- added destroySession into ApiWeb

Removed obsoletes:

- removed Form_Field->setNotNull() use validateNotNull instead
- removed Form_Field->setDefault() and getDefault(), use set() / get()



Changes 4.2.1 to 4.2.3
====
*Note: 4.2.2 was not oficially released*

DSQL:
- added andExpr()
- reimplemented order()
- fixed problem with "having" suddenly using "or" instead of "and"
- group() can accept expressions now
- added concat(var args) and describe(table)
- added support for SQLite
- added support for preexec - executes query faster but not fetching results yet
- getDebugQuery() returns same output debug() would normally echo.

UI:

- improved localization through api->_
- Add grid buttons into buttonset
- Removed support for Grid::inline type (it's an add-on now)
- Changed implementation of field->showError() , relies on custom attribute now
- field::addClass and field::setClass added
- cleaned up obsolete magic quotes code
- checkbox, radio and dropdown fileds moved from Form_Field into individual files. Therefore their type must be properly capitalized
- fixed duplicate id on <form> tag which conflicted with wrapping <div> in forms
- exceptions from validate hook will be cought and displayed as error (in case of ValidityCheck and ForUser)
- menu has better localization support

Models:

- improved handling of boolean values
- models and dsql can now work with multiple database connections
- field::getExpr() returns expression for individual field selecting (useful for sub-selects)
- hasOne() 4th argument (as_field) can be used to rename dereferenced field
- setOrder() have been made much more flexible
- added support for preexec (currently for internal uses mostly)
- added tryLoadRandom and loadRandom()

Misc:

- Lorem ipsum is now wrapped in div, so you can apply js on it
- Logger will respect $config['logger']['log_dir'] even if logging is off by default, and you are logging something explicitly (through logVar)
- Added support for using jQuery out of CDN
- jQuery and jQuery UI versions are updated
- Ability to override chain class name in jQuery
- View_Warning uses jQuery UI styles now (and icon)
- You can specify jUI_Tabs::options 
- specifying URL to url() works correctly
- allow to use hasMany on joined table fields
- pathfinder logic changed slightly
- paginator changed to avoid double-querying
- getDestinationURL is now obsolote
- Tester has now support for non-interactive testing
- added some Sublime snippets under tools
- added update.sh which can be used with SQLite
- added check for proper availability of PDO extension
- SQLile queries are restarted automatically if they fail (on schema change)
- Added Lister::current_row_html
- Auth can be used with any fields, not only email/password
- Auth - improved handling of encryption hook, won't apply it twice now
- Auth - fixed md5 encryption support
- Added License checks
- $api->cut($myview) will limit output of API to only specified object
- Added ApiFrontend->routePages() allowing to redirect certain pages into add-on
- Added support for SUHOSIN GET argument limitation
- Added support for custom loggers
- Speed optimizations
- Added support for error codes in ->exception() method


