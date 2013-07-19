<?php namespace Arx\classes;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Exception\ExceptionServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\FatalErrorException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * App
 *
 * @category Core
 * @package  Arx
 * @author   Daniel Sum <daniel@cherrypulp.com>
 * @author   Stéphan Zych <stephan@cherrypulp.com>
 * @license  http://opensource.org/licenses/MIT MIT License
 * @link     http://arx.xxx/doc/App
 */
class App extends Container
{

    // --- Constants

    const VERSION = '1.0';

    const CODENAME = 'Lupa';

    // --- Protected value

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The array of booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = array();

    /**
     * The array of booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = array();

    /**
     * The array of shutdown callbacks.
     *
     * @var array
     */
    protected $shutdownCallbacks = array();

    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = array();

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = array();

    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected $deferredServices = array();

    // --- Constructor

    /**
     * Magic constructor by default load default config.
     *
     * @param mixed $mConfig String Path, array of paths or Request object
     */
    public function __construct()
    {
        $iArgs = func_num_args();
        $aArgs = func_get_args();

        $config = __DIR__ . '/../config/';

        if ($iArgs == 1) {
            if (is_array($aArgs[0]) || is_string($aArgs[0])) {
                $config = $aArgs[0];
            } elseif (is_object($aArgs[0])) {
                $request = $aArgs[0];
            }
        } elseif($iArgs == 2){
            if (is_array($aArgs[0]) || is_string($aArgs[0])) {
                $config = $aArgs[0];
            }
            if (is_object($aArgs[1])) {
                $request = $aArgs[1];
            }
        } else {
            $config = __DIR__ . '/../config/';
            $request = null;
        }

        $this['request'] = $this->createRequest($request);

        // The exception handler class takes care of determining which of the bound
        // exception handler Closures should be called for a given exception and
        // gets the response from them. We'll bind it here to allow overrides.
        $this->register(new ExceptionServiceProvider($this));

        $this->register(new RoutingServiceProvider($this));

        $this->register(new EventServiceProvider($this));

        $this['env'] = "production";

        $this['config'] = Config::getInstance();

        $this['config']->load($config, 'defaults');

        // Settings Aliases
        $aliases = $this['config']->get('aliases');

        foreach ( (array) $aliases['classes'] as $aliasName => $class) {
            if ( !class_exists($aliasName) ) {
                class_alias($class, $aliasName);
            } else {
                Debug::notice('A class already exist');
            }
        }

        foreach ( (array) $aliases['functions'] as $aliasName => $callback) {
            if (!function_exists($aliasName)) {
                Utils::alias($aliasName, $callback);
            }
        }

        // Settings System
        $system = $this['config']->get('system');

        foreach ( (array) $system as $accessor => $class) {
            if(is_array($class)){
                $oClass = new \ReflectionClass($class[0]);

                $this[$accessor] = $oClass->newInstance($class[1]);

            } else {
                $this[$accessor] = new $class();
            }
        }

    } // __construct


    // --- Magic methods

    public function __call($sName, $aArgs)
    {

        switch (true) {

            case method_exists($this['router'], $sName):
                return call_user_func_array(array($this['router'], $sName), $aArgs);
                break;

            default:
                if (class_exists('\\Arx\\' . $sName)) {

                    $object = new ReflectionClass('\\Arx\\' . $sName);

                    if (!empty($mArgs)) {
                        return $object->newInstanceArgs($mArgs);
                    }
                    return $object->newInstance();
                } else {
                    //trigger_error('class or method not exist');
                }
                break;
        }
    } // __call

    public static function __callStatic($sName, $aArgs)
    {
        self::resolving($sName, $aArgs);
    } // __callStatic

