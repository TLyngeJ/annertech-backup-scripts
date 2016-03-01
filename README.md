Backup scripts
==============

Remember, this script only have access to sites that the user, running the
script, has access to. So, if you run this as a backup user, that backup users
has to be created on the host too, and have a valid SSH key uploaded.

Run ```./aberdeen.php``` to start the backup process for Aberdeen Cloud sites.
This will download the files and DB to the machine where the script is executed.

Run ```./platform.php``` to start the backup process for Platform.sh sites. This
will backup the sites on Platform.sh' servers.

Run ```./update-checker.php``` to check all sites for security updates.
