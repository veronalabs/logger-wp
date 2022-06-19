<?php
/*
 * This file is part of the LoggerWP package.
 *
 * License: MIT License
 */

namespace LoggerWp;

use LoggerWp\Service\AdminLogViewer;
use Psr\Log\LoggerInterface;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * LoggerWP channel
 *
 * It contains a stack of Handlers and a stack of Processors,
 * and uses them to store records that are added to it.
 *
 * @phpstan-type Level Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY
 * @phpstan-type LevelName 'DEBUG'|'INFO'|'NOTICE'|'WARNING'|'ERROR'|'CRITICAL'|'ALERT'|'EMERGENCY'
 * @phpstan-type Record array{message: string, context: mixed[], level: Level, level_name: LevelName, channel: string, datetime: \DateTimeImmutable, extra: mixed[]}
 *
 * @todo ErrorHandler is not implemented yet.
 */
class Logger implements LoggerInterface
{
    /**
     * Detailed debug information
     */
    public const DEBUG = 100;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    public const INFO = 200;

    /**
     * Uncommon events
     */
    public const NOTICE = 250;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    public const WARNING = 300;

    /**
     * Runtime errors
     */
    public const ERROR = 400;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public const CRITICAL = 500;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    public const ALERT = 550;

    /**
     * Urgent alert.
     */
    public const EMERGENCY = 600;

    /**
     * This is a static variable and not a constant to serve as an extension point for custom levels
     *
     * @var array<int, string> $levels Logging levels with the levels as key
     *
     * @phpstan-var array<Level, LevelName> $levels Logging levels with the levels as key
     */
    protected static $levels = [
        self::DEBUG     => 'DEBUG',
        self::INFO      => 'INFO',
        self::NOTICE    => 'NOTICE',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
        self::CRITICAL  => 'CRITICAL',
        self::ALERT     => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    ];

    /**
     * Default logger configuration options
     *
     * @var array
     */
    private $config = [
        'level'               => self::DEBUG,
        'dir_name'            => 'logger-wp',
        'channel'             => 'dev',
        'days_to_retain_logs' => 30,
    ];

    /**
     * @var array<HandlerInterface>
     */
    public static $instance;

    /**
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->setDefaultConfigGroup($config);

        if (!did_action('init')) {
            error_log('WordPress is not initialized! run logger into init action.');
            return;
        }

        $this->initLogDirectory();
        $this->initErrorHandler();
        $this->initAdmin();
    }

    /**
     * @return Logger
     */
    public function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initial the log directory if it doesn't exist
     *
     * @return void
     */
    private function initLogDirectory()
    {
        $logDirectoryName = Utils::getLogDirectory($this->config['dir_name']);

        /**
         * Create log directory if it doesn't exist
         */
        if (!file_exists($logDirectoryName)) {
            wp_mkdir_p($logDirectoryName);
        }

        /**
         * Create .htaccess to avoid public access.
         */
        if (is_dir($logDirectoryName) and is_writable($logDirectoryName)) {
            $htaccess_file = path_join($logDirectoryName, '.htaccess');

            if (!file_exists($htaccess_file) and $handle = @fopen($htaccess_file, 'w')) {
                fwrite($handle, "Deny from all\n");
                fclose($handle);
            }
        }

        /**
         * Delete logs older than x days
         */
        $logs = glob(path_join($logDirectoryName, '*.log'));
        $days = $this->config['days_to_retain_logs'];

        if ($days > 0) {
            $days = $days * 24 * 60 * 60;
            $now  = time();

            foreach ($logs as $log) {
                if ($now - filemtime($log) > $days) {
                    unlink($log);
                }
            }
        }
    }

    /**
     * Error handler for logging PHP errors
     *
     * @return void
     */
    private function initErrorHandler()
    {
        //ErrorHandler::register($this);
    }

