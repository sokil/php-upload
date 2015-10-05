<?php 

namespace Sokil\Uploader\Transport;

class Stream extends AbstractTransport
{
    private $sourceStream;
    
    public function getOriginalBaseName() {
        if(!$this->originalBaseName) {
            $this->originalBaseName = empty($_GET[$this->fieldName]) ? uniqid() : $_GET[$this->fieldName];
        }
        return $this->originalBaseName;
    }
    
    public function getFileSize() {
        return empty($_SERVER['CONTENT_LENGTH']) ? null : (int) $_SERVER['CONTENT_LENGTH'];
    }
    
    protected function getSourceStream()
    {
        if(!$this->sourceStream) {
            $this->sourceStream = fopen('php://input', 'r');
        }
        
        return $this->sourceStream;
    }
    
    protected function closeSourceStream()
    {
        if($this->sourceStream) {
            fclose($this->sourceStream);
            $this->sourceStream = null;
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