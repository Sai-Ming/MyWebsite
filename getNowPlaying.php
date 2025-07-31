<?php
header('Content-Type: application/json; charset=utf-8');

$station = isset($_GET['url']) ? $_GET['url'] : '';
if (!$station) {
    echo json_encode(["error" => "No station URL provided"]);
    exit;
}

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "Icy-MetaData: 1\r\nUser-Agent: Mozilla/5.0\r\n",
        "timeout" => 5
    ]
];
$context = stream_context_create($opts);
$fp = @fopen($station, 'r', false, $context);

if (!$fp) {
    echo json_encode(["title" => "無法連線"]);
    exit;
}

$metaInt = 0;
foreach ($http_response_header as $h) {
    if (stripos($h, 'icy-metaint') === 0) {
        $parts = explode(':', $h);
        $metaInt = intval(trim($parts[1]));
        break;
    }
}

if ($metaInt <= 0) {
    echo json_encode(["title" => "未知"]);
    fclose($fp);
    exit;
}

// 跳過音訊到 metadata 區塊
$read = 0;
while ($read < $metaInt) {
    $chunk = fread($fp, $metaInt - $read);
    if (!$chunk) break;
    $read += strlen($chunk);
}

// 讀 metadata
$metaLength = ord(fread($fp, 1)) * 16;
$metaData = $metaLength > 0 ? fread($fp, $metaLength) : '';
fclose($fp);

$title = "未知";
if ($metaData && preg_match("/StreamTitle='([^']*)'/i", $metaData, $matches)) {
    $title = $matches[1];
}

echo json_encode(["title" => $title]);
