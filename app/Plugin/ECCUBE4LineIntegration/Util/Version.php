<?php

namespace Plugin\ECCUBE4LineIntegration\Util;

use Eccube\Common\Constant;

/**
 * Class Version.
 * Util to check version.
 */
class Version
{

    /**
     * Check version to support get instance function. (monolog, new style, ...).
     *
     * @return bool
     */
    public static function isSupportGetInstanceFunction()
    {
        return version_compare(Constant::VERSION, '3.0.9', '>=');
    }

    /**
     * Check version to support new log function.
     *
     * @return bool
     */
    public static function isSupportLogFunction()
    {
        return version_compare(Constant::VERSION, '3.0.12', '>=');
    }

    /**
     * Check support in version Ec cube.
     *
     * @param string $version
     * @param string $operation
     *
     * @return bool
     */
    public static function isSupport($version = '3.0.9', $operation = '>=')
    {
        return version_compare(Constant::VERSION, $version, $operation);
    }

    /**
     * Check version to support new session function.
     *
     * @return bool
     */
    public static function isSupportNewSession()
    {
        return version_compare(Constant::VERSION, '3.0.15', '>=');
    }
}
