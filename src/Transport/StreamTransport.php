<?php

namespace Sokil\Upload\Transport;

use Sokil\Upload\Exception\NoFileUploadedException;
use Sokil\Upload\Exception\PartialUploadException;
use Sokil\Upload\File;

class StreamTransport extends AbstractTransport
{
    protected function validate()
    {
        // check if file uploaded
        if (!$this->getFileSize()) {
            throw new NoFileUploadedException('No file uploaded');
        }
    }

    protected function buildFile()
    {
        return new File(
            'php://input',
            $this->getOriginalBasename(),
            $this->getFileSize(),
            'application/octet-stream'
        );
    }

    private function getOriginalBasename()
    {
        // try to get from query string
        if (!empty($_GET[$this->fieldName])) {
            return $_GET[$this->fieldName];
        }

        // try to get from header
        $filenameHeaderValue = $this->getHeader('X-Filename');
        if (!$filenameHeaderValue) {
            return $filenameHeaderValue;
        }

        // generate
        return uniqid();
    }

    private function getFileSize()
    {
        return empty($_SERVER['CONTENT_LENGTH'])
            ? null
            : (int) $_SERVER['CONTENT_LENGTH'];
    }

    /**
     * @param $targetPath
     * @return File
     * @throws PartialUploadException
     */
    public function moveLocal($targetPath)
    {
        // source stream
        $sourceFile = $this->getFile();
        $sourceFileReadStream = $sourceFile->getStream('r');

        // target stream
        $targetFile = new File(
            $targetPath,
            $sourceFile->getOriginalBasename(),
            $sourceFile->getSize(),
            $sourceFile->getType()
        );
        $targetFileWriteStream = $targetFile->getStream('w+');

        // move stream content
        $size = stream_copy_to_stream(
            $sourceFileReadStream,
            $targetFileWriteStream
        );

        // close resources
        fclose($sourceFileReadStream);
        fclose($targetFileWriteStream);

        // check copied file size
        if ($size !== $this->getFileSize()) {
            throw new PartialUploadException('Partial upload. Expected ' . $this->getFileSize() . ', found ' . $size);
        }

        return $targetFile;
    }
}