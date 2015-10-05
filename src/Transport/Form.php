<?php 

namespace Sokil\Uploader\Transport;

use Sokil\Uploader\Exception;

class Form extends AbstractTransport
{
    private $file;
    
    public function __construct($fieldName) {
        parent::__construct($fieldName);
        $this->file = $_FILES[$this->fieldName];
    }
    
    public function getOriginalBaseName() {
        if(!$this->originalBaseName) {
            $this->originalBaseName = $this->file['name'];
        }
        return $this->originalBaseName;
    }
    
    public function getFileSize() {
        return $this->file['size'];
    }
    
    public function getFileType()
    {
        return $this->file['type'];
    }
    
    public function upload($targetPath)
    {
        if($this->file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload file error #' . $this->file['error']);
        }

        move_uploaded_file($this->file['tmp_name'], $targetPath);
    }
}