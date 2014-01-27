<?php
/**
 * @file
 * Defines a number of REST API routes for user object management. All
 * definitions are in this single file for simplicity.
 */

require '../vendor/autoload.php';
require '../settings.php';

// Init data store ('~/' should always be writable).
if (!is_dir($conf['datastore_path'])) {
    // Group writable is safer than world.
    mkdir($conf['datastore_path'], 0775);
}
// Data is stored as JSON by default.
$config = new \JamesMoss\Flywheel\Config($conf['datastore_path']);
$repo = new \JamesMoss\Flywheel\Repository('users', $config);

// Init framework.
$app = new \Slim\Slim();
$route_prefix = '/api';

// Declare our authentication middleware.
// This will intercept all requests. If neither a session header nor BASIC auth
// details are sent by the client, it throws a 401.
$app->add(new \ChinthakaGodawita\BasicAuthMiddleware($conf['auth_user'], $conf['auth_pass'], $conf['auth_timeout']));

/**
 * (GET) Authenticates the current client. This returns a session id that should
 * be provided (as part of the 'X-Session-Id' header) with all proceeding
 * requests.
 */
$app->get($route_prefix . '/auth', function () use ($app) {
    // Indicate response content type.
    $app->response()->header("Content-Type", "application/json");

    // Indicate success (authentication handled by middleware).
    echo json_encode(array(
        'status' => 200,
        'message' => 'Successfully authenticated.',
    ));
});

/**
 * (GET) Returns all users defined in the datastore.
 */
$app->get($route_prefix . '/users', function () use ($app, $repo) {
    // Indicate response content type.
    $app->response()->header("Content-Type", "application/json");

    // Load all from repo and return.
    $users = $repo->findAll();
    echo json_encode($users);
});

/**
 * (GET) Returns the user specified by :userId or 404 otherwise.
 */
$app->get($route_prefix . '/users/:userId', function ($user_id) use ($app, $repo) {
    // Indicate response content type.
    $app->response()->header("Content-Type", "application/json");

    // Attempt to load user from datastore.
    $user = $repo->query()->where('id', '==', $user_id)->execute();

    if ($user->count() == 0) {
        // Halt with message.
        // print_r($user);
        $app->halt(404, json_encode(array(
            'status' => 404,
            'message' => "User $user_id does not exist.",
        )));
    }
    else {
        // Print out this user.
        echo json_encode($user->first());
    }
});

/**
 * (PUT) Creates (or updates if it exists already) the user specified by
 * :userId.
 */
$app->put($route_prefix . '/users/:userId', function ($user_id) use ($app, $repo) {
    // Indicate response content type.
    $app->response()->header("Content-Type", "application/json");

    $raw_user = $app->request()->put();

    // Check if the requested user id exists first.
    $existing_user = $repo->query()->where('id', '==', $user_id)->execute();
    if ($existing_user->count() == 0) {
        // User doesn't exist, create.
        $user = new \JamesMoss\Flywheel\Document($raw_user);
        $user->id = $user_id;
        $repo->store($user);

        // Return saved user.
        echo json_encode($user);
    }
    else {
        // Merge existing and new user data together.
        $user = $raw_user + (array)$existing_user->first();
        $user = new \JamesMoss\Flywheel\Document($user);
        $repo->store($user);

        // Return saved user.
        echo json_encode($user);
    }
});

/**
 * (POST) Creates a user, returns 400 if a user id was provided and that user
 * already exists.
 */
$app->post($route_prefix . '/users', function () use ($app, $repo) {
    // Indicate response content type.
    $app->response()->header("Content-Type", "application/json");

    // Parse new user.
    $raw_user = $app->request->post();
    $user = new \JamesMoss\Flywheel\Document($raw_user);

    // Check if user exists (error out if so).
    if (isset($user->id)) {
        $existing_user = $repo->query()->where('id', '==', $user->id)->execute();
        if ($existing_user->count() == 0) {
            $app->halt(400, json_encode(array(
                'status' => 400,
                'message' => "User with id '{$user->id}' already exists.",
            )));
        }
    }

    // Save user and return new user object.
    $repo->store($user);
    echo json_encode($user);
});

// Run!
$app->run();
