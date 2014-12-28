<?php


$config = parse_ini_file('config.ini', true);


if ($config === false || !is_array($config)) {
	die("Invalid config file - be sure that you have a 'config.ini' present (not 'config.ini.example') and that any non-alphanumeric values are wrapped in double quotes");
}

try {
	$payloadBody = json_decode(file_get_contents('php://input'), true);
	if (! $payloadBody ) {
		throw new Exception("No payload body detected");
	}
} catch ( Exception $e ) {
	die("Could not decode the payload body...something seems fishy");
}

// Make sure that they have changed from the default secret key
if (strcmp(getConfig('SECRET_PHRASE'), 'CHANGETHISKEY') === 0) {
	die('Change the default secret phrase - this is a security precaution.');
}

foreach($config as $configName => $configArr) {
	if (strcmp($configName, 'CONFIG') === 0) {
		// Don't process the config settings
		continue;
	}

	if (configMatches($configArr, $payloadBody)) {
		echo "We have a match..." . $configName;
		processConfig($configArr);
	}
}

/**
 * Returns true if a given "sink" configuration matches a GitHub payload
 */
function configMatches($cfg, $payload) {
	try {
		return strcasecmp($cfg['GITHUB_ACCOUNT'], $payload['repository']['owner']['login']) === 0 &&
			strcasecmp($cfg['GITHUB_REPO'], $payload['repository']['name']) === 0 &&
			strcasecmp('refs/heads/' . $cfg['GITHUB_BRANCH'], $payload['ref']) === 0;

	} catch ( Exception $e ) {
		return false;
	}
}

/**
 *
 */
function processConfig($cfg) {

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
?>
