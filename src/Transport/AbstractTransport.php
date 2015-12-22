<?php

namespace Sokil\Upload\Transport;

abstract class AbstractTransport
{
    protected $fieldName;
    
    protected $originalBaseName;
    
    public function __construct($fieldName)
    {
        $this->fieldName = $fieldName;
    }
    
    abstract public function getOriginalBaseName();
    
    public function setOriginalBaseName($baseName)
    {
        $this->originalBaseName = $baseName;
        return $this;
    }
    
    abstract public function getFileSize();
    
    public function getFileType()
    {
        return 'application/octet-stream';
    }
    
    abstract public function upload($targetPath);
}