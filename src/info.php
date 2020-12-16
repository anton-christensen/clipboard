<?php
// header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
// header("Cache-Control: no-cache"); // needed for internet explorer

$db = new Database();
$data = $db->getMetadata();
header("Content-Type: ".'text/json');
print(json_encode($data));
// print("{\n");
// print("    \"name\": \"".file_get_contents($nameLocation)."\", \n");
// print("    \"size\": "  .filesize($fileLocation).", \n");
// print("    \"mime\": \"".mime_content_type($fileLocation)."\", \n");
// print("    \"hash\": \"".hash_file("md5", $fileLocation)."\", \n");
// print("    \"time\": \"".filemtime($fileLocation)."\"\n");
// print("}");


