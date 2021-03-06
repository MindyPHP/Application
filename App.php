<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2017 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Application;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Mindy.
 */
final class App
{
    use LegacyMethodsTrait;

    /**
     * @var bool
     */
    protected $cacheClass;

    /**
     * @var App
     */
    private static $instance;

    /**
     * @var \Symfony\Component\HttpKernel\Kernel
     */
    protected $kernel;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * App constructor.
     *
     * @param string $className
     * @param string $environment
     * @param bool   $debug
     */
    private function __construct($className, $environment, $debug = false)
    {
        $this->debug = $debug;
        $this->kernel = new $className($environment, $debug);
    }

    /**
     * @param string $className
     * @param string $environment
     * @param bool   $debug
     *
     * @return App
     */
    public static function createInstance($className, $environment, $debug)
    {
        if (null === self::$instance) {
            self::$instance = new self($className, $environment, $debug);
        }

        return self::$instance;
    }

    /**
     * @param $throw
     *
     * @return App|null
     */
    public static function getInstance($throw = true)
    {
        if (null === self::$instance && $throw) {
            throw new \LogicException(
                'Please run createInstance and create application before get application instance'
            );
        }

        return self::$instance;
    }

    /**
     * Override getter for access to components.
     *
     * @param $name
     *
     * @return object
     */
    public function __get($name)
    {
        return $this->getContainer()->get($name);
    }

    /**
     * Clear application instance.
     */
    public static function shutdown()
    {
        self::$instance = null;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    public function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * @return \Symfony\Component\HttpKernel\Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    public function enableCache($cacheClass)
    {
        $this->cacheClass = $cacheClass;
    }

    /**
     * Start application.
     *
     * @throws \Exception
     */
    public function run()
    {
        if ('cli' === php_sapi_name()) {
            // do run console application

            $input = new ArgvInput();
            $env = $input->getParameterOption(['--env', '-e'], getenv('SYMFONY_ENV') ?: 'dev');
            $debug = '0' !== getenv('SYMFONY_DEBUG') && !$input->hasParameterOption(['--no-debug', '']) && 'prod' !== $env;

            if ($debug || $this->debug) {
                Debug::enable();
            }

            $application = new Application($this->getKernel());
            $application->run($input);
        } else {
            // do run web application
            $request = Request::createFromGlobals();

            $kernel = $this->getKernel();

            if (!$this->debug) {
                $kernel->loadClassCache();
            }

            if ($this->cacheClass && class_exists($this->cacheClass)) {
                // add (or uncomment) this new line!
                // wrap the default AppKernel with the AppCache one
                $cacheClass = $this->cacheClass;
                $kernel = new $cacheClass($kernel);
            }

            // actually execute the kernel, which turns the request into a response
            // by dispatching events, calling a controller, and returning the response
            $response = $kernel->handle($request);

            // send the headers and echo the content
            $response->send();

            // triggers the kernel.terminate event
            $kernel->terminate($request, $response);
        }
    }
}
