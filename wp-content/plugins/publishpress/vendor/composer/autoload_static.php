<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit49efeb7e9dd2f90f1253e0ca403f8a2a
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit49efeb7e9dd2f90f1253e0ca403f8a2a::$classMap;

        }, null, ClassLoader::class);
    }
}
