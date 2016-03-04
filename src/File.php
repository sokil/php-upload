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

    /**
     * Get checksum of file
     *
     * @link https://tools.ietf.org/html/rfc1864
     * @return string checksum
     */
    public function getChecksum()
    {
        return base64_encode(md5_file($this->path, true));
    }

    /**
     * @return string MD5 hash of file
     */
    public function getMd5Sum()
    {
        return md5_file($this->path);
    }

    public function getStream($mode = 'r')
    {
        if (empty($this->stream[$mode])) {
            $this->stream[$mode] = fopen($this->getPath(), $mode);
        }

        return $this->stream[$mode];
    }
}