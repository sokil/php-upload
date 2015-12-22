<?php

namespace Sokil\Uploader;

use Sokil\Upload\Handler as UploadHandler;

class UploadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Sokil\Upload\Handler
     */
    private $uploadHandler;
    
    public function setUp() {
        
        // create uploader
        $this->uploadHandler = new UploadHandler;
        
        // prepare transport
        $transport = $this->getMock('\Sokil\Upload\Transport\StreamTransport', array('getSourceStream'), array('f'));
        $transport
            ->expects($this->any())
            ->method('getSourceStream')
            ->will($this->returnValue(
                fopen(__FILE__, 'r')
            ));
        
        $this->uploadHandler->setTransport($transport);
    }
    
    public function testXhrUploadToDefinedDirAndChangeFileName()
    {
        $originalBaseName = 'originalBaseName.ext';
        $fileSize = filesize(__FILE__);
        
        $targetDir = sys_get_temp_dir() . '/test_dir';
        $targetFileName = 'uploadedTestFile';
    
        $expectedPath = $targetDir . '/' . $targetFileName . '.ext';
        
        // define environmenet
        $_GET['f'] = $originalBaseName;
        $_SERVER['CONTENT_LENGTH'] = $fileSize;

        // upload to defined dir and change file name
        $this->uploadHandler->upload($targetDir, $targetFileName);

        $result = $this->uploadHandler->getLastUploadResult();

        $this->assertEquals($expectedPath, $result->getPath());
        $this->assertEquals($fileSize, $result->getSize());
        $this->assertEquals('ext', $result->getExtension());
        $this->assertEquals('originalBaseName.ext', $result->getOriginalFilename());
        
        unlink($expectedPath);
        rmdir($targetDir);
    }
    
    public function testXhrUploadToUndefinedDirAndChangeFileName()
    {
        $originalBaseName = 'originalBaseName.ext';
        $fileSize = filesize(__FILE__);
        
        $targetFileName = 'uploadedTestFile';
        
        $expectedPath = $this->uploadHandler->getDefaultUploadDirectory() . '/' . $targetFileName . '.ext';
    
        // define environmanet
        $_GET['f'] = $originalBaseName;
        $_SERVER['CONTENT_LENGTH'] = $fileSize;

        // upload to defined dir and change file name
        $this->uploadHandler->upload(null, $targetFileName);

        $result = $this->uploadHandler->getLastUploadResult();

        $this->assertEquals($expectedPath, $result->getPath());
        $this->assertEquals($fileSize, $result->getSize());
        $this->assertEquals('ext', $result->getExtension());
        $this->assertEquals('originalBaseName.ext', $result->getOriginalFilename());
        
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
        $this->uploadHandler->upload($targetDir);

        $result = $this->uploadHandler->getLastUploadResult();

        $this->assertEquals($expectedPath, $result->getPath());
        $this->assertEquals($fileSize, $result->getSize());
        $this->assertEquals('ext', $result->getExtension());
        $this->assertEquals('originalBaseName.ext', $result->getOriginalFilename());
        
        unlink($expectedPath);
        rmdir($targetDir);
    }
    
    public function testXhrUploadToUndefinedDirAndLeaveOriginalFileName()
    {
        $originalBaseName = 'originalBaseName.ext';
        $fileSize = filesize(__FILE__);
    
        $expectedPath = $this->uploadHandler->getDefaultUploadDirectory() . '/originalBaseName.ext';
        
        // define environmanet
        $_GET['f'] = $originalBaseName;
        $_SERVER['CONTENT_LENGTH'] = $fileSize;
        
        // upload to defined dir and leave original filename
        $this->uploadHandler->upload();

        $result = $this->uploadHandler->getLastUploadResult();

        $this->assertEquals($expectedPath, $result->getPath());
        $this->assertEquals($fileSize, $result->getSize());
        $this->assertEquals('ext', $result->getExtension());
        $this->assertEquals('originalBaseName.ext', $result->getOriginalFilename());
        
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
        $this->uploadHandler->upload($targetDir . '/./', $targetFileName);

        $result = $this->uploadHandler->getLastUploadResult();

        $this->assertEquals($expectedPath, $result->getPath());
        $this->assertEquals($fileSize, $result->getSize());
        $this->assertEquals('ext', $result->getExtension());
        $this->assertEquals('originalBaseName.ext', $result->getOriginalFilename());
        
        unlink($expectedPath);
        rmdir($targetDir);
    }
}