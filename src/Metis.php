<?php

namespace Marquine\Metis;

use Doctrine\DBAL\DriverManager;

class Metis
{
    /**
     * The items contained in the transformation.
     *
     * @var array
     */
    protected $items;

    /**
     * The current globally used instance.
     *
     * @var object
     */
    protected static $instance;

    /**
     * Global configuration array.
     *
     * @var array
     */
    protected static $config;

    /**
     * Global database connections.
     *
     * @var array
    */
    protected static $connections = [];

    /**
     * Create or get a Metis instance.
     *
     * @return static
     */
    private static function instance()
    {
        if (static::$instance == null) {
            static::$instance = new Metis;
        }

        return static::$instance;
    }

    /**
     * Set the global configuration or get a config item.
     *
     * @param  mixed $config
     * @param  mixed $default
     * @return mixed
     */
    public static function config($config, $default = null)
    {
        if (is_string($config)) {
            foreach (explode('.', $config) as $segment) {
                $value = static::$config[$segment];
            }

            return $value ?: $default;
        }

        static::$config = $config;

        return static::instance();
    }

    /**
     * Add a database connection.
     *
     * @param  array  $params
     * @param  string $name
     * @return static
     */
    public static function addConnection($params, $name = 'default')
    {
        static::$connections[$name] = DriverManager::getConnection($params);

        return static::instance();
    }

    /**
    * Get a database connection.
    *
    * @param  string $connection
    * @return \Doctrine\DBAL\Connection
    */
    public static function connection($name = 'default')
    {
        return static::$connections[$name];
    }

    /**
     * Get current transformation items.
     *
     * @return array
     */
    public function get()
    {
        return $this->items;
    }

    /**
     * Utilities.
     *
     * @param  string $type
     * @param  array  $options
     * @return Metis
     */
    public function utility($type, $options)
    {
        $instance = isset($this) ? $this : new Metis;

        $utility = $instance->factory($type, 'utilities', $options);

        $utility->handle();

        return $instance;
    }

    /**
     * Extract data from the given source.
     *
     * @param  string $type
     * @param  mixed  $source
     * @param  array  $columns
     * @param  array  $options
     * @return Metis
     */
    public function extract($type, $source, $columns = null, $options = [])
    {
        $instance = isset($this) ? $this : new Metis;

        $extractor = $instance->factory($type, 'extractors', $options);

        $instance->items = $extractor->extract($source, $columns);

        return $instance;
    }

    /**
     * Execute a transformation.
     *
     * @param  string $type
     * @param  mixed  $columns
     * @param  array  $options
     * @return $this
     */
    public function transform($type, $columns = null, $options = [])
    {
        $transformer = $this->factory($type, 'transformers', $options);

        $this->items = $transformer->transform($this->items, $columns);

        return $this;
    }

    /**
     * Load data to the given destination.
     *
     * @param  string $type
     * @param  string $destination
     * @param  array  $options
     * @return $this
     */
    public function load($type, $destination, $options = [])
    {
        $loader = $this->factory($type, 'loaders', $options);

        $loader->load($destination, $this->items);

        return $this;
    }

    /**
     * Create an instance of the given class.
     *
     * @param  string $class
     * @param  string $category
     * @param  array  $options
     * @return mixed
     */
    protected function factory($class, $category, $options)
    {
        $aliases = [
            'extractors' => [
                'array' => 'ArrayData',
            ]
        ];

        if (! class_exists($class)) {

            if (isset($aliases[$category][$class])) {
                $class = $aliases[$category][$class];
            }

            $class = __NAMESPACE__ . '\\' . ucwords($category) . '\\' . $class;
        }

        return new $class($options);
    }
}