PHP Uploader
========

### Nginx configuration

```
upload_progress upload 5m;

server
{
    location @php
    {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root/app.php;
        include /etc/nginx/fastcgi_params;
    }

    location /upload
    {
        upload_pass @php;
        upload_store %PATH_TO_STORAGE_DIRECTORY%;
        upload_pass_args on;
        upload_max_file_size 0;

        upload_set_form_field $upload_field_name.name "$upload_file_name";
        upload_set_form_field $upload_field_name.type "$upload_content_type";
        upload_set_form_field $upload_field_name.tmp_name "$upload_tmp_path";

        upload_aggregate_form_field $upload_field_name.size "$upload_file_size";

        track_uploads upload 5s;
    }

    location /progress
    {
        report_uploads upload;
    }

}
```
