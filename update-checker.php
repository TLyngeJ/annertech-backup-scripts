#!/usr/bin/env php
<?php
/**
 * @file
 * Checks for security updates on #AC sites.
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
print ("Updating drush aliases:\n\n");
shell_exec("./aberdeen drush-aliases > aberdeen.aliases.drushrc.php");

// Make sure backup directories exists.
if (!file_exists('sql_dump')) {
  mkdir('sql_dump');
}
if (!file_exists('file_dump')) {
  mkdir('file_dump');
}

include_once "aberdeen.aliases.drushrc.php";

foreach ($aliases as $alias_name => $alias) {
  if (substr($alias_name, -3, 3) == 'liv') {
    // Get the project name by exploding the alias_name into an array, remove
    // the last index, and implode the array again, using _ as glue.
    $alias_name_array = explode('_', $alias_name);
    array_pop($alias_name_array);
    $project_name = implode('_', $alias_name_array);
    print ("Processing $project_name\n");
    echo shell_exec("vendor/bin/drush @$alias_name ups --security-only | grep 'SECURITY UPDATE'");
    print ("Project security update check complete\n\n");
  }
}
