<?php
define('IN_NEXT', 1);
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
require 'config.php';
$app = new \Slim\Slim($setting);

// Set config into app container
foreach ($config as $key => $val) {
    $app->config($key, $val);
}
// Autorun middleware or
if (isset($config['auto'])) {
    if (isset($config['auto']['helper'])) {
        foreach ($config['auto']['helper'] as $val) {
            $helper = '\\Next\Helper\\' . ucfirst($val);
            $app->container->singleton($val, function() use ($app, $helper) {
                return new $helper();
            });
        }
    }
    if (isset($config['auto']['middleware'])) {
        foreach ($config['auto']['middleware'] as $val) {
            $mid = '\\Next\Middleware\\' . ucfirst($val);
            $app->add(new $mid()); 
        }
    }
    if (isset($config['auto']['hook'])) {
        foreach ($config['auto']['hook'] as $val) {
            $name = 'Next' . DIRECTORY_SEPARATOR . 'hook' . DIRECTORY_SEPARATOR . strtolower($name) . '.php';
            require($name);
        }
    }
}

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */

// GET route
$route = function($app) {
    $url = trim(urldecode($app->request->getResourceUri()), " \t\n\r\0\x0B//");
    $uris = explode('/', $url);
    $dir = array_shift($uris);
    if ($dir == '~') {
        $name = array_shift($uris);
        $name = sprintf('app/control/%s.php', strtolower($name));
        if (file_exists($name)) {
            require($name);
        }

        return;
    }

    if ($dir == '') {
        // Site default
        $r = $app->config("route");
        if ($r["module"]) {
            $dir = $r["module"];
            $patten = '/';
        }
    }

    $dir = strtolower(preg_replace('/[^a-zA-Z]/', '', $dir));
    if (is_dir(sprintf('app/control/%s', $dir))) {
        if (!$patten) {
            $patten = sprintf('/%s/(:name+)', $dir);
        }

        // Set auth
        require('app/control/auth.php');
        if (isset($auth[$dir])) {
            $mw = $auth[$dir];
        } else {
            $mw = function() {};
        }

        $app->map($patten, $mw, function($name = array()) use($app, $dir) {
            $tmp = implode('/', $name);
            $pos = strpos($tmp, '?');
            if ($pos !== false) {
                $tmp = substr($tmp, 0, $pos);
            }
            $name = explode('/', $tmp);

            $control = (count($name) > 0 && $name[0])? $name[0]: 'home';
            $action = (count($name) > 1 && $name[1])? $name[1]: 'index';

            $control = ucfirst(strtolower($control));
            if (file_exists(sprintf('./app/control/%s/%s.php', $dir, $control))) {
                $class = sprintf('\\app\control\\%s\\%s', $dir, $control);;
                $obj = new $class($app);

                $action = str_replace('-', '', $action);
                if (!method_exists($obj, '__call') && !method_exists($obj, $action)) {
                    if ($app->config('debug')) {
                        throw new RuntimeException(sprintf('There is not %s method in app/control/%s/%s.php file.', $action, $dir, $control));
                    } else {
                        $app->notFound();
                    }
                }

                $obj->$action();
                return;
            }

            if ($app->config('debug')) {
                throw new RuntimeException(sprintf('There is not app/control/%s/%s.php file.', $dir, $control));
            } else {
                $app->notFound();
            }

        })->via('GET', 'POST');
    }
};
$route($app);

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
