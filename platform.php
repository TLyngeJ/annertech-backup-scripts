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

// Verify that we have access to an updated version of the platform script.
if (!file_exists('platform')) {
  shell_exec("curl -L -O https://github.com/platformsh/platformsh-cli/releases/download/v2.13.0/platform.phar && mv platform.phar platform && chmod +x platform");
}

print ("Updating Platform CLI\n");
fwrite(STDOUT, shell_exec("./platform --yes self-update"));

// Fetch all the available projects.
exec('./platform project:list --pipe', $project_ids);

// Backup sites.
foreach ($project_ids as $project_id) {
  echo 'Backing up project ID ' . $project_id . "\n";
  exec('./platform backup -e master -p ' . $project_id);
}
