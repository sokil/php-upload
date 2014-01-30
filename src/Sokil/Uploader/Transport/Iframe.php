<?php 

namespace Sokil\Uploader\Transport;

class Iframe extends AbstractTransport
{
    private $_file;
    
    public function __construct($fieldName) {
        parent::__construct($fieldName);
        $this->_file = $_FILES[$this->_fieldName];
    }
    
    public function getOriginalBaseName() {
        if(!$this->_originalBaseName) {
            $this->_originalBaseName = $this->_file['name'];
        }
        return $this->_originalBaseName;
    }
    
    public function getFileSize() {
        return $this->_file['size'];
    }
    
    public function getFileType()
    {
        return $this->_file['type'];
    }
    
    public function upload($targetPath)
    {
        if($this->_file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Upload file error #' . $this->_file['error']);
        }

        move_uploaded_file($this->_file['tmp_name'], $targetPath);
    }
}