#!/bin/bash
[ -f "config.php" ] || exit

find -type d | sudo xargs chmod g+sw
sudo chgrp -R webmaster .
sudo chmod -R g+w .
chgrp -R upload logs upload 2>/dev/null
