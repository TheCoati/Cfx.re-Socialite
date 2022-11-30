<?php

namespace TheCoati\CfxSocialite;

class Cfx
{
    public static string $keyPath = '';

    /**
     * Change the directory the RSA keys are loaded from.
     *
     * @param $path
     * @return void
     */
    public static function loadKeysFrom($path): void
    {
        static::$keyPath = $path;
    }

    /**
     * Get the key path of the given file.
     *
     * @param $file
     * @return string
     */
    public static function keyPath($file): string
    {
        if (static::$keyPath == '') {
            static::$keyPath = storage_path();
        }

        $file = ltrim($file, '/\\');

        return static::$keyPath
            ? rtrim(static::$keyPath, '/\\').DIRECTORY_SEPARATOR.$file
            : storage_path($file);
    }
}
