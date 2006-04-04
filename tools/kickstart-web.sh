#!/bin/bash

# This script will help you start your adming using AModules3
#
# Go into directory, where you want to use AModules3 and run
# this script
#
# TODO: this needs testing!

function exists {
    echo "Files in your current directory already exist. You should run" >&2
    echo "this script from empty directory" >&2
    exit;
}

[ -f kickstart.sh ] && {
    echo "Please go to empty directory and call this script like this:" >&2
    echo "> amodules3-web/kickstart.sh"
    exit;
}

[ -f .htaccess ] && exists
[ -f main.php ] && exists

apdir=`dirname $0`
loader=$apdir"/lib/loader.php";

echo "apdir=$apdir"

mkdir templates 2>/dev/null

cat > .htaccess <<EOF
RewriteEngine on
RewriteRule     ^(main.php.*)$           \$1                  [L]
RewriteRule     ^([^\./]*(&.*)?)$        main.php?page=\$1    [L]
EOF

cat > main.php <<EOF
<?
include 'amodules3/lib/loader.php';
\$api=new ApiWeb('my_application');
\$api->add('SMlite')->loadTemplate('Index.html');
\$api->main();
?>
EOF

cat >templates/Index.html<<EOF
<?page_content?>
Welcome to AModules3. This template was automatically generated with kickstart.sh. Customize
it, it's located in templates/Index.html. 

See documentation on <a href="http://adevel.com/amodules3/">http://adevel.com/amodules3</a> for information about getting started
with AModules3
<?/page_content?>
EOF


[ -f amodules3 ] || ln -sf $apdir amodules3
echo "Edit main.php, create your templates inside templates/"
echo "consult documentation on http://adevel.com/amodules3"
