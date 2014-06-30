### What does it do?
This script uses the v2 HipChat API to 1) archive and 2) make private any rooms which have been inactive for over 60 days. That default value can be overridden via an ini file.

### Get up and running
* Get composer: https://getcomposer.org/
* Copy `parameters.ini.dist` to `parameters.ini` and add your v2 HipChat API token
* Run `php src/archiver.php`

### Now?
Add it to a cron somewhere and keep that room list neat!