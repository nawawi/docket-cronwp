<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1d00991b6227419f508f6e3679679277
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'Nawawi\\DocketCronWP\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Nawawi\\DocketCronWP\\' => 
        array (
            0 => __DIR__ . '/../../..' . '/includes/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Nawawi\\DocketCronWP\\Console' => __DIR__ . '/../../..' . '/includes/src/Console.php',
        'Nawawi\\DocketCronWP\\Parser' => __DIR__ . '/../../..' . '/includes/src/Parser.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1d00991b6227419f508f6e3679679277::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1d00991b6227419f508f6e3679679277::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1d00991b6227419f508f6e3679679277::$classMap;

        }, null, ClassLoader::class);
    }
}
