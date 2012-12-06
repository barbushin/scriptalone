ScriptAlone is PHP class for limiting running only one instance of some script

Project site:
https://github.com/barbushin/scriptalone
http://code.google.com/p/scriptalone

GIT:
https://github.com/barbushin/scriptalone.git

SVN:
http://scriptalone.googlecode.com/svn/trunk

Example task: you have a live queue of emails(for example in DB) that must be sent as soon as possible.

  # You need to have some PHP-script that will check if there are any emails in queue, and if yes so send them.
  # This PHP-script must be runned all time, and check queue every second.
  # Only one instance of this PHP-script can be runned, to be sure that one email was not sent twice.
  # PHP-script can be interrupted in any moment by some error, so in this case it must be restarted as soon as possible.
  # PHP-script can have a bug on some iteration, mean: it will be lunched but not sending emails, so in this case it must be restarted as soon as possible.
  # PHP-script should be restarted every 5-10 hours to prevent memory leaks and do it in safe way (when all current tasks are complete).
  # Ability to stop script in any moment.

What you do:
  # Use ScriptAlone in way like in http://code.google.com/p/scriptalone/source/browse/trunk/example.php
  # Configure CRON that will run this PHP-script every 5 minutes.

What you have:
  # Script will be runned all time, and checked emails every second.
  # There will be only one instance of runned script.
  # Script will be restarted if there will be any errors or iteration time limit expire.
  # Script will be restarted in safe way every 5 hours.
  # You can any time check if script is runned by checking existing file with $stateFilepath path (./examples.php.works)
  # You can any time stop runned script by creating file with path $stateFilepath.'.stop'  (./examples.php.works.stop).
  # All script restarts takes maximum 5 minutes (because of CRON try to run next script instance every 5 minute).

Recommended:
 * Google Chrome extension PHP Console - http://goo.gl/b10YF
 * Google Chrome extension JavaScript Errors Notifier - http://goo.gl/kNix9