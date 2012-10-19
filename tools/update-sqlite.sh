#!/bin/bash

set -e	 # exit if anything fails


db='content.sqlite'

command="sqlite3 $db"

echo "* Applying updates on database '$db'"

cd dbupdates
for x in *.sql; do 
	[ -f $x.ok ] && continue
	echo -n " $x... "
	
	# user and password must be in ~/.my.cnf
	if $command < $x 2> $x.fail; then
		mv $x.fail $x.ok
		echo 'ok'
	else
		echo 'fail'
		cat $x.fail
		echo
	fi
done
cd ..

if [ -d 'storedfx' ]; then
	echo "* Re-Importing stored procedures"
	
	cnt=0
	for x in storedfx/*.sql ; do 
		if $command < $x 2> $x.fail ; then
			rm $x.fail
			cnt=$(( $cnt + 1 ))
		else
			echo -n " $x... "
			echo 'fail'
			cat $x.fail
			echo
		fi
	done
	echo " $cnt procedures imported"
fi

echo "* Done"
