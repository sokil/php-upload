<?php

namespace Sokil\Upload;

class Result
{
    public function __construct($path, $size, $extension, $originalFilename)
    {
        $this->path = $path;
        $this->size = $size;
        $this->extension = $extension;
        $this->originalFilename = $originalFilename;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function getOriginalFilename()
    {
        return $this->originalFilename;
    }
}