<?php

$config = parse_ini_file('config.ini', true);

if ($config === false || !is_array($config)) {
	die500("Invalid config file - be sure that you have a 'config.ini' present (not 'config.ini.example') and that any non-alphanumeric values are wrapped in double quotes");
}

try {
	// Keep the raw payload so we can hash it and check the secret key
	$payload = file_get_contents('php://input');

	// But really, we're going to need a JSON decoded body to access data
	$payloadBody = json_decode($payload, true);

	if (! $payloadBody ) {
		throw new Exception("No payload body detected");
	}
} catch ( Exception $e ) {
	die500("Could not decode the payload body...something seems fishy");
}

// Make sure that they have changed from the default secret key
if (strcmp(getConfig('SECRET_PHRASE'), 'CHANGETHISKEY') === 0) {
	die500('Change the default secret phrase - this is a security precaution.');
}

// We require a GitHub signature header to validate against the secret key
if( !array_key_exists( 'HTTP_X_HUB_SIGNATURE', $_SERVER ) ) {
	die500( 'Missing X-Hub-Signature header.' );
}
if ( ! checkSecretKey($payload) ) {
	die500( 'Secret key does not match' );
}

// Go through every sink we have configured, see if it's a match
foreach($config as $configName => $configArr) {
	
	// Don't process the config settings
	if (strcmp($configName, 'CONFIG') === 0) {
		continue;
	}

	if (configMatches($configArr, $payloadBody)) {
		try {
			processConfig($configArr);
		} catch ( Exception $e ) {
			die500("Matched a sink, but could not process:\n" . $e->getMessage());
		}
	}
}

/**
 * Returns true if a given "sink" configuration matches a GitHub payload
 */
function configMatches($cfg, $payload) {
	try {
		return !(strcasecmp($cfg['GITHUB_ACCOUNT'], $payload['repository']['owner']['name']) ||
			strcasecmp($cfg['GITHUB_REPO'], $payload['repository']['name']) ||
			strcasecmp('refs/heads/' . $cfg['GITHUB_BRANCH'], $payload['ref']));

	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Process a sink config for syncing.
 *
 * Raise an exception if anything goes wrong.
 *
 */
function processConfig($cfg) {
	$curDir = getcwd();
	$targetDir = getFromArray('DIRECTORY', $cfg, '.');

	if (is_dir($targetDir)) {
		chdir($targetDir);
	} else {
		throw new Exception($targetDir . " is not a valid directory");
	}

	echo syncRepository($cfg);
}

/**
 * Perform the necessary commands to sync the folder to the repository.
 *
 * It can be assumed that when this function is called, the working directory
 * is set to the target directory specified in the sink config.
 *
 * Returns a string containing the output of the command
 *
 */
function syncRepository($cfg) {
	$statusCode = -1;
	$output = array();

	// Let's pull out some configured parameters, maybe use some defaults
	$remoteName = getFromArray('REMOTE_NAME', $cfg, 'origin');
	$branchName = getFromArray('GITHUB_BRANCH', $cfg, 'master');
	$resetMode = getFromArray('RESET_MODE', $cfg, false);

	// These will be the bash commands we execute
	$cmds = array();
   
	// Perform a git checkout of the branch we are working with
	$cmds[]	= "git checkout $branchName";

	// If they want to reset, do so before pulling
	if ($resetMode) {
		$cmds[]	= "git reset --$resetMode HEAD";
	}

	// The big pull - grab using the remote and branch
	$cmds[]	= "git pull $remoteName $branchName";

	foreach ($cmds as $cmd) {
		echo "Executing: $cmd\n";
		exec(escapeshellcmd($cmd) . " 2>&1", $output, $statusCode);

		if ($statusCode !== 0) {
			throw new Exception(implode("\n", $output));
		}
	}

	return implode("\n", $output);
}

/**
 * Check the SHA header against the configured secret phrase.
 *
 * Assume that the secret phrase has been changed and that the GitHub header is present.
 *
 */
function checkSecretKey($payload) {
	$shaHash = 'sha1=' . hash_hmac( 'sha1', $payload, getConfig('SECRET_PHRASE'), false );
	return strcmp($shaHash, $_SERVER['HTTP_X_HUB_SIGNATURE']) === 0;
}

/**
 * Get a configured value from the master config section
 */
function getConfig($configKey, $default='') {
	global $config;
	if (array_key_exists('CONFIG', $config) && is_array($config['CONFIG'])) {
		return getFromArray($configKey, $config['CONFIG'], $default);
	}

	return $default;
}

/**
 * Get a value from an array, if it exists, otherwise a default
 */
function getFromArray($key, $arr, $default) {
	if (array_key_exists($key, $arr)) {
		return $arr[$key];
	}
	return $default;
}

/**
 * Kill execution while also setting an HTTP status code of 500 - so GitHub knows it failed
 */
function die500($msg) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	die($msg);
}
?>
