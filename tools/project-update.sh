#!/bin/bash

# Update the files from Git
git pull

# First, get composer there
if [ -f composer.json ]; then
    if [ -f composer.phar ]; then
        echo "== STEP 1 == Updating composer dependencies"
        php composer.phar self-update
        php composer.phar update
    else
        echo "== STEP 1 == Installing composer dependencies"
        curl -sS https://getcomposer.org/installer | php
        php composer.phar install

        # If there are some folders around, link them
        echo "== STEP 2 == Linking files"
        CSS=""
        [ -x ~/Sites/agiletoolkit-css ] && CSS="$HOME/Sites/agiletoolkit-css"
        [ -x /var/www/agiletoolkit-css ] && CSS="/var/www/agiletoolkit-css"
        [ -x ~/www/agiletoolkit-css ] && CSS="$HOME/www/agiletoolkit-css"

        if [ "$CSS" ]; then
            echo "== STEP 2.1 == Use CSS"
            echo "HEY, I found CSS library in $CSS, so I'm going to use it"
            ln -fs $CSS .
        fi

        echo "== STEP 3 == Link ATK"
        ATK=""
        [ -x ~/Sites/atk4 ] && ATK="$HOME/Sites/atk4"
        [ -x ~/Sites/atk43 ] && ATK="$HOME/Sites/atk43"
        if [ "$ATK" ]; then
            echo "HEY, I found ATK library in $ATK, so I'm going to link it into vendor/atk4/atk4"
            rm -rf vendor/atk4/atk4
            ln -fs $ATK vendor/atk4/atk4

            # Need to make sure composer is not getting upset about it
        fi

        echo "== STEP 4 == Fixing borked Composer"
        # composer fucks up few things, so fix them
        ( cd vendor/atk4/atk4; git remote rm composer )
        ( cd vendor/atk4/atk4; git checkout 4.3 )
        ( cd vendor/atk4/atk4; git remote add composer git://github.com/atk4/atk4.git )

        touch config-auto.php
        chmod 777 config-auto.php
    fi
fi

echo "== STEP 5 == Link ATK"
cat dependencies | while read dir path; do
  if [ -x $dir ]; then
    echo " => Updating $dir"
    ( cd $dir; git pull )
  else
    echo " => Installing $dir from $path"
    mkdir -p $dir
    ( cd $dir; git clone $path . )
  fi
done
