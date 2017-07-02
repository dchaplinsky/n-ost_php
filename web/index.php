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

    function _get_form_status(Request $request) {
        $form_status = $request->query->get("form_status", "");
        if (!in_array($form_status, array("success", "captcha_error"))) {
            $form_status = "";
        }
        return $form_status;
    }

    function _render_response($template, Silex\Application $app, Request $request) {
        $lang = _get_lang($request);
        $model = get_model($lang);
        $form_status = _get_form_status($request);
        

        $content = $app['twig']->render($template, array(
            "registries"=>$model,
            "lang"=>$lang,
            "form_status"=>$form_status,
        ));

        $response = new Response($content);
        $response->setTtl(CACHE_TIME);
        $response->setClientTtl(CACHE_TIME);
        return $response;
    }

    $app->get('/', function (Silex\Application $app, Request $request) {
        return _render_response("base.html", $app, $request);
    });

    $app->post('/need_help', function (Silex\Application $app, Request $request) {
        $recaptcha = new \ReCaptcha\ReCaptcha(RECAPTCHA_SECRET);

        $lang = _get_lang($request);
        $resp = $recaptcha->verify($request->get('g-recaptcha-response'), $_SERVER['REMOTE_ADDR']);
        if ($resp->isSuccess()) {
            mail(
                FEEDBACK_EMAIL,
                "Feedback email from database.n-vestigate.net",
                "You've received a new feedback from user " . $request->get('name') . " <" . $request->get('email') . "> \n" .
                "Country: " . $request->get("country") . " \n" .
                "Media outlet: " . $request->get("media_outlet") . " \n" .
                "Question: " . $request->get("question") . " \n" .
                "Text: " . $request->get("freetext") . " \n"
            );
            return $app->redirect("/?lang=$lang&form_status=success");
        } else {
            return $app->redirect("/?lang=$lang&form_status=captcha_error");
        }        
    });

    $app->get('/wl', function (Silex\Application $app, Request $request) {
        return _render_response("table.html", $app, $request);
    });

    $app->get('/api', function (Silex\Application $app, Request $request) {
        $lang = _get_lang($request);
        return $app->json(get_model($lang));
    });

    if (DEBUG) {
        $app->run();
    } else {
       $app['http_cache']->run(); 
    }
