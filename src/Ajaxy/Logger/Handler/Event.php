<?php

namespace Ajaxy\Logger\Handler;

use Ajaxy\Logger\HandlerInterface;

class Event extends Base implements HandlerInterface{

    /** @var string|null */
    private $errors = [];

    /** @var string|null */
    private $tags = null;

    private $format = null;


    /** @var string|null */
    protected $status = HandlerInterface::PENDING;

    /**
     * @param array           $tags           An array of tags to handle, keep empty for any tag, ex: ['cron' => [], 'mail' => [Logger::ERROR]]
     * @param int             $level          The minimum logging level at which this handler will be triggered
     */

    public function __construct($tags = null, $format = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n")
    {
        $this->status = HandlerInterface::READY;
        if(is_string($format)){
            $this->format = function($tag, $record) use ($format){
                $value = $format;
                foreach($record as $key => $replace){
                    if(is_array($replace)){
                        if(empty($replace)){
                            $replace = '';
                        }else{
                            $replace = json_encode($replace);
                        }
                    }
                    $value = str_replace('%'.$key.'%', $replace, $value);
                }
                return $value;
            };
        }elseif(is_callable($format)){
            $this->format = $format;
        }
        $this->tags = $tags;
    }

    public function handle($tag, array $raw_record)
    {
        $record = call_user_func($this->format, $tag, $raw_record);
        $record = (string) $record;
        if($this->on_log) call_user_func_array($this->on_log, [$tag, $record, $raw_record]);
        return $this;
    }

    /**
     * Trigger the onLog function once a log is requested
     *
     * @since  1.0.0
     * @date   2018-01-18
     * @param  callable   $callable
     * @return Handler\Event
     */
    public function onLog(callable $callable)
    {
        $this->on_log = $callable;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle($tag, $debug_level)
    {
        if(!$this->tags){
            return true;
        }
        foreach($this->tags as $key => $levels){
            if(strtolower($key) == strtolower($tag)){
                if(in_array($debug_level, $levels)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function end()
    {
        $this->status = HandlerInterface::CLOSED;

        return $this;
    }
}


 ?>
