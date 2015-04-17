<?php

namespace Magento\Framework\Simplexml;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\ModuleManager\ModuleManagerInterface;

/**
 * Class ConfigReader
 * @package Magento\Framework\Simplexml
 */
class ConfigReader implements EventManagerAwareInterface
{

    const EVENT_MERGE = 'merge';

    /**
     * @var ModuleManagerInterface
     */
    protected $moduleManager;

    protected $events;

    /**
     * @var Config
     */
    protected static $prototype;

    /**
     * @param ModuleManagerInterface $moduleManager
     */
    public function __construct(ModuleManagerInterface $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Inject an EventManager instance
     *
     * @param EventManagerInterface $events
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers([
            __CLASS__,
            get_class($this)
        ]);
        $this->events = $events;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }

    /**
     * @return Config
     */
    protected static function getPrototype()
    {
        if (!self::$prototype) {
            self::$prototype = new Config();
        }
        return self::$prototype;
    }

    /**
     *
     * @param string|array $path
     *
     * @return Config
     */
    public function getConfig($path)
    {
            $files = $this->getConfigFiles($path);
            return  $this->merge($files);
    }

    /**
     * Merge files into a single Config object
     *
     * @param array $files
     *
     * @return Config
     */
    protected function merge(array $files)
    {

        $mergeToObject = clone static::getPrototype();
        $mergeToObject->loadString('<config/>');
        $params = ['mergeTo' => $mergeToObject, 'files' => $files];
        $results = $this->getEventManager()->trigger(static::EVENT_MERGE, $this, $params, function ($v) {
            return $v instanceof Config;
        });

        if ($results->stopped()) {
            return $results->last();
        }

        $mergeModel = clone static::getPrototype();
        foreach ($files as $configFile) {
            if ($mergeModel->loadFile($configFile)) {
                $mergeToObject->extend($mergeModel, true);
            }
        }
        return $mergeToObject;
    }

    /**
     * Search for files in all loaded modules
     *
     * @param $filePaths
     *
     * @return array
     */
    public function getConfigFiles($filePaths)
    {
        if (is_string($filePaths)) {
            $filePaths = (array)$filePaths;
        }

        if (!is_array($filePaths)) {
            throw new Exception\InvalidArgumentException('Path must be either string or array.');
        }

        $configFiles = [];
        $modules = $this->moduleManager->getLoadedModules(false);
        foreach ($modules as $moduleName => $module) {
            $moduleClass = new \ReflectionClass($module);
            $modulePath = dirname($moduleClass->getFileName());
            foreach ($filePaths as $filePath) {
                $configFile = $modulePath . DIRECTORY_SEPARATOR . $filePath;
                if (file_exists($configFile)) {
                    $configFiles[] = $configFile;
                }
            }
        }
        return $configFiles;
    }

}
