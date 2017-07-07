<?php 

namespace Sokil\Upload\Transport;

use Sokil\Upload\File;

use Sokil\Upload\Exception\MaxFileSizeExceedException;
use Sokil\Upload\Exception\PartialUploadException;
use Sokil\Upload\Exception\UploadException;

class MultipartFormDataTransport extends AbstractTransport
{
    private $file;
    
    public function init()
    {
        $this->file = $_FILES[$this->fieldName];
    }

    protected function validate()
    {
        if($this->file['error'] === UPLOAD_ERR_OK) {
            return;
        }

        switch ($this->file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                throw new MaxFileSizeExceedException(
                    'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                    UPLOAD_ERR_FORM_SIZE
                );
            case UPLOAD_ERR_FORM_SIZE:
                throw new MaxFileSizeExceedException(
                    'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                    UPLOAD_ERR_FORM_SIZE
                );
            case UPLOAD_ERR_PARTIAL:
                throw new PartialUploadException(
                    'The uploaded file was only partially uploaded',
                    UPLOAD_ERR_PARTIAL
                );
            case UPLOAD_ERR_NO_FILE:
                throw new NoFileUploadedException(
                    'No file was uploaded',
                    UPLOAD_ERR_NO_FILE
                );
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new UploadException(
                    'Missing a temporary folder',
                    UPLOAD_ERR_NO_TMP_DIR
                );
            case UPLOAD_ERR_CANT_WRITE:
                throw new UploadException(
                    'Failed to write file to disk',
                    UPLOAD_ERR_CANT_WRITE
                );
            case UPLOAD_ERR_EXTENSION:
                throw new UploadException(
                    'A PHP extension stopped the file upload',
                    UPLOAD_ERR_CANT_WRITE
                );
            default:
                throw new UploadException(
                    'Upload file error',
                    $this->file['error']
                );
        }
    }

    protected function buildFile()
    {
        return new File(
            $this->file['tmp_name'],
            $this->file['name'],
            $this->file['size'],
            $this->file['type']
        );
    }

    /**
     * @param $targetPath
     * @return File
     */
    public function moveLocal($targetPath)
    {
        $sourceFile = $this->getFile();

        $targetFile = new File(
            $targetPath,
            $sourceFile->getOriginalBasename(),
            $sourceFile->getSize(),
            $sourceFile->getType()
        );

        move_uploaded_file(
            $sourceFile->getPath(),
            $targetPath
        );

        return $targetFile;
    }
}