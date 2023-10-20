<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    private $templateEngine;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * Constructor.
     *
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();

        $loader = new FilesystemLoader(APPPATH . 'Views');

        if (ENVIRONMENT === 'production') {
            $this->templateEngine = new Environment($loader, [
                'debug' => false,
                'cache' => false,
            ]);
        } else {
            $this->templateEngine = new Environment($loader, [
                'debug' => true,
                'cache' => false,
            ]);
            $this->templateEngine->addExtension(new DebugExtension());
        }

        // Create a new function for date.
        $this->templateEngine->addFunction(new TwigFunction('date', function ($format) {
            return date($format);
        }));

        // Create a new function for base_url.
        $this->templateEngine->addFunction(new TwigFunction('base_url', function ($uri) {
            return base_url($uri);
        }));

        // Create a new function for route_to.
        $this->templateEngine->addFunction(new TwigFunction('route_to', function ($uri) {
            return route_to($uri);
        }));

        // Create a new function for get_segment.
        $this->templateEngine->addFunction(new TwigFunction('get_segment', function ($segment) {
            return service('uri')->setSilent()->getSegment($segment);
        }));

        // Create a new function for session.
        $this->templateEngine->addFunction(new TwigFunction('session', function ($key = null) {
            return session($key);
        }));

        // Create a new function for old.
        $this->templateEngine->addFunction(new TwigFunction('old', function ($key) {
            return old($key);
        }));

        // Create a new function for set_value.
        $this->templateEngine->addFunction(new TwigFunction('set_value', function ($field, $default) {
            return set_value($field, $default);
        }));

        // Create a new function for csrf_token.
        $this->templateEngine->addFunction(new TwigFunction('csrf_token', function () {
            return csrf_token();
        }));

        // Create a new function for csrf_hash.
        $this->templateEngine->addFunction(new TwigFunction('csrf_hash', function () {
            return csrf_hash();
        }));

        // Create a new function for csrf_field.
        $this->templateEngine->addFunction(new TwigFunction('csrf_field', function () {
            return csrf_field();
        }));
    }

    /**
     * This method render the template.
     *
     * @param string $filename - the filename of template.
     * @param array $params - the data with context of the template.
     * @return string
     */
    public function render(string $filename, array $params = []): string
    {
        try {
            // Render the template.
            return $this->templateEngine->render($filename, $params);
        } catch (LoaderError | SyntaxError | RuntimeError | \Throwable $e) {
            if ('production' === ENVIRONMENT) {
                // Save error in file log
                log_message('error', $e->getTraceAsString());

                throw PageNotFoundException::forPageNotFound();
            }

            // Show error in the current page
            return '<pre>' . $e->getTraceAsString() . '</pre>' . PHP_EOL . $e->getMessage();
        }
    }
}
