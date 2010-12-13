#!/bin/bash

# file creates a blank Controller file...
# geez what a waste :(

[ "$1" ] || exit

f=`echo $1 | sed 's/_/\//'`
[ -f "$f.php" ] && exit

d=`dirname $f`
mkdir -p $d
mkdir -p ../Model/$d

cat > "$f.php" <<EOF
<?
class Controller_$1 extends Controller {
	public \$model_name='Model_$1';
}
EOF

fl=`echo $f | sed 's/\(.\).*/\1/'`

cat > "../Model/$f.php" <<EOF
<?
class Model_$1 extends Model_Table {
	//public \$entity_code='table';
	public \$table_alias='$fl';
}
EOF
