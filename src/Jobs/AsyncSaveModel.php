<?php

namespace Addons\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AsyncSaveModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $className;
    protected $saveList;
    protected $connectionName;

    /**
     * Save models in queue(async)
     *
     * @param string $className Model
     * @param array  $saveList  [[id => xxx, field1 => value1]]
     */
    public function __construct(string $className, array $saveList, string $connectionName = null)
    {
        if (!is_subclass_of($className, Model::class))
            throw new \RuntimeException($className .' is not instanceOf \Illuminate\Database\Eloquent\Model');

        $this->className = $className;
        $this->saveList = base64_encode(serialize($saveList));
        $this->connectionName = $connectionName;

        //$this->onQueue('async-save-model');

    }

    public function handle()
    {
        $this->saveList = unserialize(base64_decode($this->saveList));

        if (empty($this->saveList))
            return;

        $class = $this->className;
        $instance = new $class();
        $idName = $instance->getKeyName();

        $list = collect($this->saveList);

        // ID 不存在
        if (!array_key_exists($idName, $list->first()))
            return;

        $list = $list->keyBy($idName);

        $collections = $instance::whereIn($idName, $list->keys())->get(array_keys($list->first()));

        foreach($collections as $item)
        {
            $item->fill($list[$item->getKey()]);

            $item->save();
        }

    }

}
