<?php

namespace Sokil\Uploader\Transport;

abstract class AbstractTransport
{
    protected $_fieldName;
    
    protected $_originalBaseName;
    
    public function __construct($fieldName) {
        $this->_fieldName = $fieldName;
    }
    
    abstract public function getOriginalBaseName();
    
    public function setOriginalBaseName($baseName)
    {
        $this->_originalBaseName = $baseName;
        return $this;
    }
    
    abstract public function getFileSize();
    
    public function getFileType()
    {
        return 'application/octet-stream';
    }
    
    abstract public function upload($targetPath);
}