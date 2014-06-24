<?php

namespace Sokil\Uploader;

class UploaderTest extends \PHPUnit_Framework_TestCase
{
    private $_uploader;
    
    public function setUp() {
        
        // create uploader
        $this->_uploader = new \Sokil\Uploader\Uploader;
        
        // prepare transport
        $transport = $this->getMock('\Sokil\Uploader\Transport\Stream', array('getSourceStream'), array('f'));
        $transport
            ->expects($this->any())
            ->method('getSourceStream')
            ->will($this->returnValue(
                fopen(__FILE__, 'r')
            ));
        
        $this->_uploader->setTransport($transport);
    }
    
    public function testXhrUploadToDefinedDirAndChangeFileName()
    {
        $originalBaseName = 'originalBaseName.ext';
        $fileSize = filesize(__FILE__);
        
        $targetDir = sys_get_temp_dir() . '/test_dir';
        $targetFileName = 'uploadedTestFile';
    
        $expectedPath = $targetDir . '/' . $targetFileName . '.ext';
        
        // define environmanet
        $_GET['f'] = $originalBaseName;
        $_SERVER['CONTENT_LENGTH'] = $fileSize;

        // upload to defined dir and change file name
        $this->_uploader->upload($targetDir, $targetFileName);
        
        $this->assertEquals(array(
            'path'      => $expectedPath,
            'size'      => $fileSize,
            'extension' => 'ext',
            'original'  => 'originalBaseName.ext'
        ), $this->_uploader->getLastUploadStatus());
        
        unlink($expectedPath);
        rmdir($targetDir);
    }
    
    public function testXhrUploadToUndefinedDirAndChangeFileName()
    {
        $originalBaseName = 'originalBaseName.ext';
        $fileSize = filesize(__FILE__);
        
        $targetFileName = 'uploadedTestFile';
        
        $expectedPath = $this->_uploader->getDefaultUploadDirectory() . '/' . $targetFileName . '.ext';
    
        // define environmanet
        $_GET['f'] = $originalBaseName;
        $_SERVER['CONTENT_LENGTH'] = $fileSize;

        // upload to defined dir and change file name
        $this->_uploader->upload(null, $targetFileName);
        
        
        $this->assertEquals(array(
            'path'      => $expectedPath,
            'size'      => $fileSize,
            'extension' => 'ext',
            'original'  => 'originalBaseName.ext'
        ), $this->_uploader->getLastUploadStatus());
        
        unlink($expectedPath);
    }
    
    public function testXhrUploadToDefinedDirAndLeaveOriginalFileName()
    {
        $originalBaseName = 'originalBaseName.ext';
        $fileSize = filesize(__FILE__);
        
        $targetDir = sys_get_temp_dir() . '/test_dir';
    
        $expectedPath = $targetDir . '/originalBaseName.ext';
        
        // define environmanet
        $_GET['f'] = $originalBaseName;
        $_SERVER['CONTENT_LENGTH'] = $fileSize;
        
        // upload to defined dir and leave original filename
        $this->_uploader->upload($targetDir);
        
        $this->assertEquals(array(
            'path'      => $expectedPath,
            'size'      => $fileSize,
            'extension' => 'ext',
            'original'  => 'originalBaseName.ext'
        ), $this->_uploader->getLastUploadStatus());
        
        unlink($expectedPath);
        rmdir($targetDir);
    }
    
    public function testXhrUploadToUndefinedDirAndLeaveOriginalFileName()
    {
        $originalBaseName = 'originalBaseName.ext';
        $fileSize = filesize(__FILE__);
    
        $expectedPath = $this->_uploader->getDefaultUploadDirectory() . '/originalBaseName.ext';
        
        // define environmanet
        $_GET['f'] = $originalBaseName;
        $_SERVER['CONTENT_LENGTH'] = $fileSize;
        
        // upload to defined dir and leave original filename
        $this->_uploader->upload();
        
        $this->assertEquals(array(
            'path'      => $expectedPath,
            'size'      => $fileSize,
            'extension' => 'ext',
            'original'  => 'originalBaseName.ext'
        ), $this->_uploader->getLastUploadStatus());
        
        unlink($expectedPath);
    }
    
    public function testUploadToRelativelySpecifiedDir()
    {
        $originalBaseName = 'originalBaseName.ext';
        $fileSize = filesize(__FILE__);
        
        $targetDir = sys_get_temp_dir() . '/test_dir';
        
        $targetFileName = 'uploadedTestFile';
    
        $expectedPath = $targetDir . '/' . $targetFileName . '.ext';
        
        // define environmanet
        $_GET['f'] = $originalBaseName;
        $_SERVER['CONTENT_LENGTH'] = $fileSize;

        // upload to defined dir and change file name
        $this->_uploader->upload($targetDir . '/./', $targetFileName);
        
        $this->assertEquals(array(
            'path'      => $expectedPath,
            'size'      => $fileSize,
            'extension' => 'ext',
            'original'  => 'originalBaseName.ext'
        ), $this->_uploader->getLastUploadStatus());
        
        unlink($expectedPath);
        rmdir($targetDir);
    }
}