<?php

namespace Sokil\Upload;

use Sokil\Upload\Transport\AbstractTransport;

use Sokil\Upload\Exception\WrongChecksum;
use Sokil\Upload\Exception\WrongFormat;

class Handler
{
    const FILE_EXISTENCE_BEHAVIOR_RENAME    = 0;
    const FILE_EXISTENCE_BEHAVIOR_REPLACE   = 1;
    
    private $supportedFormats = array();
    
    private $fieldName = 'f';
    
    private $dirPermission = 0777;
    
    private $fileExistenceBehavior = self::FILE_EXISTENCE_BEHAVIOR_REPLACE;
    
    /**
     *
     * @var \Sokil\Upload\Transport\AbstractTransport
     */
    private $transport;
    
    private $lastUploadResult;

    public function __construct($options = array())
    {
        // supported formats option
        if(isset($options['supportedFormats'])) {
            $this->setSupportedFormats($options['supportedFormats']);
        }
        
        // file existence behavior option
        if(isset($options['fileExistanceBehavior'])) {
            switch($options['fileExistanceBehavior']) {
                case self::FILE_EXISTENCE_BEHAVIOR_RENAME:
                    $this->renameOnFileExistance();
                    break;
                default:
                case self::FILE_EXISTENCE_BEHAVIOR_REPLACE:
                    $this->replaceOnFileExistance();
                    break;
            }
        }
        
        // field name
        if(isset($options['fieldName'])) {
            $this->setFieldName($options['fieldName']);
        }
    }
    
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }
    
    public function setSupportedFormats(array $formats)
    {
        $this->supportedFormats = array_map('strtolower', $formats);

        return $this;
    }
    
    public function getSupportedFormats()
    {
        return $this->supportedFormats;
    }
    
    public function renameOnFileExistance()
    {
        $this->fileExistenceBehavior = self::FILE_EXISTENCE_BEHAVIOR_RENAME;
        return $this;
    }
    
    public function replaceOnFileExistance()
    {
        $this->fileExistenceBehavior = self::FILE_EXISTENCE_BEHAVIOR_REPLACE;
        return $this;
    }

    /**
     * Get transport. If no transport specified by self::setTransport(),
     * system will try to detect in automatically
     * 
     * @return \Sokil\Upload\Transport\AbstractTransport
     * @throws \Exception
     */
    protected function getTransport()
    {
        if($this->transport) {
            return $this->transport;
        }

        $transportClassName = '\\Sokil\\Upload\\Transport\\' . $this->getTransportName() . 'Transport';
        if (!class_exists($transportClassName)) {
            throw new GenericUploadException('Wrong transport passed');
        }
        
        /* @var $transport \Sokil\Upload\Transport\AbstractTransport */
        $this->transport = new $transportClassName($this->fieldName);
        
        return $this->transport;
    }
    
    public function getTransportName()
    {
        if(isset($_FILES[$this->fieldName])) {
            // iframe upload
            $transportName = 'MultipartFormData';
        } elseif(isset($_POST[$this->fieldName . '_tmp_name'])) {
            // iframe througn nginx's UploadModule
            $transportName = 'Nginx';
        } else {
            // Ajax upload to input stream
            $transportName = 'Stream';
        }
        
        return $transportName;
    }
    
    /**
     * Specify user defined transport class
     * 
     * @param \Sokil\Upload\Transport\AbstractTransport $transport
     * @return \Sokil\Upload\Uploader
     */
    public function setTransport(AbstractTransport $transport)
    {
        $this->transport = $transport;
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
        } elseif(!file_exists($targetDir)) {
            $oldUmast = umask(0);
            mkdir($targetDir, $this->dirPermission, true);
            umask($oldUmast);
        }
        
        // remove relative sections
        $targetDir = realpath($targetDir);
        
        // get original basename
        $originalBaseName = $transport->getOriginalBaseName();
        
        // test if format supported
        $ext = strtolower(pathinfo($originalBaseName, PATHINFO_EXTENSION));
        if(count($this->supportedFormats) && !in_array($ext, $this->supportedFormats)) {
            throw new WrongFormat('File not allowed');
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
        if(self::FILE_EXISTENCE_BEHAVIOR_RENAME === $this->fileExistenceBehavior) {
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
                throw new WrongChecksum('MD5 sum missmatch. Expected ' . $expectedMD5 . ', actual ' . $actualMD5);
            }
        }
        
        // set upload status
        $this->lastUploadResult = new Result(
            $targetPath,
            $transport->getFileSize(),
            $ext,
            $originalBaseName
        );
        
        return $this;
    }

    /**
     * @return Result
     */
    public function getLastUploadResult()
    {
        return $this->lastUploadResult;
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