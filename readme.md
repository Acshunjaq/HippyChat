### What does it do?
This script uses the [v2 HipChat API](https://www.hipchat.com/docs/apiv2) to

1. Archive
2. Make private

...any rooms which have been inactive for over 60 days. That default value can be overridden via an ini file.

### Get up and running
* Copy `parameters.ini.dist` to `parameters.ini` and add your v2 HipChat API token
* Get composer: https://getcomposer.org/
* Run `php composer.phar install`
* Run `php src/archive.php`

### Now?
Add it to a cron somewhere and keep that room list neat!

### Flags
* Debug
    * `php src/archive.php debug` will show the uris formed and sent
* Dry-run    
    * `php src/archive.php dry-run` will not archive, and will automatically enable debug mode
        * __Important__: This is not a true 'dry-run' as it will still hit the API endpoints. However, it only hits 'read only' end-points and will not make any changes to HipChat 
