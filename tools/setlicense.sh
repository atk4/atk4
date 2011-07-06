#!/bin/bash

# WARNING:
#  Agile Toolkit 4 is copyright of Agile Technologies Limited Ireland.
#  Toolkit it freely available under terms of AGPL license. You are
#  permitted to download, use it for any project as long as that project
#  code is licensed under AGPLv3. You must provide source code download
#  link on your project website.
#
#  !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
#  Using this script to strip off license terms of Agile Toolkit 4
#  without agreement from copyright holder is a criminal offerce.
#
#  Using this code in combination with proprietary code in on-line
#  (Software as a Service or Application Service) or distribution
#  of such packages are violation of Licensing terms.
#  !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
#
#  If you are willing to use Agile Toolkit 4 in your project under
#  other license - please visit http://www.atk4.com/commercial
#  

[ -f tools/header1 ] || { echo "Run from main atk4 directory"; exit; }
d=`dirname $0`

find lib -name '*.php' | while read f; do 

echo -n "$f.. "

grep -q '**ATK**' $f || {

vim -e $f <<EOF
1r tools/header1
w
EOF
echo "added"
continue

}


# Replace license text
vim -e $f <<EOF
H
/\*\*ATK4\*\*\*/,/\*\*\*ATK4\*\*\//-1d
/\*\*\*ATK4\*\*\//-1r tools/header2
%s/atk4\.com/agiletoolkit.org/g
w!
EOF


#indent
#vim -esc "normal gg=G" -c "wq" $f

echo "updated"

done