    /**
     * Initial admin functionality
     *
     * @return void
     */
    private function initAdmin()
    {
        if (WP_DEBUG && is_admin()) {
            $loggerViewer = AdminLogViewer::getInstance();
            $loggerViewer->setConfig($this->config)->handleActions();
        }
    }

    /**
     * Set config group
     *
     * @param array $config
     * @return $this
     */
    private function setDefaultConfigGroup(array $config)
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Set config options
     *
     * @param $config
     * @param $value
     * @return $this
     */
    public function setConfig($config, $value)
    {
        $this->config[$config] = $value;
        return $this;
    }

    /**
     * Set channel name
     *
     * @param $channel
     * @return $this
     */
    public function setChannel($channel)
    {
        $this->setConfig('channel', $channel);
        return $this;
    }

    /**
     * Adds a log record.
     *
     * @param int $level The logging level
     * @param string $message The log message
     * @param array $context The log context
     * @return bool    Whether the record has been processed
     *
     * @phpstan-param Level $level
     */
    public function addRecord(int $level, string $message, array $context = []): bool
    {
        // Backward compatibility
        if (!did_action('init')) {
            return false;
        }

        $logLevelName = $this->getLevelName($level);
        $context      = $context ? json_encode($context, JSON_PRETTY_PRINT) : '';

        $logContent = sprintf('[%s] [%s] %s %s%s',
            date('Y-m-d H:i:s'),
            $logLevelName,
            $message,
            $context,
            PHP_EOL
        );

        return file_put_contents(
            Utils::getLogFinalPath($this->config['dir_name'], $this->config['channel']),
            $logContent, FILE_APPEND
        );
    }

    /**
     * Gets all supported logging levels.
     *
     * @return array<string, int> Assoc array with human-readable level names => level codes.
     * @phpstan-return array<LevelName, Level>
     */
    public static function getLevels(): array
    {
        return array_flip(static::$levels);
    }

    /**
     * Gets the name of the logging level.
     *
     * @throws InvalidArgumentException If level is not defined
     *
     * @phpstan-param  Level $level
     * @phpstan-return LevelName
     */
    public static function getLevelName(int $level): string
    {
        if (!isset(static::$levels[$level])) {
            throw new InvalidArgumentException('Level "' . $level . '" is not defined, use one of: ' . implode(', ', array_keys(static::$levels)));
        }

        return static::$levels[$level];
    }

    /**
     * Adds a log record at an arbitrary level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param mixed $level The log level
     * @param string $message The log message
     * @param array $context The log context
     *
     * @phpstan-param Level|LevelName|LogLevel::* $level
     */
    public function log($level, $message, array $context = []): void
    {
        if (!is_int($level) && !is_string($level)) {
            throw new \InvalidArgumentException('$level is expected to be a string or int');
        }

        $this->addRecord($level, (string)$message, $context);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array $context The log context
     */
    public function debug($message, array $context = []): void
    {
        $this->addRecord(static::DEBUG, (string)$message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array $context The log context
     */
    public function info($message, array $context = []): void
    {
        $this->addRecord(static::INFO, (string)$message, $context);
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array $context The log context
     */
    public function notice($message, array $context = []): void
    {
        $this->addRecord(static::NOTICE, (string)$message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array $context The log context
     */
    public function warning($message, array $context = []): void
    {
        $this->addRecord(static::WARNING, (string)$message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array $context The log context
     */
    public function error($message, array $context = []): void
    {
        $this->addRecord(static::ERROR, (string)$message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array $context The log context
     */
    public function critical($message, array $context = []): void
    {
        $this->addRecord(static::CRITICAL, (string)$message, $context);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array $context The log context
     */
    public function alert($message, array $context = []): void
    {
        $this->addRecord(static::ALERT, (string)$message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array $context The log context
     */
    public function emergency($message, array $context = []): void
    {
        $this->addRecord(static::EMERGENCY, (string)$message, $context);
    }
}