    /**
     * Dynamically access application services.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set application services.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }


#A
    static function autoload($className, $aParam = array())
    {

        $instance = self::getInstance();

        $aAutoload = Config::get('autoload');

        $className = ltrim($className, '\\');
        $fileName = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        $aNamespaces = Composer::getNamespaces();

        if (is_array($aAutoload) and array_key_exists($className, $aAutoload) and is_file($aAutoload[$className])) {
            include $aAutoload[$className];
        } else {

        }

        if ($className != 'u') {

            $aNamespaces = Composer::getNamespaces();

        }
    }

    #C
    public static function conf($name)
    {
        return self::getInstance()['config']->get($name);
    }

#L
    static function load()
    {

        $instance = self::getInstance();

        $className = ltrim($className, '\\');
        $fileName = '';
        $namespace = '';

        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        require $fileName;

    } // load

    /**
     * Create the request for the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Request
     */
    protected function createRequest(Request $request = null)
    {
        return $request ? : Request::createFromGlobals();
    }

    /**
     * Set the application request for the console environment.
     *
     * @return void
     */
    public function setRequestForConsoleEnvironment()
    {
        $url = $this['config']->get('app.url', 'http://localhost');

        $this->instance('request', Request::create($url, 'GET', array(), array(), array(), $_SERVER));
    }

    /**
     * Redirect the request if it has a trailing slash.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
     */
    public function redirectIfTrailingSlash()
    {
        if ($this->runningInConsole()) return;

        // Here we will check if the request path ends in a single trailing slash and
        // redirect it using a 301 response code if it does which avoids duplicate
        // content in this application while still providing a solid experience.
        $path = $this['request']->getPathInfo();

        if ($path != '/' and ends_with($path, '/') and !ends_with($path, '//')) {
            with(new SymfonyRedirect($this['request']->fullUrl(), 301))->send();

            exit;
        }
    }

    /**
     * Bind the installation paths to the application.
     *
     * @param  array $paths
     * @return void
     */
    public function bindInstallPaths(array $paths)
    {
        $this->instance('path', realpath($paths['app']));

        foreach (array_except($paths, array('app')) as $key => $value) {
            $this->instance("path.{$key}", realpath($value));
        }
    }

    /**
     * Get the application bootstrap file.
     *
     * @return string
     */
    public static function getBootstrapFile()
    {
        return __DIR__ . '/start.php';
    }

    /**
     * Start the exception handling for the request.
     *
     * @return void
     */
    public function startExceptionHandling()
    {
        $this['exception']->register($this->environment());

        $this['exception']->setDebug($this['config']['app.debug']);
    }

    /**
     * Get the current application environment.
     *
     * @return string
     */
    public function environment()
    {
        return $this['env'];
    }

    /**
     * Detect the application's current environment.
     *
     * @param  array|string $environments
     * @return string
     */
    public function detectEnvironment($environments)
    {
        $base = $this['request']->getHost();

        $arguments = $this['request']->server->get('argv');

        if ($this->runningInConsole()) {
            return $this->detectConsoleEnvironment($base, $environments, $arguments);
        }

        return $this->detectWebEnvironment($base, $environments);
    }

    /**
     * Set the application environment for a web request.
     *
     * @param  string $base
     * @param  array|string $environments
     * @return string
     */
    protected function detectWebEnvironment($base, $environments)
    {
        // If the given environment is just a Closure, we will defer the environment
        // detection to the Closure the developer has provided, which allows them
        // to totally control the web environment detection if they require to.
        if ($environments instanceof Closure) {
            return $this['env'] = call_user_func($environments);
        }

        foreach ($environments as $environment => $hosts) {
            // To determine the current environment, we'll simply iterate through the
            // possible environments and look for a host that matches this host in
            // the request's context, then return back that environment's names.
            foreach ((array)$hosts as $host) {
                if (str_is($host, $base) or $this->isMachine($host)) {
                    return $this['env'] = $environment;
                }
            }
        }

        return $this['env'] = 'production';
    }

    /**
     * Set the application environment from command-line arguments.
     *
     * @param  string $base
     * @param  mixed $environments
     * @param  array $arguments
     * @return string
     */
    protected function detectConsoleEnvironment($base, $environments, $arguments)
    {
        foreach ($arguments as $key => $value) {
            // For the console environment, we'll just look for an argument that starts
            // with "--env" then assume that it is setting the environment for every
            // operation being performed, and we'll use that environment's config.
            if (starts_with($value, '--env=')) {
                $segments = array_slice(explode('=', $value), 1);

                return $this['env'] = head($segments);
            }
        }

        return $this->detectWebEnvironment($base, $environments);
    }

