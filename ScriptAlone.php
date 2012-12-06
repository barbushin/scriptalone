<?php

/**
 * 
 * @desc Class for limiting running only one instance of some script
 * @see https://github.com/barbushin/scriptalone
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 * 
 */
class ScriptAlone {

	protected $processUid;
	protected $startedTime;
	protected $workedTime;
	protected $expireTime;
	protected $withoutNotifyLifetime;
	protected $scriptLifetime;
	protected $stateFilepath;
	protected $stopOnReadyToStop;
	protected $scriptWasStopped;
	protected $scriptWasRestarted;
	
	public function __construct($stateFilepath, $withoutNotifyLifetime = 60, $scriptLifetime = null, $stopOnReadyToStop = false) {
		
		if(!$withoutNotifyLifetime) {
			$withoutNotifyLifetime = 60*60*24*365*100; // 100 years
		}
		
		if($scriptLifetime < $withoutNotifyLifetime) {
			throw new Exception('Value of argument "scriptLifetime" cannot be less than value of "withoutNotifyLifetime"');
		}
		
		$this->stateFilepath = $stateFilepath;
		$this->withoutNotifyLifetime = $withoutNotifyLifetime;
		$this->scriptLifetime = $scriptLifetime;
		$this->stopOnReadyToStop = $stopOnReadyToStop;
		$this->processUid = time() . mt_rand();
		$this->startedTime = time();
		
		if ($this->isAnotherNotExpiredScriptInstance()) {
			$this->stop('Another script instance still not expired');
		}
		
		$this->notifyItWorks(false);
	}

	/**************************************************************
	 STATE STORAGE
	 **************************************************************/
	
	protected function stateVarsToString(array $vars) {
		$string = '';
		foreach ($vars as $title => $value) {
			$string .= "$title: $value\n";
		}
		return rtrim($string, "\n");
	}

	protected function stateStringToVars($string) {
		$vars = array();
		if (preg_match_all('/([\w]+?)\:\s*(.+)/', $string, $m)) {
			foreach ($m[1] as $i => $title) {
				$vars[$title] = $m[2][$i];
			}
		}
		return $vars;
	}

	protected function saveState() {
		$stateString = $this->stateVarsToString(array(
			'PID' => $this->processUid, 
			'Started' => date('Y-m-d H:i:s', $this->startedTime), 
			'Worked' => date('Y-m-d H:i:s', $this->workedTime), 
			'Expire' => date('Y-m-d H:i:s', $this->expireTime)));		
		
		if (!file_put_contents($this->stateFilepath, $stateString)) {
			throw new Exception('Saving state was failed, cannot write file '.$this->stateFilepath);
		}
	}

	protected function readState(&$processUid, &$startedTime, &$workedTime, &$expireTime) {
		clearstatcache();
		if (is_file($this->stateFilepath)) {
			$state = $this->stateStringToVars(file_get_contents($this->stateFilepath));
			$processUid = isset($state['PID']) ? $state['PID'] : null;
			$startedTime = isset($state['Started']) ? strtotime($state['Started']) : null;
			$workedTime = isset($state['Worked']) ? strtotime($state['Worked']) : null;
			$expireTime = isset($state['Expire']) ? strtotime($state['Expire']) : null;
			return $state;
		}
		return false;
	}

	/**************************************************************
	 STATES
	 **************************************************************/
	
	public function isAnotherNotExpiredScriptInstance() {
		if ($state = $this->readState($processUid, $startedTime, $workedTime, $expireTime)) {
			return $processUid != $this->processUid && $expireTime > time();
		}
		return false;
	}

	public function isAnotherScriptInstanceStarted() {
		if ($this->readState($processUid, $startedTime, $workedTime, $expireTime)) {
			return $processUid != $this->processUid;
		}
		return false;
	}
	
	public function isReadyToStop() {
		return $this->scriptLifetime && ($this->startedTime + $this->scriptLifetime - time()) < $this->withoutNotifyLifetime;
	}

	public function isScriptStopped() {
		return is_file($this->stateFilepath . '.stop');
	}
	
	public function isScriptRestart() {
		return is_file($this->stateFilepath . '.restart');
	}

	/**************************************************************
	 ACTIONS
	 **************************************************************/
	
	public function notifyItWorks($checkAnotherScriptInstanceStarted = true) {
		$this->workedTime = time();
		$this->expireTime = $this->workedTime + $this->withoutNotifyLifetime;
		
		if ($this->isScriptStopped()) {
			$this->unlinkStateFilepath();
			$this->stop('Script is stoped by stop-file');
		}
		if($this->isScriptRestart()) {
			$this->restart();
		}
		elseif ($checkAnotherScriptInstanceStarted && $this->isAnotherScriptInstanceStarted()) {
			$this->stop('Another script instance was started');
		}
		elseif($this->isReadyToStop() && $this->stopOnReadyToStop) {
			$this->unlinkStateFilepath();
			$this->stop('Script lifetime is over');
		}
		
		set_time_limit($this->withoutNotifyLifetime);
		
		$this->saveState();
	}
	
	protected function stop($message) {
		$this->scriptWasStopped = true;
		throw new ScriptAlone_Stopped($message);
	}
	
	protected function restart() {
		unlink($this->stateFilepath . '.restart');
		$this->scriptWasRestarted = true;
		$this->stop('Script need to be restarted');
	}
	
	protected function unlinkStateFilepath() {
		if (is_file($this->stateFilepath)) {
			unlink($this->stateFilepath);
		}
	}

	public function __destruct() {
		if (!$this->scriptWasStopped || $this->scriptWasRestarted) {
			$this->unlinkStateFilepath();
		}
	}
}

class ScriptAlone_Stopped extends Exception {
}