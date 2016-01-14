<?php 

namespace Sokil\Upload\Transport;

/**
 * This file used on large files upload (more than 2 GB).
 * 
 * On standard upload file moved to php's temp dir, and then moved to target
 * destination using move_uploaded_file. If this dirs on different
 * physical devises, some time will spend to move file physically between devices.
 * 
 * There is another reason to use nginx + php-fpm stack.
 * During upload nginx cached file to its own temp dir. After passing control to 
 * php-fpm, nginx moves cached file to php's temp dir, and than php moves file 
 * to destination using move_uploaded_file. So file copied three times, and
 * maybe on different physical devices.
 * 
 * This method moves file directly to required device, so in php code only
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
class NginxTransport extends AbstractTransport
{
    protected function validate()
    {
        if (empty($_POST[$this->fieldName . '_size'])) {
            throw new NoFileUploadedException(
                'No file was uploaded',
                UPLOAD_ERR_NO_FILE
            );
        }
    }

    protected function buildFile()
    {
        return new File(
            $_POST[$this->fieldName . '_tmp_name'],
            $_POST[$this->fieldName . '_name'],
            $_POST[$this->fieldName . '_size'],
            $_POST[$this->fieldName . '_type']
        );
    }

    /**
     * @param $targetPath
     * @return File
     */
    public function moveLocal($targetPath)
    {
        $sourceFile = $this->getFile();

        $targetFile = new File(
            $targetPath,
            $sourceFile->getOriginalBasename(),
            $sourceFile->getSize(),
            $sourceFile->getType()
        );

        rename(
            $sourceFile->getPath(),
            $targetPath
        );

        return $targetFile;
    }
}