    /**
     * Determine if the name matches the machine name.
     *
     * @param  string $name
     * @return bool
     */
    protected function isMachine($name)
    {
        return str_is($name, gethostname());
    }

    /**
     * Determine if we are running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Determine if we are running unit tests.
     *
     * @return bool
     */
    public function runningUnitTests()
    {
        return $this['env'] == 'testing';
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     * @param  array $options
     * @return void
     */
    public function register($provider, $options = array())
    {
        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }

        $provider->register();

        // Once we have registered the service we will iterate through the options
        // and set each of them on the application so they will be available on
        // the actual loading of the service objects and for developer usage.
        foreach ($options as $key => $value) {
            $this[$key] = $value;
        }

        $this->serviceProviders[] = $provider;

        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    protected function resolveProviderClass($provider)
    {
        return new $provider($this);
    }

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        // We will simply spin through each of the deferred providers and register each
        // one and boot them if the application has booted. This should make each of
        // the remaining services available to this application for immediate use.
        foreach (array_unique($this->deferredServices) as $provider) {
            $this->register($instance = new $provider($this));

            if ($this->booted) $instance->boot();
        }

        $this->deferredServices = array();
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param  string $service
     * @return void
     */
    protected function loadDeferredProvider($service)
    {
        $provider = $this->deferredServices[$service];

        // If the service provider has not already been loaded and registered we can
        // register it with the application and remove the service from this list
        // of deferred services, since it will already be loaded on subsequent.
        if (!isset($this->loadedProviders[$provider])) {
            $this->register($instance = new $provider($this));

            unset($this->deferredServices[$service]);

            $this->setupDeferredBoot($instance);
        }
    }

    /**
     * Handle the booting of a deferred service provider.
     *
     * @param  \Illuminate\Support\ServiceProvider $instance
     * @return void
     */
    protected function setupDeferredBoot($instance)
    {
        if ($this->booted) return $instance->boot();

        $this->booting(function () use ($instance) {
            $instance->boot();
        });
    }

    /**
     * Resolve the given type from the container.
     *
     * (Overriding Container::make)
     *
     * @param  string $abstract
     * @param  array $parameters
     * @return mixed
     */
    public function make($abstract, $parameters = array())
    {
        if (isset($this->deferredServices[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * Register a "before" application filter.
     *
     * @param  Closure|string $callback
     * @return void
     */
    public function before($callback)
    {
        return $this['router']->before($callback);
    }

    /**
     * Register an "after" application filter.
     *
     * @param  Closure|string $callback
     * @return void
     */
    public function after($callback)
    {
        return $this['router']->after($callback);
    }

    /**
     * Register a "close" application filter.
     *
     * @param  Closure|string $callback
     * @return void
     */
    public function close($callback)
    {
        return $this['router']->close($callback);
    }

    /**
     * Register a "finish" application filter.
     *
     * @param  Closure|string $callback
     * @return void
     */
    public function finish($callback)
    {
        $this['router']->finish($callback);
    }

    /**
     * Register a "shutdown" callback.
     *
     * @param  callable $callback
     * @return void
     */
    public function shutdown($callback = null)
    {
        if (is_null($callback)) {
            $this->fireAppCallbacks($this->shutdownCallbacks);
        } else {
            $this->shutdownCallbacks[] = $callback;
        }
    }

    /**
     * Handles the given request and delivers the response.
     *
     * @return void
     */
    public function run()
    {
        $aArgs = func_get_args();
        $iArgs = func_num_args();

        if (isset($aArgs[0])) {
            $this['request'] = $aArgs[0];
        }

        $response = $this->dispatch($this['request']);

        $this['router']->callCloseFilter($this['request'], $response);

        $response->send();

        $this['router']->callFinishFilter($this['request'], $response);
    }

    /**
     * Handle the given request and get the response.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatch(Request $request)
    {
        /*        if ($this->isDownForMaintenance())
                {
                    $response = $this['events']->until('illuminate.app.down');

                    return $this->prepareResponse($response, $request);
                }
                else
                {
                    return $this['router']->dispatch($this->prepareRequest($request));
                }*/

        return $this['router']->dispatch($this->prepareRequest($request));
    }

    /**
     * Handle the given request and get the response.
     *
     * Provides compatibility with BrowserKit functional testing.
     *
     * @implements HttpKernelInterface::handle
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $type
     * @param  bool $catch
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $this->instance('request', $request);

        Facade::clearResolvedInstance('request');

        return $this->dispatch($request);
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) return;

        // To boot the application we will simply spin through each service provider
        // and call the boot method, which will give them a chance to override on
        // something that was registered by another provider when it registers.
        foreach ($this->serviceProviders as $provider) {
            $provider->boot();
        }

        $this->fireAppCallbacks($this->bootingCallbacks);

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Register a new boot listener.
     *
     * @param  mixed $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  mixed $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $this);
        }
    }

    /**
     * Prepare the request by injecting any services.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Request
     */
    public function prepareRequest(Request $request)
    {
        if (isset($this['session'])) {
            $request->setSessionStore($this['session']);
        }

        return $request;
    }

    /**
     * Prepare the given value as a Response object.
     *
     * @param  mixed $value
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function prepareResponse($value)
    {
        if (!$value instanceof SymfonyResponse) $value = new Response($value);

        return $value->prepare($this['request']);
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return file_exists($this->conf('app.down'));
    }

    /**
     * Register a maintenance mode event listener.
     *
     * @param  \Closure $callback
     * @return void
     */
    public function down(Closure $callback)
    {
        $this['events']->listen('illuminate.app.down', $callback);
    }

    /**
     * Throw an HttpException with the given data.
     *
     * @param  int $code
     * @param  string $message
     * @param  array $headers
     * @return void
     */
    public function abort($code, $message = '', array $headers = array())
    {
        if ($code == 404) {
            throw new NotFoundHttpException($message);
        } else {
            throw new HttpException($code, $message, null, $headers);
        }
    }

    /**
     * Register a 404 error handler.
     *
     * @param  Closure $callback
     * @return void
     */
    public function missing(Closure $callback)
    {
        $this->error(function (NotFoundHttpException $e) use ($callback) {
            return call_user_func($callback, $e);
        });
    }

    /**
     * Register an application error handler.
     *
     * @param  \Closure $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        $this['exception']->error($callback);
    }

    /**
     * Register an error handler at the bottom of the stack.
     *
     * @param  \Closure $callback
     * @return void
     */
    public function pushError(Closure $callback)
    {
        $this['exception']->pushError($callback);
    }

    /**
     * Register an error handler for fatal errors.
     *
     * @param  Closure $callback
     * @return void
     */
    public function fatal(Closure $callback)
    {
        $this->error(function (FatalErrorException $e) use ($callback) {
            return call_user_func($callback, $e);
        });
    }

    /**
     * Get the configuration loader instance.
     *
     * @return \Illuminate\Config\LoaderInterface
     */
    public function getConfigLoader()
    {
        return new FileLoader(new Filesystem, $this['path'] . '/config');
    }

    /**
     * Get the service provider repository instance.
     *
     * @return \Illuminate\Foundation\ProviderRepository
     */
    public function getProviderRepository()
    {
        $manifest = $this['config']['app.manifest'];

        return new ProviderRepository(new Filesystem, $manifest);
    }

    /**
     * Set the current application locale.
     *
     * @param  string $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this['config']->set('app.locale', $locale);

        $this['translator']->setLocale($locale);

        $this['events']->fire('locale.changed', array($locale));
    }

    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * Set the application's deferred services.
     *
     * @param  array $services
     * @return void
     */
    public function setDeferredServices(array $services)
    {
        $this->deferredServices = $services;
    }


} // class::App
