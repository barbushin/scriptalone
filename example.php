<?php

/**
 * 
 * @desc This is example of using class ScriptAlone. 
 *
 * Example task: you have a live queue of emails(for example in DB) that must
 *               be sent as soon as possible.
 *
 * 1. You need to have some PHP-script that will check if there are any emails 
 *    in queue, and if yes so send them.
 * 2. This PHP-script must be runned all time, and check queue every second.
 * 3. Only one instance of this PHP-script can be runned, to be shure that one
 *    email was not sent twice.
 * 4. PHP-script can be interrupted in any moment by some error, so in this 
 *    case it must be restarted as soon as possible.
 * 5. PHP-script can have a bug on some iteration, mean: it will be lunched
 *    but not sending emails, so in this case it must be restarted as soon 
 *    as possible.
 * 6. PHP-script should be restarted every 5-10 hours to prevent memory leaks
 *    and do it in safe way (when all current tasks are complete)
 * 7. Ability to stop/restart script in any moment.
 *
 * What you do:
 * 1. Use ScriptAlone in way like in this PHP-script.
 * 2. Configure CRON(http://wikipedia.org/wiki/Cron) that will lunch this 
 *    PHP-script every 5 minutes.
 *
 * What you have:
 * 1. Script will be runned all time, and checking emails every second.
 * 2. There will be only one instance of runned script.
 * 3. Script will be restarted if there will be any errors or iteration time
 *    limit expires.
 * 3. Script will be restarted in safe way every 5 hours.
 * 4. You can any time check if script is runned by checking existing file
 *    with $stateFilepath path (./examples.php.works)
 * 5. You can any time stop/restart runned script by creating file with path
 *    $stateFilepath.'.stop' or $stateFilepath.'.restart'
 * 6. All script restarts takes maximum 5 minutes (because of CRON try to run
 *    next script instance every 5 minute).
 * 
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 * 
 */


// example function that sends emails from queue 
function sendEmailsFromQueue($limit) {
	// ... send some emails from queue
	return mt_rand(0, 3); // return count of sent emails
}

function _debug($message) {
	echo $message.'<br />';
	flush();
}

define('PAUSE_SECONDS_ON_EMPTY_QUEUE', 1);
define('EMAILS_GET_FROM_QUEUE', 5);
define('MAX_SECONDS_TO_SEND_ONE_EMAIL', 3);
$withoutNotifyLifetime =  EMAILS_GET_FROM_QUEUE * MAX_SECONDS_TO_SEND_ONE_EMAIL + PAUSE_SECONDS_ON_EMPTY_QUEUE;

$stateFilepath = __FILE__.'.works';
$scriptLifetime = 60*60*5;

// if FALSE so you should check $scriptAlone->isReadyToStop() every time
// if TRUE so checking of $scriptAlone->isReadyToStop() will be doing automaticly in every calling of $scriptAlone->notifyItWorks()
//         and if $scriptAlone->isReadyToStop() == true, so $scriptAlone->notifyItWorks() will throw exception of class ScriptAlone_Stopped
$stopOnReadyToStop = false; 

require_once('ScriptAlone.php');
$scriptAlone = new ScriptAlone($stateFilepath, $withoutNotifyLifetime, $scriptLifetime, $stopOnReadyToStop);

_debug('script is runned, you can see it by created file'.$stateFilepath);

while (!$scriptAlone->isReadyToStop()) {
	_debug('sendEmailsFromQueue');
	if (!sendEmailsFromQueue(EMAILS_GET_FROM_QUEUE)) {
		_debug('sleep');
		sleep(PAUSE_SECONDS_ON_EMPTY_QUEUE);
	}
	_debug('notifyItWorks');
	$scriptAlone->notifyItWorks();
}

_debug('done');