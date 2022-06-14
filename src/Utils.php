<?php
/*
 * This file is part of the LoggerWP package.
 *
 * License: MIT License
 */

namespace LoggerWp;

final class Utils
{
    public static function getClass(object $object): string
    {
        $class = \get_class($object);

        if (false === ($pos = \strpos($class, "@anonymous\0"))) {
            return $class;
        }

        if (false === ($parent = \get_parent_class($class))) {
            return \substr($class, 0, $pos + 10);
        }

        return $parent . '@anonymous';
    }

    /**
     * Get hashed channel name
     *
     * @return string
     */
    public static function getLogFileName($channel)
    {
        $dateSuffix = date('Y-m-d', time());
        $hashSuffix = wp_hash($dateSuffix);

        return sprintf('%s-%s-%s.log',
            $channel,
            $dateSuffix,
            $hashSuffix
        );
    }

    /**
     * Get log directory path
     *
     * @param $dirName
     * @return string
     */
    public static function getLogDirectory($dirName)
    {
        $uploadDir = wp_upload_dir();
        return $uploadDir['basedir'] . '/' . $dirName;
    }

    /**
     * Get log file path
     *
     * @param $dirName
     * @param $channel
     * @return string
     */
    public static function getLogFinalPath($dirName, $channel)
    {
        return self::getLogDirectory($dirName) . '/' . self::getLogFileName($channel);
    }
}
