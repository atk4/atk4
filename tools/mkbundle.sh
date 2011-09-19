#!/bin/bash

## Creates Bundle from many PHP class files
cd `dirname $0`
cd ..

echo '<?php' > atk4-bundle.php
echo '// Agile Toolkit Bundle: http://agiletoolkit.org/' >> atk4-bundle.php
echo "set_include_path('.'.PATH_SEPARATOR.'.'.DIRECTORY_SEPARATOR.'lib'.PATH_SEPARATOR.dirname(__FILE__).'/lib');" >> atk4-bundle.php

 while read cl; do
    echo "?><?php // File: $cl"
    cat lib/$cl | sed 1d 
done >> atk4-bundle.php <<EOF
static.php
AbstractObject.php
AbstractController.php
AbstractModel.php
AbstractView.php
ApiCLI.php
ApiWeb.php
ApiFrontend.php
View.php
BaseException.php
BasicAuth.php
View/Box.php
View/Button.php
View/ButtonSet.php
View/Columns.php
View/Error.php
View/Hint.php
View/HtmlElement.php
View/Icon.php
View/Info.php
View/Tabs/jUItabs.php
View/Tabs.php
View/Warning.php
Button.php
ButtonSet.php
Columns.php
CompleteLister.php
Controller/Compat.php
DBlite.php
DBlite/dsql.php
DBlite/Exception.php
DBlite/mysql.php
Exception/DB.php
Exception/Hook.php
Exception/StopInit.php
Exception/StopRender.php
ExceptionNotConfigured.php
Filter.php
Form/Field.php
Form/Basic.php
Form/Button.php
Form/Field/DatePicker.php
Form/Field/DateSelector.php
Form/Field/Grouped.php
Form/Field/multiSelect.php
Form/Field/SimpleCheckbox.php
Form/Field/Slider.php
Form/Field/upload.php
Form/Hint.php
Form/Plain.php
Form/Submit.php
Form.php
Frame.php
Grid/Basic.php
Grid.php
H1.php
H2.php
H3.php
H4.php
HelloWorld.php
Hint.php
HR.php
HtmlElement.php
Icon.php
InfoWindow.php
IOException.php
jQuery/Chain.php
jQuery.php
jUI.php
Lister.php
Logger.php
LoremIpsum.php
Menu/Basic.php
Menu/Light.php
Menu.php
ObsoleteException.php
Order.php
P.php
Page/EntityManager.php
Page/Error.php
Page/Tester.php
Page.php
PageManager.php
Paginator.php
PathFinder.php
QuickSearch.php
SMlite.php
SMliteException.php
SQLAuth.php
SQLException.php
static.php
System/HTMLSanitizer.php
System/ProcessIO.php
Tabs.php
Text.php
TMail.php
UpgradeChecker.php
URL.php
EOF
patch atk4-bundle.php < tools/bundle.patch

php -r 'echo php_strip_whitespace("atk4-bundle.php");' > atk4-bundle.min.php

echo "Created `pwd`/atk4-bundle.php"
echo "Created `pwd`/atk4-bundle.min.php"
