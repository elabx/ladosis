# Install
Download the module from : https://gitlab.sekretservices.com/flydev-fr/Duplicator
or
Clone the repo : `git clone https://gitlab.sekretservices.com/flydev-fr/Duplicator.git`


## Install the SDKs
To install the SDKs with composer, edit the **composer.json** file in the root directory of ProcessWire and add to the **require** section :
* `"google/apiclient": "2.*"`
* `"dropbox/dropbox-sdk": "1.*"`
* `"aws/aws-sdk-php": "3.*"`

then execute the following command-line : `composer update` in the root directory of ProcessWire.


## Installing a CRON job
First of all, you must install PWCron, go to this repo and clone or download the fork: https://github.com/flydev-fr/PWCron


To edit a crontab through the command line, type: `cronjob -e` then add for example the following line:
> `*/1 * * * * /usr/bin/php /absolute/path/of/public/site/modules/PWCron/cron.php >/dev/null 2>&1`


If you are running CRON via a panel like cPanel, you must determine if your hosting provider configured PHP with internal extension SAPI or not.
To do that, create a new PHP file and write this line  :
> ```<?php echo php_sapi_name(); ?>```

or

> ```<?php echo PHP_SAPI; ?>```

the returned content from this script should be : 
* cgi
* cgi-fcgi
* cli

*For more informations and values, they can be found in `apache/mod_php5.c`.*

Now for example, if the result is `cli`, then in cPanel you should try to run the CRON job with this command :
> ```php-cli /path/to/script/cron.php```

or (depending on the hosting provider)

> ```php5-cli /path/to/script/cron.php```

### Some hosting companies don’t allow access to cron
If this the case, you have to rely on LazyCron module.




### Example CRON delay table:

| When | Settings |
| -------- | -------- |
| Every 1 minute   | */1 * * * *   |
| Every 15 minutes   | */15 * * * *   |
| Every 30 minutes |	*/30 * * * *
| Every 1 hour |	0 * * * * |
| Every 6 hours |	0 */6 * * * |
| Every 12 hours |	0 */12 * * * |
| Once a day |	4 0 * * * |
| Once a week |	4 0 * * 0 |
| Once a month |	4 0 1 * * |


## Note about Amazon AWS
A **bucket name** should conform with **DNS requirements**:
* Should not contain uppercase characters
* Should not contain underscores (_)
* Should be between 3 and 63 characters long
* Should not end with a dash
* Cannot contain two, adjacent periods
* Cannot contain dashes next to periods (e.g., "my-.bucket.com" and "my.-bucket" are invalid)


