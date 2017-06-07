<?php
    require_once __DIR__.'/../vendor/autoload.php';
    require_once __DIR__.'/../settings.php';
    require_once __DIR__.'/../models.php';
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;


    $app = new Silex\Application();
    $app['debug'] = DEBUG;
    
    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/templates',
    ));

    $app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
        'http_cache.cache_dir' => __DIR__.'/cache/',
    ));

    Request::setTrustedProxies(array('127.0.0.1'));

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

    function _get_lang(Request $request) {
        $lang = $request->query->get("lang", "en");
        if (!in_array($lang, array("en", "ru"))) {
            $lang = "en";
        }
        return $lang;        
    }

    function _render_response($template, Silex\Application $app, Request $request) {
        $lang = _get_lang($request);
        $model = get_model($lang);
        

        $content = $app['twig']->render($template, array(
            "registries"=>$model,
            "lang"=>$lang,
        ));

        $response = new Response($content);
        $response->setTtl(CACHE_TIME);
        $response->setClientTtl(CACHE_TIME);
        return $response;
    }

    $app->get('/', function (Silex\Application $app, Request $request) {
        return _render_response("base.html", $app, $request);
    });

    $app->get('/wl', function (Silex\Application $app, Request $request) {
        return _render_response("table.html", $app, $request);
    });

    $app->get('/api', function (Silex\Application $app, Request $request) {
        $lang = _get_lang($request);
        return $app->json(get_model($lang));
    });

    $app['http_cache']->run();
