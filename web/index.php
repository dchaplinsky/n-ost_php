<?php
    require_once __DIR__.'/../vendor/autoload.php';
    require_once __DIR__.'/../settings.php';
    require_once __DIR__.'/../models.php';
    use Symfony\Component\HttpFoundation\Request;


    $app = new Silex\Application();
    $app['debug'] = DEBUG;
    
    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/templates',
    ));

    $app->extend('twig', function($twig, $app) {
        $function = new Twig_SimpleFunction('url', function ($scope, $filename) {
            return "static/".$filename;
        });
        $twig->addFunction($function);

        return $twig;
    });

    if (DEBUG) {
        $filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
        if (php_sapi_name() === 'cli-server' && (0 === strpos($_SERVER['REQUEST_URI'], STATIC_FOLDER)) && is_file($filename)) {
            return false;
        }
    }

    $app->get('/', function (Silex\Application $app, Request $request) {
        $lang = $request->query->get("lang", "en");
        $model = get_model($lang);

        return $app['twig']->render('base.html', array(
            "registries"=>$model,
            "lang"=>$lang,  # TODO: Sanitize
        ));
    });

    $app->get('/wl', function (Silex\Application $app, Request $request) {
        $lang = $request->query->get("lang", "en");
        $model = get_model($lang);

        return $app['twig']->render('table.html', array(
            "registries"=>$model,
            "lang"=>$lang,  # TODO: Sanitize
        ));
    });

    $app->get('/api', function (Silex\Application $app, Request $request) {
        return $app->json(get_model($request->query->get("lang", "en")));
    });

    $app->run();