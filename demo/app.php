<?php

// get request
$requestUri = $_SERVER['REQUEST_URI'];
if(false !== ($pos = strpos($_SERVER['REQUEST_URI'], '?'))) {
    $requestUri = substr($requestUri, 0, $pos);
}

// route
switch($requestUri) {
    default:
    case '/':
        require_once __DIR__ . '/index.html';
        break;
    case '/upload-iframe':
    case '/upload-xhr':
    case '/upload-nginx':
        echo '<b>$_GET:</b><br/>';
        var_dump($_GET);
        echo '<b>$_POST:</b><br/>';
        var_dump($_POST);
        echo '<b>$_FILES:</b><br/>';
        var_dump($_FILES);
        break;
}

