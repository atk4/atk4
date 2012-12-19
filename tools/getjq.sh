#!/bin/bash
export v=$1

[ "$v" ] || { echo "Specify version" ; exit 1; }

rm jquery-$v.min.js
wget http://code.jquery.com/jquery-$v.min.js

echo "        else(\$v=\$this->api->getConfig('js/jquery','jquery-$v.min'));   // bundled jQuery version" > tmp
trap "rm tmp" EXIT
vim -e ../../lib/jQuery.php <<EOF
/\/\/ bundled jQuery version/
d
-
r tmp
w
EOF
git add jquery-$v.min.js

