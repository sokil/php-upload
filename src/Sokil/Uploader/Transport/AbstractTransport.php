<?php

namespace Sokil\Uploader\Transport;

abstract class AbstractTransport
{
    protected $_fieldName;
    
    public function __construct($fieldName) {
        $this->_fieldName = $fieldName;
    }
    
    abstract public function getOriginalBaseName();
    
    abstract public function getFileSize();
    
    public function getFileType()
    {
        return 'application/octet-stream';
    }
    
    abstract public function upload($targetPath);
}