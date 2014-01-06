<?php

namespace Sokil\Uploader;

class Uploader
{
    private $_supportedFormats = array();
    
    private $_formFileFieldName = 'f';
    
    private $_dirPermission = 0777;

    public function __construct($options = array())
    {
        if(isset($options['supported_formats'])) {
            $this->setSupportedFormats($options['supported_formats']);
        }
    }
    
    public function setSupportedFormats(array $formats)
    {
        $this->_supportedFormats = array_map('strtolower', $formats);

        return $this;
    }

    public function upload($targetDir = null)
    {
        if(isset($_FILES[$this->_formFileFieldName])) {
            // iframe upload
            $file = $this->_formUpload($targetDir);
        }
        elseif(isset($_POST[$this->_formFileFieldName . '_tmp_name'])) {
            // iframe througn nginx's UploadModule
            $file = $this->_formThroughNginxUpload($targetDir);
            
        }
        elseif(isset($_GET[$this->_formFileFieldName])) {
            // Ajax upload
            $file = $this->_xhrUpload($targetDir);
        }
        else {
            throw new \Exception('No file');
        }

        return $file;
    }

    private function _xhrUpload($targetDir = null)
    {
        if(empty($_SERVER['CONTENT_LENGTH'])) {
            throw new \Exception('No file');
        }

        $originalFileName = $_GET[$this->_formFileFieldName];
        
        // test if format supported
        $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        if(count($this->_supportedFormats) && !in_array($ext, $this->_supportedFormats)) {
            throw new \Exception('File not allowed');
        }
        
        // prepare resources
        $stream = fopen('php://input', 'r');

        $tmpDir = ini_get('upload_tmp_dir');
        
        $tmpFileName = tempnam($tmpDir, 'xhr');
        chmod($tmpFileName, 0666);
        
        $tmpFile = fopen($tmpFileName, 'w+');

        // move stream
        $size = stream_copy_to_stream($stream, $tmpFile);
        if($size !== (int) $_SERVER['CONTENT_LENGTH']) {
            throw new \Exception('Partial upload. Expected ' . $_SERVER['CONTENT_LENGTH'] . ', found ' . $size);
        }
        
        fclose($stream);
        fclose($tmpFile);

        if(is_null($targetDir)) {
            $targetPath = $tmpFileName;
        }
        else {
            if(!file_exists($targetDir)) {
                mkdir($targetDir, $this->_dirPermission, true);
            }

            $targetFileName = pathinfo($originalFileName, PATHINFO_BASENAME);
            $targetPath = $targetDir . '/' . $targetFileName;
        
            copy($tmpFileName, $targetPath);
            unlink($tmpFileName);
        }

        return array(
            'path'      => $targetPath,
            'size'      => $size,
            'extension' => $ext,
            'original'  => $originalFileName
        );
    }

    public function _formUpload($targetDir = null)
    {
        $file = $_FILES[$this->_formFileFieldName];

        if($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception($file['error']);
        }

        // test if format supported
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(count($this->_supportedFormats) && !in_array($ext, $this->_supportedFormats)) {
            throw new Core_Uploader_FileNotAllowedException();
        }

        if(is_null($targetDir)) {
            $targetPath = $file['tmp_name'];
        }
        else {
            // move file
            if(!file_exists($targetDir)) {
                mkdir($targetDir, $this->_dirPermission, true);
            }
            
            $targetPath = $targetDir . '/' . $file['name'];
            move_uploaded_file($file['tmp_name'], $targetPath);
        }

        return array(
            'path'      => $targetPath,
            'size'      => $file['size'],
            'extension' => $ext,
            'original'  => $file['name']
        );
    }
    
    public function _formThroughNginxUpload($targetDir = null)
    {
        $fileName       = $_POST[$this->_formFileFieldName . '_name'];
        $fileTempName   = $_POST[$this->_formFileFieldName . '_tmp_name'];
        $fileType       = $_POST[$this->_formFileFieldName . '_type'];
        $fileSize       = $_POST[$this->_formFileFieldName . '_size'];
        
        // test if format supported
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if(count($this->_supportedFormats) && !in_array($ext, $this->_supportedFormats)) {
            throw new \Exception('File not allowed');
        }

        if(is_null($targetDir)) {
            $targetPath = $fileTempName;
        }
        else {
            // move file
            if(!file_exists($targetDir)) {
                mkdir($targetDir, $this->_dirPermission, true);
            }
            
            $targetPath = $targetDir . '/' . $fileName;
            rename($fileTempName, $targetPath);
        }

        return array(
            'path'      => $targetPath,
            'size'      => $fileSize,
            'extension' => $ext,
            'original'  => $fileName
        );
    }
}