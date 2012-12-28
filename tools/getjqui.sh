#!/bin/bash
export v=$1

[ "$v" ] || { echo "Specify version" ; exit 1; }

mkdir tmp
trap "echo 'cleanup'; rm -rf tmp" EXIT

(
    cd tmp
    wget http://jqueryui.com/resources/download/jquery-ui-$v.custom.zip
    unzip jquery-ui-$v.custom.zip
    mv jquery-ui-$v.custom/js/jquery-ui-$v.custom.min.js ../jquery-ui-$v.min.js

echo "        else(\$v=\$this->api->getConfig('js/jqueryui','jquery-ui-$v.min'));  // bundled jQueryUI version" > line
vim -e ../../../lib/jUI.php <<EOF
/\/\/ bundled jQueryUI version/
d
-
r line
w
EOF

)

git add jquery-ui-$v.min.js

echo "WARNING: You must upate CSS theme manually! Update: ../shared/css/jquery-ui.css"
