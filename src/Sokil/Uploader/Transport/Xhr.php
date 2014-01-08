<?php 

namespace Sokil\Uploader\Transport;

class Xhr extends AbstractTransport
{
    private $_sourceStream;
    
    public function getOriginalBaseName() {
        return $_GET[$this->_fieldName];
    }
    
    public function getFileSize() {
        return empty($_SERVER['CONTENT_LENGTH']) ? null : (int) $_SERVER['CONTENT_LENGTH'];
    }
    
    protected function getSourceStream()
    {
        if(!$this->_sourceStream) {
            $this->_sourceStream = fopen('php://input', 'r');
        }
        
        return $this->_sourceStream;
    }
    
    protected function closeSourceStream()
    {
        if($this->_sourceStream) {
            fclose($this->_sourceStream);
            $this->_sourceStream = null;
        }
        
        return $this;
    }
    
    public function upload($targetPath)
    {
        // check if file uploaded
        if(!$this->getFileSize()) {
            throw new \Exception('No file');
        }
        
        // open target stream
        $targetFileStream = fopen($targetPath, 'w');

        // move stream
        $size = stream_copy_to_stream($this->getSourceStream(), $targetFileStream);
        
        // close resources
        $this->closeSourceStream();
        fclose($targetFileStream);
        
        if($size !== $this->getFileSize()) {
            throw new \Exception('Partial upload. Expected ' . $this->getFileSize() . ', found ' . $size);
        }
        
    }
}