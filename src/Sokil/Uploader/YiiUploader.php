<?php

namespace Sokil\Uploader;

class YiiUploader extends Uploader implements \IApplicationComponent
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

    /**
     * Register javascript and css files
     * 
     * @return \Sokil\Uploader\YiiUploader
     */
    public function registerScripts()
    {
        $path = \Yii::app()->getAssetManager()
            ->publish(__DIR__ . '/../../../js/');
        
        \Yii::app()->getClientScript()
            ->registerScriptFile($path . '/uploader.js');
        
        return $this;
    }
}