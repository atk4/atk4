#!/bin/bash

# This script will help you start your adming using AModules3
#
# Go into directory, where you want to use AModules3 and run
# this script

function exists {
    echo "Files in your current directory already exist. You should run" >&2
    echo "this script from empty directory" >&2
    exit;
}

[ -f kickstart.sh ] && {
    echo "Please go to empty directory and call this script like this:" >&2
    echo "> amodules3/kickstart-admin.sh"
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
\$api = new ApiAdmin('AModules3_website');
\$api->main();
?>
EOF

[ -f amodules3 ] || ln -sf $apdir amodules3
echo "Edit main.php, create your templates inside templates/"
echo "consult documentation on http://adevel.com/amodules3"
