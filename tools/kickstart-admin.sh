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
apdir=`dirname $apdir`
loader=$apdir"/lib/loader.php";

echo "apdir=$apdir"

mkdir templates 2>/dev/null

cat > .htaccess <<EOF
RewriteEngine on
RewriteBase /
RewriteRule .* - [E=URL_ROOT:/]
RewriteRule     ^(main.php.*)$           $1                  [L]

RewriteRule     ^([^\.]*\.html.*)$            main.php   [L]
RewriteRule     ^([^\.]*\.xml.*)$            main.php   [L]
RewriteRule     ^([^\./&?]*)$            main.php   [L]
EOF

cat > main.php <<EOF
<?
include '$apdir/loader.php';
\$api = new ApiAdmin('ATK','jui');
\$api->main();
?>
EOF

if echo $apdir | grep -q '\.\./'; then
cat >> .htaccess <<EOF
RewriteRule     ^(.*gif)$    wrap.php?file=\$1&ct=image,gif   [L]
RewriteRule     ^(.*png)$    wrap.php?file=\$1&ct=image,png   [L]
RewriteRule     ^(.*css)$    wrap.php?file=\$1&ct=text,css   [L]
RewriteRule     ^(.*jpg)$    wrap.php?file=\$1&ct=text,jpg   [L]
RewriteRule     ^(.*js)$    wrap.php?file=\$1&ct=application,x-javascript   [L]
EOF


cat > wrap.php <<EOF
<?
\$amodules3_path="$apdir";
include_once \$amodules3_path.'/tools/generic-wrap.php';
EOF
fi

ln -sf $apdir/tools/update.sh .
mkdir docs
mkdir docs/dbupdates

# we don't link anymore we use them out from there :)
#[ -f amodules3 ] || ln -sf $apdir amodules3
echo "Edit main.php, create your templates inside templates/"
echo "consult documentation on http://adevel.com/amodules3"
