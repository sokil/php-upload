<?php

namespace Sokil\Upload\Transport;

use Sokil\Upload\Exception\WrongFormatException;
use Sokil\Upload\File;

use Sokil\Upload\Exception\WrongChecksumException;

abstract class AbstractTransport
{
    protected $fieldName;

    private $supportedFormats;

    private $isChecksumValidationAllowed;

    /**
     * @var File
     */
    private $file;

    public function __construct(
        $fieldName,
        array $supportedFormats = array(),
        $isChecksumValidationAllowed = false
    ) {
        $this->fieldName = $fieldName;
        $this->supportedFormats = $supportedFormats;
        $this->isChecksumValidationAllowed = (bool) $isChecksumValidationAllowed;
        $this->init();
    }

    public function init() {}

    /**
     * @return File
     */
    public function getFile()
    {
        if (!empty($this->file)) {
            return $this->file;
        }

        // validate
        $this->validate();

        // build file
        $file = $this->buildFile();

        // test if format supported
        if (!empty($this->supportedFormats) && !in_array($file->getExtension(), $this->supportedFormats)) {
            throw new WrongFormatException('File type not allowed');
        }

        // check checksum
        if ($this->isChecksumValidationAllowed) {
            if ($this->getExpectedChecksum() !== $file->getChecksum()) {
                throw new WrongChecksumException('Checksum missmatch');
            }
        }

        $this->file = $file;

        return $this->file;
    }

    /**
     * @return void
     */
    abstract protected function validate();

    /**
     * @return File
     */
    abstract protected function buildFile();

    /**
     * Get expected checksum
     *
     * @link https://tools.ietf.org/html/rfc1864
     * @return string checksum
     */
    public function getExpectedChecksum()
    {
        return $this->getHeader('Content-MD5');
    }

    protected function getHeader($headerName, $default = null)
    {
        $serverVarKey = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        if (isset($_SERVER[$serverVarKey])) {
            return $_SERVER[$serverVarKey];
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();

            if (isset($headers[$headerName])) {
                return $headers[$headerName];
            }

            $headerName = strtolower($headerName);
            foreach ($headers as $key => $value) {
                if(strtolower($key) == $headerName) {
                    return $value;
                }
            }
        }

        return $default;
    }

    /**
     * @param $targetPath
     * @return File
     */
    abstract public function moveLocal($targetPath);
}
