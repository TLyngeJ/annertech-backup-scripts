#!/bin/bash

# locate where the this script is located
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
cd $SCRIPT_DIR

# disable out, parse to logfile instead
exec 1>backup.log 2>&1

# make sure all temp files a gone
rm -f *.txt

# update #AC CLI tool
aberdeen self-update

# update the #AC aliases
aberdeen drush-aliases > ~/.drush/aberdeen.aliases.drushrc.php

# list all available projects
aberdeen project-list > project_list.txt

# the first two lines in all_projects.txt is not needed. Delete them
tail -n+3 project_list.txt > all_projects.txt

# find the projects that has a live site available
while read AC_SITES; do
  SITE_ID="$(LINE=($AC_SITES);echo ${LINE[0]})";
  PROJECT_INFO=$(aberdeen project-info --project=$SITE_ID)
  if [[ $PROJECT_INFO == *"tier : basic"* ]]; then
    echo "FOUND"
  else
    echo "NOT FOUND"
  fi
done < all_projects.txt

