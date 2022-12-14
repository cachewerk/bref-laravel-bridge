<?php

namespace CacheWerk\BrefLaravelBridge;

class StorageDirectories
{
    /**
     * The storage path for the execution environment.
     *
     * @var string
     */
    public const Path = '/tmp/storage';

    /**
     * Ensure the necessary storage directories exist.
     *
     * @param  array  $extra
     * @return void
     */
    public static function create(array $extra = [])
    {
        $directories = array_merge([
            // self::Path . '/app',
            // self::Path . '/logs',
            self::Path . '/bootstrap/cache',
            self::Path . '/framework/cache',
            self::Path . '/framework/views',
        ], $extra);

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                fwrite(STDERR, "Creating storage directory: {$directory}" . PHP_EOL);

                mkdir($directory, 0755, true);
            }
        }
    }
}
