<?php

namespace Ajaxy\Logger;

use DateTimeZone;
/**
 * https://tools.ietf.org/html/rfc5424
 */
class Logger implements \Psr\Log\LoggerInterface
{
    /**
     * Debug: debug-level messages
     */
    const DEBUG = 7;
    /**
     * Informational: informational messages
     */
    const INFO = 6;
    /**
     * Notice: normal but significant condition
     */
    const NOTICE = 5;
    /**
     * Warning: warning conditions
     */
    const WARNING = 4;
    /**
     * Error: error conditions
     */
    const ERROR = 3;
    /**
     * Critical: critical conditions
     */
    const CRITICAL = 2;
    /**
     * Alert: action must be taken immediately
     */
    const ALERT = 1;
    /**
     * Emergency: system is unusable
     */
    const EMERGENCY = 0;

    /**
     * Logging levels from syslog protocol defined in RFC 5424, https://tools.ietf.org/html/rfc5424
     *
     * @var string[] $levels Logging levels with the levels as key
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
     * The handler stack
     *
     * @var HandlerInterface[]
     */
    protected $handlers;
    /**
     * @var bool
     */
    protected $use_microsecond = true;

    /**
     * @var DateTimeZone
     */
    protected $timezone;

    /**
     * @param HandlerInterface[] $handlers   Optional stack of handlers, the first one in the array is called first, etc.
     * @param callable[]         $processors Optional array of processors
     * @param DateTimeZone       $timezone   Optional timezone, if not provided date_default_timezone_get() will be used
     */
    public function __construct(array $handlers = [], array $processors = [], DateTimeZone $timezone = null)
    {
        $this->handlers = $handlers;
        $this->processors = $processors;
        $this->timezone = $timezone ?: new DateTimeZone(date_default_timezone_get() ?: 'UTC');
    }
    /**
     * Adds a handler on to the stack.
     */
    public function addHandler(HandlerInterface $handler)
    {
        $this->handlers[] = $handler;
        return $this;
    }
    /**
     * Removes a handler from the stack
     */
    public function removeHandler($index)
    {
        if (!$this->handlers) {
            return null;
        }
        unset($this->handlers[$index]);
        return $this;
    }
    /**
     * Removes a handler from the stack
     */
    public function getHandler($index)
    {
        if (!$this->handlers || !isset($this->handlers[$index])) {
            return null;
        }
        return $this->handlers[$index];
    }
    /**
     * Set handlers, replacing all existing ones.
     *
     * If a map is passed, keys will be ignored.
     *
     * @param HandlerInterface[] $handlers
     */
    public function setHandlers(array $handlers)
    {
        $this->handlers = [];
        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }
        return $this;
    }
    /**
     * @return HandlerInterface[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }


    /**
     * Adds a log record at an arbitrary level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function log($level, $message, array $context = [])
    {
        $level = static::getLevel($level);
        return $this->addRecord('debug', $level, (string) $message, $context);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function debug($message, array $context = [])
    {
        return $this->_log('debug', static::DEBUG, (string) $message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function info($message, array $context = [])
    {
        return $this->_log('info', static::INFO, (string) $message, $context);
    }
    /**
     * Adds a log record at the NOTICE level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function notice($message, array $context = [])
    {
        return $this->_log('debug', static::NOTICE, (string) $message, $context);
    }
    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function warning($message, array $context = [])
    {
        return $this->_log('debug', static::WARNING, (string) $message, $context);
    }
    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function error($message, array $context = [])
    {
        return $this->_log('critical', static::ERROR, (string) $message, $context);
    }
    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function critical($message, array $context = [])
    {
        return $this->_log('critical', static::CRITICAL, (string) $message, $context);
    }
    /**
     * Adds a log record at the ALERT level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function alert($message, array $context = [])
    {
        return $this->_log('debug', static::ALERT, (string) $message, $context);
    }
    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function emergency($message, array $context = [])
    {
        return $this->_log('critical', static::EMERGENCY, (string) $message, $context);
    }

    /**
     * Control the use of microsecond resolution timestamps in the 'datetime'
     * member of new records.
     *
     * @param bool $micro True to use microtime() to create timestamps
     *
     * microtime function is only available on operating systems that support the gettimeofday() system call.
     * http://php.net/manual/en/function.microtime.php
     *
     * defaults to normal seconds if microtime is not available
     *
     */
    public function useMicrosecondTimestamps(bool $micro)
    {
        $this->use_microsecond = $micro;
    }


    /**
     * Create or open file with ({tag}.log) and adds a log record with specified level
     *
     * @since  1.0.0
     * @date   2018-01-18
     * @param  string  $tag   The logging tag
     * @param  int     $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    private function _log(string $tag, int $level, string $message, array $context = [])
    {
        $handlers = [];
        foreach ($this->handlers as $key => $handler) {
            if ($handler->canHandle($tag, $level)) {
                $handlers[] = $handler;
                if(!$handler->getBubble()){
                    // if we can't bubble, then break
                    break;
                }
            }
        }
        if (empty($handlers)) {
            return false;
        }

        $date = date('Y-m-d H:i:s');
        if ($this->use_microsecond) {
            $timestamp = microtime(true);
            $microseconds = sprintf("%06d", ($timestamp - floor($timestamp)) * 1000000);
            $date = date('Y-m-d H:i:s.' . $microseconds, (int) $timestamp);
        }
        $levelName = isset(static::$levels[$level]) ? static::$levels[$level]: 'UNKNOWN_LEVEL';
        $record = [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => $levelName,
            'channel' => $tag,
            'datetime' => $date,
            'extra' => [],
        ];
        foreach($handlers as $handler) {
            $handler->handle($tag, $record);
        }
        return true;
    }

    /**
     * Gets all registered logging levels.
     *
     * @since  1.0.0
     * @date   2018-01-18
     * @return array of all levels
     */
    public static function getLevels()
    {
        return static::$levels;
    }

    /**
     * Gets the level value by name
     *
     * @since  1.0.0
     * @date   2018-01-18
     * @param  string     $name
     * @param  integer     $default
     * @return integer   (level)
     */
    public function getLevel($name, $default = self::DEBUG){
        $index = array_search(strtoupper($name), self::$levels);
        if($index === false){
            return $default;
        }
        return $index;
    }

    /**
     * Set the timezone to be used for the timestamp of log records.
     *
     * @since 1.0.0
     * @date  2018-01-18
     * @param DateTimeZone $tz
     */
    public function setTimezone(DateTimeZone $tz)
    {
        $this->timezone = $tz;
        return $this;
    }

    /**
     * Set the timezone to be used for the timestamp of log records.
     *
     * @since  1.0.0
     * @date   2018-01-18
     * @return DateTimeZone
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Magic function to call the logs based on tags, Example:
     *
     * $logger->mailError() will create/append to a file called mail.log and adds an error record to it
     * same for all levels $logger->mailCritical(), $logger->mailDebug() etc...
     *
     * $logger->{FILE_NAME}{LEVEL} (joined by camel case or underscore, mailError and mail_error are equivilant)
     *
     * if level is not specified, debug will be used
     *
     * @since  1.0.0
     * @date   2018-01-18
     * @param  string     $method
     * @param  array     $args
     * @return Boolean whether it wrote the logs or not
     */
    public function __call($method, $args) {
        $arr = preg_split('/(?=[A-Z])/',$method, 2);
        if(count($arr) == 1){
            $arr = explode('_', $method, 2);
        }
        $level = self::DEBUG;
        if(count($arr) == 2){
            $level = $this->getLevel(trim($arr[1]));
        }
        $tag = strtolower(trim($arr[0]));
        $context = [];
        $message = '';
        if(isset($args[0])){
            $message = $args[0];
        }
        if(isset($args[1])){
            $context = (array)$args[1];
        }
        return $this->_log($tag, $level, $message, $context);
    }
}
