#!/bin/bash

set -e	 # exit if anything fails

dsn=`cat ../config.php | grep "config\['dsn'\]" | grep -vP '^\s*(//|#)' | cut -d= -f2 | sed "s/'/\"/g" | cut -d\" -f2 | sed 's|mysql://||'`
u=`echo $dsn | cut -d: -f1`
p=`echo $dsn | cut -d: -f2 | cut -d@ -f1`
db=`echo $dsn | cut -d/ -f2`

## TODO: verify existance of ~/.my.cnf and if it's not present use $u, $p
[ -f ~/.my.cnf ] || echo "Warning: you should put your username and password into ~/.my.cnf"

echo "* Applying updates on database '$db'"

cd dbupdates
for x in *.sql; do 
	[ -f $x.ok ] && continue
	echo -n " $x... "
	
	# user and password must be in ~/.my.cnf
	if mysql -B "$db" < $x 2> $x.fail; then
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
	for x in storedfx/*manual ; do 
		if mysql -B "$db" < $x 2> $x.fail ; then
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
