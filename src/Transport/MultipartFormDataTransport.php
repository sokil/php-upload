<?php 

namespace Sokil\Upload\Transport;

use Sokil\Upload\Exception\UploadError;

class MultipartFormDataTransport extends AbstractTransport
{
    private $file;
    
    public function __construct($fieldName)
    {
        parent::__construct($fieldName);
        $this->file = $_FILES[$this->fieldName];
    }
    
    public function getOriginalBaseName()
    {
        if(!$this->originalBaseName) {
            $this->originalBaseName = $this->file['name'];
        }
        return $this->originalBaseName;
    }
    
    public function getFileSize()
    {
        return $this->file['size'];
    }
    
    public function getFileType()
    {
        return $this->file['type'];
    }
    
    public function upload($targetPath)
    {
        if($this->file['error'] !== UPLOAD_ERR_OK) {
            throw new UploadError('Upload file error', $this->file['error']);
        }

        move_uploaded_file($this->file['tmp_name'], $targetPath);
    }
}