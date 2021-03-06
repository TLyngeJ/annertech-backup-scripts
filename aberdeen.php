#!/usr/bin/env php
<?php
/**
 * @file
 * This is the script that initiates a backup process.
 */

// Make sure composer and drush is available.
if (!file_exists('composer.phar')) {
  print ("Downloading composer and drush\n");
  shell_exec("php -r \"readfile('https://getcomposer.org/installer');\" | php");
  shell_exec("./composer.phar install");
}

// Verify that we have access to an updated version of the aberdeen python
// script.
if (file_exists('aberdeen')) {
  print ("Updating aberdeen CLI\n");
  fwrite(STDOUT, shell_exec("./aberdeen self-update"));
}
else {
  print ("Downloading aberdeen CLI\n");
  shell_exec("curl -O https://aberdeen.s3.amazonaws.com/toolkit/latest/aberdeen && chmod +x aberdeen");
}

// Update aliases.
print ("Updating drush aliases:\n");
shell_exec("./aberdeen drush-aliases > aberdeen.aliases.drushrc.php");

// Make sure backup directories exists.
if (!file_exists('sql_dump')) {
  mkdir('sql_dump');
}
if (!file_exists('file_dump')) {
  mkdir('file_dump');
}

// Load the newly fetched aliases.
include_once "aberdeen.aliases.drushrc.php";

// Traverse all the aliases, but only work on aliases that has a live
// environment. There is no need to act on developer projects.
foreach ($aliases as $alias_name => $alias) {
  if (substr($alias_name, -3, 3) == 'liv') {
    // Get the project name by exploding the alias_name into an array, remove
    // the last index, and implode the array again, using _ as glue.
    $alias_name_array = explode('_', $alias_name);
    array_pop($alias_name_array);
    $project_name = implode('_', $alias_name_array);
    $remote_liv_user = $aliases[$project_name . '_liv']['remote-user'];
    $remote_liv_host = $aliases[$project_name . '_liv']['remote-host'];
    print ("Processing $project_name\n");
    // Download the database to this filesystem.
    print ("Downloading database\n");
    shell_exec("vendor/bin/drush @$alias_name sql-dump --gzip > sql_dump/$project_name.sql.gz");
    // By making sure that the DB dump has a certain size we assume that the
    // export went well.
    if (filesize("sql_dump/$project_name.sql.gz") > 5000) {
      // Rename the DB dump.
      rename("sql_dump/$project_name.sql.gz", "sql_dump/$project_name" . "_" . date("Y-m-d") . ".sql.gz");
      // And delete the one from 4 days ago.
      unlink("sql_dump/$project_name" . "_" . date("Y-m-d", strtotime("-4 day")) . ".sql.gz");
    }
    // Sync files to local folder.
    print ("Downloading files folder\n");
    shell_exec("rsync -rz --size-only --delete $remote_liv_user@$remote_liv_host:/srv/drupal/sites/default/files/ file_dump/$project_name");
    print ("Project backup complete\n\n");
  }
}
