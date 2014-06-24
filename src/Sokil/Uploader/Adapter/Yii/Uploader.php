<?php

namespace Sokil\Uploader\Adapter\Yii;

class Uploader extends \Sokil\Uploader\Uploader implements \IApplicationComponent
{
    private $_initialized = false;
    
    /**
     * Initializes the application component.
     * This method is invoked after the application completes configuration.
     */
    public function init()
    {
        $this->_initialized = true;
    }
    
    /**
     * @return boolean whether the {@link init()} method has been invoked.
     */
    public function getIsInitialized()
    {
        return $this->_initialized;
    }
}