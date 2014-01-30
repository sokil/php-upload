<?php

namespace Sokil\Uploader;

use \Sokil\Uploader\Transport\AbstractTransport;

class Uploader
{
    const FILE_EXISTANCE_BEHAVIOR_RENAME    = 0;
    const FILE_EXISTANCE_BEHAVIOR_REPLACE   = 1;
    
    private $_supportedFormats = array();
    
    private $_fieldName = 'f';
    
    private $_dirPermission = 0777;
    
    private $_fileExistanceBehavior = self::FILE_EXISTANCE_BEHAVIOR_REPLACE;
    
    /**
     *
     * @var \Sokil\Uploader\Transport\AbstractTransport
     */
    private $_transport;
    
    private $_lastUploadStatus;

    public function __construct($options = array())
    {
        // supported formats option
        if(isset($options['supportedFormats'])) {
            $this->setSupportedFormats($options['supportedFormats']);
        }
        
        // file existence behavior option
        if(isset($options['fileExistanceBehavior'])) {
            switch($options['fileExistanceBehavior']) {
                case self::FILE_EXISTANCE_BEHAVIOR_RENAME:
                    $this->renameOnFileExistance();
                    break;
                default:
                case self::FILE_EXISTANCE_BEHAVIOR_REPLACE:
                    $this->replaceOnFileExistance();
                    break;
            }
        }
    }
    
    public function setSupportedFormats(array $formats)
    {
        $this->_supportedFormats = array_map('strtolower', $formats);

        return $this;
    }
    
    public function getSupportedFormats()
    {
        return $this->_supportedFormats;
    }
    
    public function renameOnFileExistance()
    {
        $this->_fileExistanceBehavior = self::FILE_EXISTANCE_BEHAVIOR_RENAME;
        return $this;
    }
    
    public function replaceOnFileExistance()
    {
        $this->_fileExistanceBehavior = self::FILE_EXISTANCE_BEHAVIOR_REPLACE;
        return $this;
    }

    /**
     * Get transport. If no transport specified by self::setTransport(),
     * system will try to detect in automatically
     * 
     * @return \Sokil\Uploader\Transport\AbstractTransport
     * @throws \Exception
     */
    protected function getTransport()
    {
        if($this->_transport) {
            return $this->_transport;
        }
        
        // iframe upload
        if(isset($_FILES[$this->_fieldName])) {
            $transportName = 'Iframe';
        }
        // iframe througn nginx's UploadModule
        elseif(isset($_POST[$this->_fieldName . '_tmp_name'])) {
            $transportName = 'Nginx';
        }
        // Ajax upload
        else {
            $transportName = 'Xhr';
        }
        
        $transportClassName = '\\Sokil\\Uploader\\Transport\\' . $transportName;
        
        /* @var $transport \Sokil\Uploader\Transport\AbstractTransport */
        $this->_transport = new $transportClassName($this->_fieldName);
        
        return $this->_transport;
    }
    
    /**
     * Specify user defined transport class
     * 
     * @param \Sokil\Uploader\Transport\AbstractTransport $transport
     * @return \Sokil\Uploader\Uploader
     */
    public function setTransport(AbstractTransport $transport)
    {
        $this->_transport = $transport;
        return $this;
    }
    
    public function getDefaultUploadDirectory()
    {
        // get configured upload dir frpm php.ini
        $tempDir = ini_get('upload_tmp_dir');
            
        // upload temp dir not configured - use system default
        if(!$tempDir) {
            $tempDir = sys_get_temp_dir();
        }
        
        return $tempDir;
    }
    
    public function setOriginalBaseName($baseName)
    {
        $this->getTransport()->setOriginalBaseName($baseName);
        return $this;
    }
    
    /**
     * 
     * @param string $targetDir Dir to store file. If omited - store in php's upload_tmp_dir
     * @param string $newFileName New file name. If omited - use original filename
     * @return array uploaded file metadata
     * @throws \Exception
     */
    public function upload($targetDir = null, $targetFileName = null)
    {
        $transport = $this->getTransport();
        
        // get target dir
        if(!$targetDir) {
            $targetDir = $this->getDefaultUploadDirectory();
        }
        elseif(!file_exists($targetDir)) {
            $oldUmast = umask(0);
            mkdir($targetDir, $this->_dirPermission, true);    
            umask($oldUmast);
        }
        
        // remove relative sections
        $targetDir = realpath($targetDir);
        
        // get original basename
        $originalBaseName = $transport->getOriginalBaseName();
        
        // test if format supported
        $ext = strtolower(pathinfo($originalBaseName, PATHINFO_EXTENSION));
        if(count($this->_supportedFormats) && !in_array($ext, $this->_supportedFormats)) {
            throw new \Sokil\Uploader\Exception\WrongFormat('File not allowed');
        }
        
        // get target file name
        if(!$targetFileName) {
            $targetFileName = pathinfo($originalBaseName, PATHINFO_FILENAME);
        }
        
        /**
         * Сheck target path existance
         */
        
        $targetPath = $targetDir . '/' . $targetFileName . '.' . $ext;
        
        // rename file
        if(self::FILE_EXISTANCE_BEHAVIOR_RENAME === $this->_fileExistanceBehavior) {
            $i = 0;
            while(false === ($targetFileStream = @fopen($targetPath, 'x'))) {
                $targetPath = $targetDir . '/' . $targetFileName . ++$i . '.' . $ext;
            }
            fclose($targetFileStream);
        }
        
        $transport->upload($targetPath);
        
        // check MD5 hash
        $expectedMD5 = $this->getHeader('Content-MD5');
        if($expectedMD5) {
            $actualMD5 = base64_encode(md5_file($targetPath, true));
            if(rtrim($expectedMD5, '=') !== rtrim($actualMD5, '=')) {
                throw new \Sokil\Uploader\Exception\WrongChecksum('MD5 sum missmatch. Expected ' . $expectedMD5 . ', actual ' . $actualMD5);            
            }
        }
        
        // set upload status
        $this->_lastUploadStatus = array(
            'path'      => $targetPath,
            'size'      => $transport->getFileSize(),
            'extension' => $ext,
            'original'  => $originalBaseName
        );
        
        return $this;
    }
    
    public function getLastUploadStatus()
    {
        return $this->_lastUploadStatus;
    }
    
    protected function getHeader($headerName, $default = null)
    {
        $serverVarKey = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        if(isset($_SERVER[$serverVarKey])) {
            return $_SERVER[$serverVarKey];
        }

        if(function_exists('apache_request_headers')) {
            $headers = apache_request_headers();

            if(isset($headers[$headerName])) {
                return $headers[$headerName];
            }

            $headerName = strtolower($headerName);
            foreach($headers as $key => $value) {
                if(strtolower($key) == $headerName) {
                    return $value;
                }
            }
        }

        return $default;
    }
    
}