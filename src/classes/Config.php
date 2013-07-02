<?php namespace Arx\classes;

/**
 * Config
 *
 * @category Configuration
 * @package  Arx
 * @author   Daniel Sum <daniel@cherrypulp.com>
 * @author   Stéphan Zych <stephan@cherrypulp.com>
 * @license  http://opensource.org/licenses/MIT MIT License
 * @link     http://arx.xxx/doc/Config
 */
class Config extends Singleton
{
    // --- Protected members

    protected static $aSettings = array();


    // --- Magic methods


    // --- Public methods

    /**
     * Delete a setting.
     *
     * @param string $sName The name of the setting
     *
     * @return void
     */
    public static function delete($sName)
    {
        Arrays::delete(static::$aSettings, $sName);
    } // delete


    /**
     * Get value from $_settings
     *
     * @param string $sNeedle  The (dot-notated) name
     * @param mixed  $mDefault A default value if necessary
     *
     * @return mixed           The value of the setting or the entire settings array
     *
     * @example
     * Config::getInstance()->get('something.other');
     *
     * @todo Faire en sorte que si pas trouvé dans les config user (property), il tente de chopper la config par défault (default.property)
     */
    public static function get($sNeedle = null, $mDefault = null)
    {
        if (is_null($sNeedle) && is_null($mDefault)) {
            $mDefault = static::$aSettings;
        }

        return Arrays::get(static::$aSettings, $sNeedle, Arrays::get(static::$aSettings, 'defaults.'.$sNeedle, $mDefault));
    } // get


    /**
     * Load single or multiple file configuration.
     *
     * @param string $mPath      Array of path or string
     * @param string $sNamespace String used as reference (ex. Config::get('namespace.paths.classes'))
     *
     * @return instance
     *
     * @example
     * Config::getInstance()->load('paths.adapters', 'defaults'); // dot-notated query url in configuration paths
     * Config::getInstance()->load('some/path/to/your/configuration/file.php');
     * Config::getInstance()->load('some/path/to/your/configuration/folder/');
     */
    public static function load($mPath, $sNamespace = null)
    {
        if (is_array($mPath) && count($mPath) > 0) {
            $aFiles = realpath($mPath);
        } elseif (strpos($mPath, '.') > 0 && !is_null(Arrays::get(static::$aSettings, $mPath))) {
            $tmp = Arrays::get(static::$aSettings, $mPath);
            $aFiles = glob(substr($tmp, -1) === '/' ? $tmp.'*' : $tmp);
        } else {
            $aFiles = glob(substr($mPath, -1) === '/' ? $mPath.'*' : $mPath);
        }

        foreach ($aFiles as $sFilePath) {
            $pathinfo = pathinfo($sFilePath);
            $key = !is_null($sNamespace) ? $sNamespace.'.'.$pathinfo['filename'] : $pathinfo['filename'];

            if (!is_int(array_search($sFilePath, $aFiles))) {
                $key = array_search($sFilePath, $aFiles);
            }

            if (!is_null(Arrays::get(static::$aSettings, $key))) {
                static::set($key, Arrays::merge(static::get($key), include $sFilePath));
            } else {
                static::set($key, include $sFilePath);
            }
        }

        return static::getInstance();
    } // load


    /**
     * Request a particular config.
     *
     * @param string $sNeedle   The config name requested
     * @param string $sCallback The callback if not find
     * @param array  $aArgs     The args of the callback
     *
     * @return bool             True if the config exist, false instead
     */
    public static function needs($sNeedle, $sCallback = null, $aArgs = null) {
        if (!is_null(static::get($sNeedle))) {
            return true;
        } elseif (!is_null($sCallback)) {
            if (is_array($aArgs)) {
                return call_user_func_array($sCallback, $aArgs);
            }

            return call_user_func($sCallback);
        }

        return false;
    } // needs


    /**
     * Set value in $_settings
     *
     * @param string $sName  Array of new value or name
     * @param mixed  $mValue Value for name
     *
     * @return instance
     *
     * @example
     * Config::getInstance()->set(array('defaults.somehing' => 'something'));
     * Config::getInstance()->set('defaults.something', 'something');
     */
    public static function set($sName, $mValue = null)
    {
        if (is_array($sName)) {
            foreach ($sName as $key => $value) {
                Arrays::set(static::$aSettings, $key, $value);
            }
        } else {
            Arrays::set(static::$aSettings, $sName, $mValue);
        }

        return static::getInstance();
    } // set

} // class::Config
