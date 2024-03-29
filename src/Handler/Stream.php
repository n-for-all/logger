<?php

namespace Ajaxy\Logger\Handler;

use Ajaxy\Logger\HandlerInterface;

class Stream extends Base implements HandlerInterface
{

    /** @var resource[]|null */
    protected $streams;

    /** @var string[]|null */
    protected $dirs;

    /** @var array|null */
    protected $files;

    /** @var string[]|null */
    private $errors = [];

    /** @var string[]|null */
    private $tags = null;

    private $format = null;

    protected $filePermission;
    protected $useLocking;

    /** @var string|null */
    protected $status = HandlerInterface::PENDING;

    /** @var string|null */
    protected $errorMessage = null;

    /**
     * @param array|strings $streams
     * @param array         $tags           An array of tags to handle, keep empty for any tag, ex: ['cron' => [], 'mail' => [Logger::ERROR]]
     * @param int           $format         The log format to use
     * @param int|null      $filePermission Optional file permissions (default (0644) are only for owner read/write)
     * @param Boolean       $useLocking     Try to lock log file before doing any writes
     */
    public function __construct($streams, $tags = null, $format = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", $filePermission = 0775, $useLocking = false)
    {
        if (is_resource($streams)) {
            $this->streams[] = $streams;
        } elseif (is_string($streams)) {
            if ($this->createDir($streams)) {
                $this->dirs[] = rtrim($streams, DIRECTORY_SEPARATOR);
            }
        } elseif (is_array($streams)) {
            foreach ($streams as $stream) {
                if (is_resource($stream)) {
                    $this->streams[] = $stream;
                } elseif (is_string($stream)) {
                    //Show error if the logger can't create or save its files
                    if ($this->createDir($stream)) {
                        $this->dirs[] = rtrim($stream, DIRECTORY_SEPARATOR);
                    }
                }
            }
        }
        if (empty($this->dirs) && empty($this->streams)) {
            //nothing to write to
            $this->errors[] = 'None of the streams provided could be initialized';
            $this->status = HandlerInterface::ERROR;
        } else {
            $this->status = HandlerInterface::READY;
        }
        if (is_string($format)) {
            $this->format = function ($tag, $record) use ($format) {
                $value = $format;
                foreach ($record as $key => $replace) {
                    if (is_array($replace)) {
                        if (empty($replace)) {
                            $replace = '';
                        } else {
                            $replace = json_encode($replace);
                        }
                    }
                    $value = str_replace('%' . $key . '%', $replace, $value);
                }
                return $value;
            };
        } elseif (is_callable($format)) {
            $this->format = $format;
        }
        $this->tags = $tags;
        $this->filePermission = $filePermission;
        $this->useLocking = $useLocking;
    }

    /**
     * Return the currently active stream if it is open
     *
     * @return resource|null
     */
    public function getStreams()
    {
        return $this->streams;
    }
    /**
     * Return the stream URL if it was configured with a URL and not an active resource
     *
     * @return string|null
     */
    public function getDirs()
    {
        return $this->dirs;
    }
    /**
     * Write the stream content to the directories
     *
     * @param string $tag
     * @param array  $record
     *
     * @return void
     */
    protected function writeToDirs($tag, array $record)
    {
        if (!empty($this->dirs)) {
            foreach ($this->dirs as &$dir) {
                if (null === $dir || '' === $dir) {
                    continue;
                }
                $filename = $dir . DIRECTORY_SEPARATOR . $tag . ".log";
                //if the directory was converted to a resource
                if (isset($this->files[$filename]) && is_resource($this->files[$filename])) {
                    $this->streamWrite($this->files[$filename], null, $record);
                    continue;
                }
                set_error_handler([$this, 'customErrorHandler']);
                $stream = fopen($filename, 'a');
                if ($this->filePermission !== null) {
                    @chmod($filename, $this->filePermission);
                }
                restore_error_handler();
                if (is_resource($stream)) {
                    $this->files[$filename] = $stream;
                    $this->streamWrite($this->files[$filename], null, $record);
                } else {
                    trigger_error("Logging is inactive\n" . implode("\n", $this->errors), E_USER_NOTICE);
                }
            }
        }
        return $this;
    }

    /**
     * Write the stream content to another stream
     *
     * @param string $tag
     * @param array  $record
     *
     * @return void
     */
    protected function writeToStreams($tag, array $record)
    {
        if (!empty($this->streams)) {
            foreach ($this->streams as $stream) {
                if ($this->useLocking) {
                    flock($stream, LOCK_EX);
                }
                $this->streamWrite($stream, $tag, $record);
                if ($this->useLocking) {
                    flock($stream, LOCK_UN);
                }
            }
        }
        return $this;
    }

    /**
     * Write to stream
     * 
     * @param resource $stream
     * @param string   $tag
     * @param array    $record
     * 
     * @return self
     */
    protected function streamWrite($stream, $tag, array $record)
    {
        $record = call_user_func($this->format, $tag, $record);
        $record = ($tag ? $tag . " - " : '') . (string) $record;
        fwrite($stream, $record);
        return $this;
    }


    private function customErrorHandler($code, $msg)
    {
        $this->errors[] = $code . " - " . $msg;
    }

    /**
     * @param string $url
     *
     * @return null|string
     */
    private function getDirFromUrl($url)
    {
        $pos = strpos($url, '://');
        if ($pos === false) {
            return $url;
        }
        if ('file://' === substr($url, 0, 7)) {
            return dirname(substr($url, 7));
        }
        return '';
    }
    private function createDir($dir)
    {
        $dir = $this->getDirFromUrl($dir);
        if (null !== $dir) {

            $this->errorMessage = null;
            if (!is_dir($dir)) {
                $umask = umask(0);

                if (!@mkdir($dir, 0755, true)) {
                    $this->errorMessage = error_get_last();
                }

                umask($umask);
                clearstatcache(false, $dir);

                if (!is_dir($dir) || !is_readable($dir)) {
                    throw new \Exception(sprintf('Failed to create the log directory "%s". %s', $dir, json_encode($this->errorMessage)));
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Handle the stream content
     *
     * @param string $tag
     * @param array  $record
     *
     * @return self
     */
    public function handle($tag, array $record)
    {
        if (!empty($this->dirs)) {
            $this->writeToDirs($tag, $record);
        }
        if (!empty($this->streams)) {
            $this->writeToStreams($tag, $record);
        }
        return $this;
    }

    /**
     * Check if the stream can handle the given tag
     *
     * @param string  $tag
     * @param integer $debug_level
     *
     * @return boolean
     */
    public function canHandle($tag, $debug_level)
    {
        if (!$this->tags) {
            return true;
        }
        foreach ($this->tags as $key => $levels) {
            if (strtolower($key) == strtolower($tag)) {
                if (in_array($debug_level, (array)$levels)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get status code
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * End the stream
     *
     * @return self
     */
    public function end()
    {
        if (!empty($this->files)) {
            foreach ($this->files as $file) {
                is_resource($file) && @fclose($file);
            }
        }
        if (!empty($this->streams)) {
            foreach ($this->streams as $file) {
                is_resource($file) && @fclose($file);
            }
        }
        $this->streams = null;
        $this->files = null;
        $this->status = HandlerInterface::CLOSED;

        return $this;
    }
}
