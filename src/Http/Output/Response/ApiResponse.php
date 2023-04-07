<?php

namespace Addons\Core\Http\Output\Response;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Addons\Core\Http\Output\Response\TextResponse;

class ApiResponse extends TextResponse {

    public function getOf(): string
    {
        if ($this->of == 'auto') {
            $request = app('request');
            $of = $request->input('of', null);

            if (!in_array($of, ['txt', 'text', 'json', 'xml', 'yaml', 'proto', 'protobuf']))
                $of = '';

            return $of;
        }

        return $this->of;
    }

    public function getMessage(): Translator|array|null|string
    {
        return null;
    }

    public function getOutputData(): ?array
    {
        return Arr::except(parent::getOutputData(), ['action', 'message']);
    }

}
