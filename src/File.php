<?php

namespace Sokil\Upload;

class File
{
    private $originalBasename;
    private $path;
    private $size;
    private $type;

    private $stream;

    public function __construct(
        $path,
        $originalBasename,
        $size,
        $type
    ) {
        $this->path = $path;
        $this->originalBasename = $originalBasename;
        $this->size = $size;
        $this->type = $type;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getOriginalBasename()
    {
        return $this->originalBasename;
    }

    public function getOriginalFilename()
    {
        return strtolower(pathinfo($this->originalBasename, PATHINFO_FILENAME));
    }

    public function getExtension()
    {
        return strtolower(pathinfo($this->originalBasename, PATHINFO_EXTENSION));
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getChecksum()
    {
        return base64_encode(md5_file($this->path, true));
    }

    public function getStream($mode = 'r')
    {
        if (empty($this->stream[$mode])) {
            $this->stream[$mode] = fopen($this->getPath(), $mode);
        }

        return $this->stream[$mode];
    }
}