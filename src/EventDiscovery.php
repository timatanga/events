<?php

/*
 * This file is part of the Events package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace timatanga\Events;

use timatanga\Events\Exceptions\RegisterEventException;

class EventDiscovery
{

    /**
     * @var string
     */
    private static $config = 'events.php';

    /**
     * @var string
     */
    private static $basePath;

    /**
     * @var Dispatcher
     */
    private static $dispatcher;

    /**
     * @var array
     */
    private static $listeners = [];

    /**
     * @var array
     */
    private static $subscribers = [];


    /**
     * Create a new class instance.
     *
     * @param string $basePath  the applications base path
     */
    public static function discover( ?string $basePath = null )
    {
        // resolve applications base path
        static::$basePath = static::resolveBasePath($basePath);

        // resolve config file
        $config = static::resolveConfig();

        // extract listener configuration
        $listenerDirs = static::extractListenerDirectories($config);

        // retrieve listener classes
        $listeners = static::getListeners($listenerDirs);

        // extract subscriber configuration
        $subscriberDirs = static::extractSubscriberDirectories($config);

        // retrieve subscriber classes
        $subscribers = static::getSubscribers($subscriberDirs);

        return ['listeners' => $listeners, 'subscribers' => $subscribers];
    }


    /**
     * Resolve base path
     * 
     * When installed as a package, it's assumed that the package is located under vendor/timatanga/events
     * The vendors parent directory is assumed as applications path 
     * 
     * @param string $basePath  the applications base path
     * @return string
     */
    private static function resolveBasePath( ?string $basePath )
    {
        if (! is_null($basePath) )
            return $basePath;

        $separator = DIRECTORY_SEPARATOR;

        // return __DIR__.$separator.'..'.$separator.'..'.$separator.'..'.$separator.'..'.$separator;
        return __DIR__.$separator.'..'.$separator.'..'.$separator.'..'.$separator;
    }


    /**
     * Resolve configuration file
     * 
     * @return string|null
     */
    private static function resolveConfig()
    {
        // config file
        $file = static::$config;

        // laravel config path
        $laravelPath = function_exists('config_path') ? config_path($file) : null;

        // local config path
        $localPath = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$file;

        if ( static::fileExists($laravelPath) )
            return require $laravelPath;

        if ( static::fileExists($localPath) )
            return require $localPath;

        return null;
    }


    /**
     * Extract event listener directories
     * 
     * @param  array  $config  base configuration
     * @return array|null
     */
    private static function extractListenerDirectories( array $config = [] )
    {
        $dirs = [];

        if ( isset($config['listeners']) && !empty($config['listeners']) )
            $dirs = array_merge([], $config['listeners']);

        return $dirs;
    }


    /**
     * Extract event subscriber directories
     * 
     * @param  array  $config  base configuration
     * @return array|null
     */
    private static function extractSubscriberDirectories( array $config = [] )
    {
        $dirs = [];

        if ( isset($config['subscribers']) && !empty($config['subscribers']) )
            $dirs = array_merge([], $config['subscribers']);

        return $dirs;
    }


    /**
     * Get listener configuration
     * 
     * @param array $listenerDirs
     * @return array
     */
    private static function getListeners( array $listenerDirs = [] )
    {
        $listeners = [];

        foreach ($listenerDirs as $path) {

            $path = static::$basePath.$path;
            
            foreach (static::getFiles($path) as $file) {

                // parse namespace and class from file content
                [$namespace, $class] = static::classByFile($file);

                // build classpath by namespace and class
                $classpath = ($namespace ? $namespace.'\\' : '') . $class;

                if ( in_array('timatanga\Events\Contracts\EventListenerInterface', class_implements($classpath)) )
                    $listeners[$class] = [$classpath, 'handle'];

            }
        }

        return $listeners;
    }


    /**
     * Get listener configuration
     * 
     * @param array $subscriberDirs
     * @return array
     */
    private static function getSubscribers( array $subscriberDirs = [] )
    {
        $subscribers = [];

        foreach ($subscriberDirs as $path) {

            $path = static::$basePath.$path;

            foreach (static::getFiles($path) as $file) {

                // parse namespace and class from file content
                [$namespace, $class] = static::classByFile($file);

                // build classpath by namespace and class
                $classpath = ($namespace ? $namespace.'\\' : '') . $class;

                if ( in_array('timatanga\Events\Contracts\EventSubscriberInterface', class_implements($classpath)) )
                    $subscribers[] = new $classpath;
            }
        }

        return $subscribers;
    }


    /**
     * Get namespace and class by filename
     * 
     * @param string  $path
     * @return array  ['namespace' => '..', 'class' => '..']
     */ 
    private static function classByFile( string $path )
    {   
        if (! static::fileExists($path) )
            throw new RegisterEventException('Invalid event registry file');

        // read file content
        $content = file_get_contents($path);

        // init arguments
        $namespace = $class = null;

        if (preg_match('/namespace\s+([a-zA-z0-9_]{1,})(.*);/', $content, $matches))
            $namespace = $matches[1];

        if (preg_match('/class\s+([a-zA-z0-9_]{1,})(\s+|{)/', $content, $matches))
            $class = $matches[1];

        return [$namespace, $class];
    }


    /**
     * Read files within directory
     * 
     * @param string  $path  path to directory
     * @param bool    $include    inlude given file for deletion
     */ 
    private static function getFiles( string $path )
    {
        if (! is_dir($path) )
            throw new RegisterEventException('Invalid event registry path');

        // List of name of files inside specified path
        return glob($path.'/*'); 
    }


    /**
     * Determine if config file exists
     * 
     * Load and extract configuration by key
     *
     * @param string      $file
     * @return bool
     */
    private static function fileExists($file)
    {
        if (!is_file($file) || !file_exists($file))
            return false;

        return true;
    }

}