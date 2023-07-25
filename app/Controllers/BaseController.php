<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Psr\Log\LoggerInterface;

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

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = ['response_template'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    protected $session;
    protected $viewData = [];
    protected array $supportedLocales = [];

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        if (session_status() == PHP_SESSION_NONE) {
            $this->session = Services::session();
        }

        date_default_timezone_set($_ENV["time.default_timezone"] ?? "UCT");
        $this->supportedLocales = $request->config->supportedLocales;
        $this->assignViewData([
            'locale' => $this->getLocale(),
            'supportedLocales' => $this->supportedLocales,
        ]);
    }
    public function assignViewData($data, $value = null)
    {
        if (is_array($data) && is_null($value)) {
            $this->viewData = array_merge($this->viewData, $data);
            return $this;
        }
        if (is_string($data)) {
            $this->viewData[$data] = $value;
            return $this;
        }
    }

    public function subView(string $view, array $data = [])
    {
        $this->assignViewData([
            "childView" => $view,
            "dataView" => $data
        ]);
        return $this;
    }
    /**
     * **Same as function view but always add $viewData to view
     */
    public function showView(string $view, $data = null, $option = [])
    {
        if (is_array($data)) {
            $this->assignViewData($data);
        }
        $this->assignViewData("locale", $this->getLocale());
        return view($view, $this->viewData);
    }

    public function getLocale()
    {
        $language = Services::language();
        $activeLocale = $language->getLocale();
        return $activeLocale;
    }

    function setLocale($lang)
    {
        Services::language($lang);
    }
}
