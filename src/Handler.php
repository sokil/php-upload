<?php

namespace Sokil\Upload;

use Gaufrette\Filesystem;
use Sokil\Upload\Exception\WrongChecksum;
use Sokil\Upload\Exception\WrongFormat;
use Sokil\Upload\Transport\AbstractTransport;

class Handler
{
    private $options = array(
        'transport'             => null,
        'supportedFormats'      => array(),
        'fieldName'             => 'file',
        'validateChecksum'      => false,
    );

    /**
     *
     * @var \Sokil\Upload\Transport\AbstractTransport
     */
    private $transport;

    public function __construct($options = array())
    {
        // configure field name
        if (isset($options['fieldName'])) {
            $this->options['fieldName'] = $options['fieldName'];
        }
        
        $fieldName = $this->options['fieldName'];

        // detect transport
        if (!isset($options['transport'])) {
            if (isset($_FILES[$fieldName])) {
                // iframe upload
                $this->options['transport'] = 'MultipartFormData';
            } elseif (isset($_POST[$fieldName . '_tmp_name'])) {
                // iframe through nginx's UploadModule
                $this->options['transport'] = 'Nginx';
            } else {
                // Ajax upload to input stream
                $this->options['transport'] = 'Stream';
            }
        } else {
            $this->options['transport'] = $options['transport'];
        }

        // supported formats option
        if (isset($options['supportedFormats']) && is_array($options['supportedFormats'])) {
            $this->options['supportedFormats'] = array_map('strtolower', $options['supportedFormats']);
        }

        // checksum
        if (isset($options['validateChecksum'])) {
            $this->options['validateChecksum'] = (bool) $options['validateChecksum'];
        }
    }
    
    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;    
    }

    /**
     * @param AbstractTransport $transport
     * @return $this
     */
    public function setTransport(AbstractTransport $transport)
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * Get uploaded file instance
     * 
     * @return \Sokil\Upload\Transport\AbstractTransport
     * @throws \Exception
     */
    private function getTransport()
    {
        if ($this->transport) {
            return $this->transport;
        }

        $transportClassName = '\\Sokil\\Upload\\Transport\\' . $this->options['transport'] . 'Transport';

        $this->transport = new $transportClassName(
            $this->options['fieldName'],
            $this->options['supportedFormats'],
            $this->options['validateChecksum']
        );
        
        return $this->transport;
    }
    
    public function getDefaultLocalUploadDirectory()
    {
        // get configured upload dir frpm php.ini
        $tempDir = ini_get('upload_tmp_dir');
            
        // upload temp dir not configured - use system default
        if (!$tempDir) {
            $tempDir = sys_get_temp_dir();
        }

        return $tempDir;
    }

    /**
     * Get source file
     * @return File
     * @throws Exception\WrongChecksumException
     * @throws Exception\WrongFormatException
     */
    public function getFile()
    {
        return $this->getTransport()->getFile();
    }

    private function buildTargetBasename(File $sourceFile, $targetFilename = null)
    {
        if ($targetFilename) {
            $extension = $sourceFile->getExtension();
            if ($extension) {
                $targetBasename = $targetFilename . '.' . $extension;
            } else {
                $targetBasename = $targetFilename;
            }
        } else {
            $targetBasename = $sourceFile->getOriginalBasename();
        }

        return $targetBasename;
    }

    /**
     * Move file to local filesystem
     *
     * @param string $targetDir File system to store file. If omitted - store in php's upload_tmp_dir
     * @param string $targetFilename New file name. If omitted - use original filename
     * @return \Sokil\Upload\File uploaded file
     */
    public function moveLocal($targetDir = null, $targetFilename = null)
    {
        // source file
        $sourceFile = $this->getTransport()->getFile();

        // target base name
        $targetBasename = $this->buildTargetBasename($sourceFile, $targetFilename);

        // target path
        if (!$targetDir) {
            $targetDir = $this->getDefaultLocalUploadDirectory();
        }
        $targetPath = $targetDir . '/' . $targetBasename;

        // if target dir not exists - create it
        if (!file_exists($targetDir)) {
            $oldUmast = umask(0);
            mkdir($targetDir, 0777, true);
            umask($oldUmast);
        }

        // move file to target dir
        return $this
            ->getTransport()
            ->moveLocal($targetPath);
    }

    /**
     * Move file to external filesystem
     *
     * @param Filesystem $filesystem File system to store file. If omitted - store in php's upload_tmp_dir
     * @param string $targetFilename New file name. If omitted - use original filename
     * @return \Gaufrette\File
     */
    public function move(Filesystem $filesystem = null, $targetFilename = null, $overwrite = true)
    {
        // source file
        $sourceFile = $this->getTransport()->getFile();
        
        // target base name
        $targetBasename = $this->buildTargetBasename($sourceFile, $targetFilename);

        // move file to target storage
        $content = stream_get_contents($sourceFile->getStream());

        // write file
        $filesystem->write(
            $targetBasename,
            $content,
            $overwrite
        );

        return $filesystem->get($targetBasename);
    }
}
