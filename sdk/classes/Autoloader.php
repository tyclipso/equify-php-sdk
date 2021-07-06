<?php
namespace equifySDK;

class Autoloader
{
	/**
     * Installs this class loader on the SPL autoload stack.
     */
    public static function register()
    {
        spl_autoload_register('equifySDK\Autoloader::loadClass');
    }

    /**
     * Uninstalls this class loader from the SPL autoloader stack.
     */
    public static function unregister()
    {
        spl_autoload_unregister('equifySDK\Autoloader::loadClass');
    }

    /**
     * Loads the given class or interface.
     * @param string $className The name of the class to load.
     */
    public static function loadClass($className)
    {
		$parts = explode('\\', $className);
		if ($parts[0] == 'equifySDK')
		{
			array_shift($parts);
			if (is_readable(__DIR__.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$parts).'.php')) include_once(__DIR__.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$parts).'.php');
			
		}
    }
}