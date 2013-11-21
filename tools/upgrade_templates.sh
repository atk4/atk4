#!/bin/bash

find . -name '*.html' | while read f; do
echo "Upgrading $f"
vim -e $f <<EOF
%s/?>/}/g 
%s/<?/{/g 
w
EOF
done
