<?php
namespace Configuration;

use Exception;

class Configuration extends Object
{
    /**
     * read all configurations, merge them and set as DATA
     */
    public function __construct($config)
    {
        $mainConfig = $this->_configCache();

        if (!$mainConfig) {
            $coreConfig = $this->loadModuleConfiguration('Core');
            $otherConfig = $this->loadEnabledModulesConfiguration($coreConfig['modules']);
            $mainConfig = array_merge_recursive($coreConfig, $otherConfig);
            $this->_configCache($mainConfig);
        }

        parent::__construct($mainConfig);
        $newData = $this->traveler('convertToObject');
        $this->setData($newData);
    }

    /**
     * get configuration for single module
     * 
     * @param string $module
     * @return array
     */
    protected function loadModuleConfiguration($module)
    {
        //$module = Loader::code2name($module);
        $mainConfig = $this->getConfiguration($module);
        return $mainConfig;

        return [];
    }

    /**
     * return path for module configuration file
     *
     * @param string $module
     * @return string
     * @throws Exception
     */
    protected function getConfiguration($module)
    {
        if ($module === 'Core') {
            $basePath = CORE_LIB . 'config.';
        } else {
            $libPath = Loader::name2path($module, FALSE);
            $basePath = CORE_LIB . $libPath . '/etc/config.';
        }

        $ini = $basePath . 'ini';
        $json = $basePath . 'json';
        $php = $basePath . 'php';

        switch (true) {
            case file_exists($ini):
                return parse_ini_file($ini, true);

            case file_exists($json):
                $data = file_get_contents($json);
                return json_decode($data, true);

            case file_exists($php):
                return file_get_contents($php, true);

            default:
                throw new Exception (
                    'Missing '
                    . $module
                    . " configuration:\n    $ini\n    $json\n    $php"
                );
                break;
        }
    }

    /**
     * load configuration for all enabled modules
     * 
     * @param array $modules
     * @return array
     */
    protected function loadEnabledModulesConfiguration($modules)
    {
        $config = [];
        foreach ($modules as $moduleName => $enabled) {
            if ($enabled === 'enabled') {
                $config[] = $this->loadModuleConfiguration($moduleName);
            }
        }

        return array_merge_recursive(...$config);
    }

    /**
     * convert first level arrays to blue objects
     * 
     * @param string $key
     * @param mixed $data
     * @return Object
     */
    protected function convertToObject($key, $data)
    {
        if (is_array($data)) {
            return new Object($data);
        }

        return $data;
    }

    /**
     * return cached configuration or save it to cache file
     * 
     * @param null|mixed $data
     * @return bool|void
     */
    protected function configCache($data = NULL)
    {
        /** @var Cache $cache */
        //$cache = Loader::getObject('Core\Blue\Model\Cache');
        if ($data) {
            $readyData = serialize($data);
            return $cache->setCache('main_configuration', $readyData);
        } else {
            return unserialize($cache->getCache('main_configuration'));
        }
    }
}