<?php
// header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
// header("Cache-Control: no-cache"); // needed for internet explorer

$db = new Database();
$data = $db->getLabels();
header("Content-Type: ".'text/json');
print(json_encode($data));

