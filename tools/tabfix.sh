#!/bin/bash -x
d=`dirname $0`

# fixes file's tabbing
vim -s "$d/tabfix.vim" -e $1
