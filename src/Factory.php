<?php

namespace Sokil\Uploader;

class Factory
{
    public function createUploader(array $options = null)
    {
        return new Uploader($options);
    }
}