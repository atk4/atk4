#!/bin/bash

# This file creates necessary packages for the ZIP files


v="4.1.3"
br="master"

p=`pwd`
b=`basename $p`

if [ "$b" != "lab" ]; then
	exit
#[ -d .git ] || exit
fi

# Check out files
if [ -d atk4-source ]; then
  ( cd atk4-source; git pull origin $br )
else
  git clone git://github.com/atk4/atk4.git atk4-source
fi

if [ -d atk4-addons-source ]; then
  ( cd atk4-addons-source; git pull origin $br )
else
  git clone git://github.com/atk4/atk4-addons.git atk4-addons-source
fi

if [ -d atk4-example-source ]; then
  ( cd atk4-example-source; git pull origin $br )
else
  git clone git://github.com/atk4/atk4-example.git atk4-example-source
fi

if [ -d dvdrental-source ]; then
  ( cd dvdrental-source; git pull origin 'instbundle' )
else
  git clone git@github.com:atk4/dvdrental.git dvdrental-source
fi

if [ -d sitesample-source ]; then
  ( cd sitesample-source; git pull origin 'master' )
else
  git clone git@github.com:romaninsh/atk4-sitesample.git sitesample-source
fi

v=`cat atk4-source/VERSION`
v="-$v"
echo -- $v

ln -sf atk4-source/tools/makereleases.sh .

# create atk4 standalone build
#echo "Creating atk4 distrib files"
#rm -rf atk4
#(cd atk4-source; git checkout-index -a -f --prefix=../atk4/)
#zip -r atk4$v.zip atk4/ >/dev/null
##tar -czf atk4$v.tgz atk4
#rm -rf atk4
#
#
## create atk4-addons standalone build
#echo "Creating atk4-addons distrib files"
#rm -rf atk4-addons
#(cd atk4-addons-source; git checkout-index -a -f --prefix=../atk4-addons/)
#zip -r atk4-addons$v.zip atk4-addons/ >/dev/null
##tar -czf atk4-addons$v.tgz atk4-addons
#rm -rf atk4-addons

# create atk4-addons standalone build
echo "Creating agiletoolkit distrib files"
rm -rf atk4-example
rm -rf agiletoolkit
(cd atk4-example-source; git checkout-index -a -f --prefix=../agiletoolkit/)
(cd atk4-source; git checkout-index -a -f --prefix=../agiletoolkit/atk4/)
(cd atk4-addons-source; git checkout-index -a -f --prefix=../agiletoolkit/atk4-addons/)
(cd dvdrental-source; git checkout-index -a -f --prefix=../agiletoolkit/examples/dvdrental/)
(cd sitesample-source; git checkout-index -a -f --prefix=../agiletoolkit/examples/website/)
(cd agiletoolkit; find -name .gitignore | xargs rm; find -name .gitmodules | xargs rm)
rm agiletoolkit$v.zip
zip -r agiletoolkit$v.zip agiletoolkit/ >/dev/null
#tar -czf agiletoolkit$v.tgz agiletoolkit
rm -rf agiletoolkit
