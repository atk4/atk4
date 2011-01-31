#!/bin/bash

# This file creates necessary packages for the ZIP files


v="3.0.1"

p=`pwd`
b=`basename $p`
v="-$v"

if [ "$b" != "distfiles" ]; then
  [ -d .git ] || exit
  mkdir -p distfiles
  cd distfiles
fi

# Check out files
if [ -d atk4-source ]; then
  ( cd atk4-source; git pull origin master )
else
  git clone git://github.com/atk4/atk4.git atk4-source
fi

if [ -d atk4-addons-source ]; then
  ( cd atk4-addons-source; git pull origin master )
else
  git clone git://github.com/atk4/atk4-addons.git atk4-addons-source
fi

ln -sf atk4-source/tools/makereleases.sh .

# create atk4 standalone build
echo "Creating atk4 distrib files"
rm -rf atk4
(cd atk4-source; git checkout-index -a -f --prefix=../atk4/)
zip -r atk4$v.zip atk4/ >/dev/null
tar -czf atk4$v.tgz atk4
rm -rf atk4


# create atk4 standalone build
echo "Creating atk4-addons distrib files"
rm -rf atk4-addons
(cd atk4-addons-source; git checkout-index -a -f --prefix=../atk4-addons/)
zip -r atk4-addons$v.zip atk4-addons/ >/dev/null
tar -czf atk4-addons$v.tgz atk4-addons
rm -rf atk4-addons

# create atk4-sample-project build

echo "Creating atk4-sample-project distrib files"
cp -aR atk4-source/tools/sampleproject atk4-sample-project
(cd atk4-source; git checkout-index -a -f --prefix=../atk4-sample-project/atk4/)
(cd atk4-addons-source; git checkout-index -a -f --prefix=../atk4-sample-project/atk4-addons/)
zip -r atk4-sample-project$v.zip atk4-sample-project/ >/dev/null
tar -czf atk4-sample-project$v.tgz atk4-sample-project
rm -rf atk4-sample-project

# create atk4-sample-website build

echo "Creating atk4-sample-website distrib files"
cp -aR atk4-source/tools/samplewebsite atk4-sample-website
(cd atk4-source; git checkout-index -a -f --prefix=../atk4-sample-website/atk4/)
zip -r atk4-sample-website.zip atk4-sample-website/ >/dev/null
tar -czf atk4-sample-website.tgz atk4-sample-website
rm -rf atk4-sample-website

#(cd atk4-source; git checkout-index -a -f --prefix=../atk4)
#(cd atk4-addons-source; git checkout-index -a -f --prefix=../atk4-addons)
