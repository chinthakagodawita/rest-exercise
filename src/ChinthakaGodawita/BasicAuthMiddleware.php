<?php

namespace ChinthakaGodawita;

/**
 * Custom middleware that executes on each request and verifies user
 * authentication. Inspired by Slim-Extras (which is now deprecated).
 *
 * @see https://github.com/codeguy/Slim-Extras
 */
class BasicAuthMiddleware extends \Slim\Middleware
{
    /**
     * @var string
     */
    protected $realm;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * Construct middleware.
     *
     * @param string $username
     *   Username to accept.
     * @param string $password
     *   Password to accept.
     * @param string $realm
     *   HTTP authentication realm.
     */
    public function __construct($username, $password, $timeout = 3600, $realm = 'Secure API') {
        $this->username = $username;
        $this->password = $password;
        $this->realm = $realm;
        $this->timeout = $timeout;
    }

    /**
    * {@inheritdoc}
    */
    public function call()
    {
        // Get reference to application
        $app = $this->app;

        $log = $app->log;

        // Get request and response headers.
        $req = $app->request();
        $res = $app->response();

        if ($this->authenticateUser($req)) {
            // Start session if required.
            if (session_id() == '') {
                session_start();
            }

            $_SESSION['user'] = true;
            $_SESSION['start'] = time();

            // Let the client know what the session id is so that they can
            // reuse it later.
            $res->header('X-Session-Id', session_id());

            // Let Slim handle the rest.
            $this->next->call();
        }
        else {
            // Attempt to re-authenticate.
            $res->status(401);
            $res->header('WWW-Authenticate', "Basic realm=\"{$this->realm}\"");
            $res->header("Content-Type", "application/json");
            $res->body(json_encode(array(
                'status' => 401,
                'message' => "Invalid username or password.",
            )));
        }
    }

    /**
     * Attempt to authenticate a request.
     *
     * @param \Slim\Http\Request $req
     *   The current request object.
     *
     * @return boolean
     *   Whether or not the user is authenticated.
     */
    private function authenticateUser($req) {
        // Check if a session was sent by the client first.
        $session_header = $req->headers('X-Session-Id');
        if ($session_header) {
            // Load their previous session.
            session_id($session_header);
            session_start();

            // Make sure the user was previously authenticated and that the
            // session has not expired.
            if (isset($_SESSION['user']) && $_SESSION['user'] && isset($_SESSION['start']) && ($_SESSION['start'] + $this->timeout) > time()) {
                return true;
            }
            else {
                // Reset authentication status.
                $_SESSION['user'] = false;
            }
        }

        // Now attempt to authenticate via HTTP Basic.
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            // Allows us to use HTTP Basic easily under FastCGI.
            // Thanks to http://stackoverflow.com/a/7792912/356237.
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        }
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            // This header is sometimes used under FPM.
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (!empty($auth_header)) {
            list($auth_user, $auth_pass) =  explode(':', base64_decode(substr($auth_header, 6)));
        }
        else {
            // Assume this is mod_php.
            $auth_user = $req->headers('PHP_AUTH_USER');
            $auth_pass = $req->headers('PHP_AUTH_PW');
        }

        // Attempt to authenticate.
        if ($auth_user && $auth_pass && $auth_user === $this->username && $auth_pass === $this->password) {
            return true;
        }

        // If we've gotten this far, authentication was not successful.
        return false;
    }
}
