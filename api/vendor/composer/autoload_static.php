<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc076fb6f9eb7918867f67d10e3856cad
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Stripe\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc076fb6f9eb7918867f67d10e3856cad::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc076fb6f9eb7918867f67d10e3856cad::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitc076fb6f9eb7918867f67d10e3856cad::$classMap;

        }, null, ClassLoader::class);
    }
}
