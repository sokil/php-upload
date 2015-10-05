<?php 

namespace Sokil\Uploader\Transport;

/**
 * This transport used on large files upload (more than 2 GB).
 * 
 * On standart upload file moved to php's temp dir, and then moved to target 
 * destinarion using move_uploaded_file. If this dirs on different 
 * physical devises, some time will spend to move file physically between devices.
 * 
 * There is another reason to use this transport on nginx + php-fpm stack.
 * During upload nginx cached file to its own temp dir. After passing control to 
 * php-fpm, nginx moves cached file to php's temp dir, and than php moves file 
 * to destination using move_uploaded_file. So file comied three times, and
 * maybe on different physical devices.
 * 
 * This transport moves file directly to required device, so in php code only
 * rename to destination required.   
 * 
 * Nginx configuration:
 * 
 *  upload_progress {UPLOAD_PROGRESS_NAME} 5m;
 * 
 *  # php handler which execute requests to php files
 *  location @php {
 *      fastcgi_pass 127.0.0.1:9000;
 *      fastcgi_param SCRIPT_FILENAME $document_root/index.php;
 *      include /usr/local/etc/nginx/fastcgi_params;
 *  }
 *  
 *  # location which handles upload and pass params of upload to php handler
 *  location /upload {
 *      client_max_body_size 20000m;
 *  
 *      upload_pass @php;
 *      upload_store {PATH_TO_STORAGE_DIR};
 *      upload_pass_args on;
 *      upload_max_file_size 0;
 * 
 *      upload_set_form_field $upload_field_name.name "$upload_file_name";
 *      upload_set_form_field $upload_field_name.type "$upload_content_type";
 *      upload_set_form_field $upload_field_name.tmp_name "$upload_tmp_path";
 *  
 *      upload_aggregate_form_field $upload_field_name.size "$upload_file_size";
 *  
 *      track_uploads {UPLOAD_PROGRESS_NAME} 5s;
 *  }
 * 
 *  # upload progress location, which returns state of upload
 *  location /uploadprogress {
 *      report_uploads {UPLOAD_PROGRESS_NAME};
 *  }
 */
class NginxForm extends AbstractTransport
{
    private $file;
    
    public function __construct($fieldName) {
        parent::__construct($fieldName);
        $this->file = $_FILES[$this->fieldName];
    }
    
    public function getOriginalBaseName() {
        if(!$this->originalBaseName) {
            $this->originalBaseName = $_POST[$this->fieldName . '_name'];
        }
        
        return $this->originalBaseName;
    }
    
    public function getFileSize() {
        return (int) $_POST[$this->fieldName . '_size'];
    }
    
    public function getFileType()
    {
        return $_POST[$this->fieldName . '_type'];
    }
    
    public function upload($targetPath)
    {
        rename($_POST[$this->fieldName . '_tmp_name'], $targetPath);
    }
}