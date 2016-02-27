PHP Uploader
========

[![Latest Stable Version](https://poser.pugx.org/sokil/php-upload/v/stable.png)](https://packagist.org/packages/sokil/php-upload)
[![Build Status](https://travis-ci.org/sokil/php-upload.svg?branch=master)](https://travis-ci.org/sokil/php-upload)
[![Coverage Status](https://coveralls.io/repos/sokil/php-upload/badge.svg?branch=master&service=github)](https://coveralls.io/github/sokil/php-upload?branch=master)

### Installation

You can install library through Composer:
```javascript
{
    "require": {
        "sokil/php-upload": "dev-master"
    }
}
```

### Useage

First create HTML:
```html
    <input type="file" name="attachment" />
```

Then add PHP code to upload action to upload file to local system:
```php
<?php
    $uploader = new \Sokil\Upload\Handler([
        'fieldName' => 'attachment',
    ]);
    $uploader->moveLocal(__DIR__ . '/uploads/');
```

Also library supports Gaufrette filesistems. Read about Gaufrette at https://github.com/KnpLabs/Gaufrette.
Read abount configuring Gaufrette filesystems in Symfony at https://github.com/KnpLabs/KnpGaufretteBundle.

To upload file into Gaufrette Filesystem:
```php
<?php

$filesystem = new \Gaufrette\Filesystem(new \Gaufrette\Adapter\Local(
    this->getParameter('kernel.root_dir') . '/attachments/'
));

$uploader = new \Sokil\Upload\Handler([
    'fieldName' => 'attachment',
]);
$uploader->move($filesystem);
```

### Suggested packasges

* https://github.com/sokil/upload.js - frontend component
* https://github.com/sokil/FileStorageBundle - Symfony bundle
* https://github.com/sokil/php-upload-sandbox - Sandbox to test backend and frontend. Clone repo and start server.

### Nginx configuration

During standard upload file is moved to php's temp dir, and then moved to target
destination using `move_uploaded_file`. If this dirs on different
physical drives, some time will be spend to move file physically between devices.

There is another reason when nginx + php-fpm stack user.
During upload nginx stored file to its own temp dir. After passing control to 
php-fpm, nginx moves cached file to php's temp dir, and than php moves file 
to destination using `move_uploaded_file`. So file copied three times, and
maybe on different physical devices.

This method moves file directly to configured drive, so in php code only
rename of file required.

Nginx must be compiled with this modules:
* Upload module: https://github.com/vkholodkov/nginx-upload-module
```
--add-module=/path/to/nginx-upload-module
```
* Upload progress module: https://github.com/masterzen/nginx-upload-progress-module
```
--add-module=/path/to/nginx-upload-progress-module
```

Example of nginx configuration to handle upload and progress:
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
