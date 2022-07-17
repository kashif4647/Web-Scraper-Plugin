<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$json = $_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/product-scraper/logging/logging.txt';
$msg = file_get_contents($json);

if(!$msg){
    $msg = 'Script started, please be patient...';
}
echo "data: {$msg}\n\n";
flush();