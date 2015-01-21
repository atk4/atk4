
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


