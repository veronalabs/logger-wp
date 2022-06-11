<?php
/*
 * This file is part of the LoggerWP package.
 *
 * License: MIT License
 */

namespace LoggerWp;

final class Utils
{
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
