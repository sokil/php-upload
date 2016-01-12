<?php

namespace Sokil\Upload;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use \Sokil\Upload\Handler;

class UploadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Sokil\Upload\Handler
     */
    private $uploadHandler;

    private static $originalBaseName;

    private static $originalFileSize;

    public static function setUpBeforeClass()
    {
        self::$originalBaseName = 'originalBaseName.ext';
        $_GET['f'] = self::$originalBaseName;

        self::$originalFileSize = filesize(__FILE__);
        $_SERVER['CONTENT_LENGTH'] = self::$originalFileSize;
    }

    public function setUp() {

        // create uploader
        $this->uploadHandler = new Handler(array(
            'transport' => 'Stream',
            'fieldName' => 'f',
        ));

        $transport = $this->getMock(
            '\Sokil\Upload\Transport\StreamTransport',
            array('getFile'),
            array(
                'fieldName' => 'f',
            )
        );

        $transport
            ->expects($this->any())
            ->method('getFile')
            ->will($this->returnValue(
                new File(
                    __FILE__,
                    self::$originalBaseName,
                    self::$originalFileSize,
                    'application/octet-stream'
                )
            ));

        $this->uploadHandler->setTransport($transport);
    }
    
    public function testMoveLocal_DefinedDir_ChangedFileName()
    {
        $targetDir = sys_get_temp_dir() . '/test_dir';
        $targetFileName = 'uploadedTestFile';
    
        $expectedExtension = pathinfo(self::$originalBaseName, PATHINFO_EXTENSION);
        $expectedPath = $targetDir . '/' . $targetFileName . '.' . $expectedExtension;

        // upload to defined dir and change file name
        $file = $this
            ->uploadHandler
            ->moveLocal($targetDir, $targetFileName);

        $this->assertInstanceOf('\Sokil\Upload\File', $file);
        $this->assertEquals($expectedPath, $file->getPath());
        $this->assertEquals(self::$originalFileSize, $file->getSize());
        $this->assertEquals($expectedExtension, $file->getExtension());
        $this->assertEquals(self::$originalBaseName, $file->getOriginalBasename());
        
        unlink($expectedPath);
        rmdir($targetDir);
    }
    
    public function testMoveLocal_UndefinedDir_ChangedFileName()
    {
        $targetFileName = 'uploadedTestFile';

        $expectedExtension = pathinfo(self::$originalBaseName, PATHINFO_EXTENSION);
        $expectedPath = $this->uploadHandler->getDefaultLocalUploadDirectory() . '/' . $targetFileName . '.' . $expectedExtension;

        // upload to defined dir and change file name
        $file = $this->uploadHandler->moveLocal(null, $targetFileName);

        $this->assertInstanceOf('\Sokil\Upload\File', $file);
        $this->assertEquals($expectedPath, $file->getPath());
        $this->assertEquals(self::$originalFileSize, $file->getSize());
        $this->assertEquals($expectedExtension, $file->getExtension());
        $this->assertEquals(self::$originalBaseName, $file->getOriginalBasename());
        
        unlink($expectedPath);
    }
    
    public function testMoveLocal_DefinedDir_OriginalFileName()
    {
        $targetDir = sys_get_temp_dir() . '/test_dir';

        $expectedExtension = pathinfo(self::$originalBaseName, PATHINFO_EXTENSION);
        $expectedPath = $targetDir . '/' . self::$originalBaseName;
        
        // upload to defined dir and leave original filename
        $file = $this->uploadHandler->moveLocal($targetDir);

        $this->assertInstanceOf('\Sokil\Upload\File', $file);
        $this->assertEquals($expectedPath, $file->getPath());
        $this->assertEquals(self::$originalFileSize, $file->getSize());
        $this->assertEquals($expectedExtension, $file->getExtension());
        $this->assertEquals(self::$originalBaseName, $file->getOriginalBasename());
        
        unlink($expectedPath);
        rmdir($targetDir);
    }
    
    public function testMoveLocal_UndefinedDir_OriginalFileName()
    {
        $expectedExtension = pathinfo(self::$originalBaseName, PATHINFO_EXTENSION);
        $expectedPath = $this->uploadHandler->getDefaultLocalUploadDirectory() . '/' . self::$originalBaseName;
        
        // upload to defined dir and leave original filename
        $file = $this->uploadHandler->moveLocal();

        $this->assertInstanceOf('\Sokil\Upload\File', $file);
        $this->assertEquals($expectedPath, $file->getPath());
        $this->assertEquals(self::$originalFileSize, $file->getSize());
        $this->assertEquals($expectedExtension, $file->getExtension());
        $this->assertEquals(self::$originalBaseName, $file->getOriginalBasename());
        
        unlink($expectedPath);
    }

    public function testMove_DefinedDir_ChangedFileName()
    {
        $expectedExtension = pathinfo(self::$originalBaseName, PATHINFO_EXTENSION);

        $targetDir = sys_get_temp_dir() . '/test_dir';
        $targetFileName = 'uploadedTestFile';
        $targetBaseName = $targetFileName . '.' . $expectedExtension;

        $expectedPath = $targetDir . '/' . $targetFileName . '.' . $expectedExtension;

        // upload to defined dir and change file name
        $file = $this
            ->uploadHandler
            ->move(
                new Filesystem(new Local($targetDir, true)),
                $targetFileName
            );

        $this->assertInstanceOf('\Gaufrette\File', $file);
        $this->assertEquals($expectedPath, $targetDir . '/' . $file->getName());
        $this->assertEquals(self::$originalFileSize, $file->getSize());
        $this->assertEquals($expectedExtension, pathinfo($file->getName(), PATHINFO_EXTENSION));
        $this->assertEquals($targetBaseName, $file->getName());

        unlink($expectedPath);
        rmdir($targetDir);
    }
}