<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7791acbe720e69db6364c92bd18b6700
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'GPLCellar\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'GPLCellar\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7791acbe720e69db6364c92bd18b6700::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7791acbe720e69db6364c92bd18b6700::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
