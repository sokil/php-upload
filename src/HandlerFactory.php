<?php

namespace Sokil\Upload;

class HandlerFactory
{
    /**
     * @param array|null $options
     * @return Handler
     */
    public function createUploadHandler(array $options = null)
    {
        return new Handler($options);
    }
}