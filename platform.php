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

// Make sure backup directories exists.
if (!file_exists('sql_dump')) {
  mkdir('sql_dump');
}
if (!file_exists('file_dump')) {
  mkdir('file_dump');
}

// Fetch all the available projects.
exec('./platform project:list --pipe', $project_ids);
$projects_total = count($project_ids);
echo $projects_total . " will be backed up\n\n";

foreach ($project_ids as $index => $project_id) {
  // Backup sites remotely.
  echo "Remote backup of project ID " . $project_id . " (site $index of $projects_total)\n";
  exec('./platform backup -q -e master -p ' . $project_id);
  echo "Local back up (DB)\n";
  exec('./platform drush -e master -p ' . $project_id . ' "sql-dump --gzip" > sql_dump/' . $project_id . '.sql.gz');
  if (filesize("sql_dump/$project_id.sql.gz") > 5000) {
    // Rename the DB dump.
    rename("sql_dump/$project_id.sql.gz", "sql_dump/$project_id" . "_" . date("Y-m-d") . ".sql.gz");
    // And delete the one from 4 days ago.
    unlink("sql_dump/$project_id" . "_" . date("Y-m-d", strtotime("-4 day")) . ".sql.gz");
  }
  echo "Local back up (files)\n";
  exec("rsync -rz --size-only --delete $project_id-master@ssh.eu.platform.sh:/app/public/sites/default/files/ file_dump/$project_id");
}
