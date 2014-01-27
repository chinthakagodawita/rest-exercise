<?php

/**
 * @file
 * Settings for the REST excercise app are defined here.
 * Copy this to 'settings.php' to use.
 */

$conf = array();

// User details.
$conf['auth_user'] = null;
$conf['auth_pass'] = null;

// Session timeout (in seconds).
$conf['auth_timeout'] = 3600;

$conf['datastore_path'] = $_SERVER['HOME'] . '/rest_exercise_data';
