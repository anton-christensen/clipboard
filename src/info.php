<?php

$db = new Database();
$data = $db->getLabels();
header("Content-Type: " . 'text/json');
print(json_encode($data));

