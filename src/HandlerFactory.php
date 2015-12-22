<?php

namespace Sokil\Upload;

class HandlerFactory
{
    public function createUploader(array $options = null)
    {
        return new Handler($options);
    }
}