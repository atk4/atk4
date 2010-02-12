#!/bin/bash

set -e	 # exit if anything fails

dsn=`cat ../config.php | grep "config\['dsn'\]" | cut -d= -f2 | sed "s/'/\"/g" | cut -d\" -f2 | sed 's|mysql://||'`
u=`echo $dsn | cut -d: -f1`
p=`echo $dsn | cut -d: -f2 | cut -d@ -f1`
db=`echo $dsn | cut -d/ -f2`

echo "Applying updates on database '$db'"

cd dbupdates
for x in *.sql; do 
	[ -f $x.ok ] && continue
	echo -n " $x... "
	
	# user and password must be in ~/.my.cnf
	if mysql -B "$db" < $x 2> $x.fail; then
		mv $x.{fail,ok}
		echo 'ok'
	else
		echo 'fail'
	fi
done

echo "  done"
