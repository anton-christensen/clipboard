<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Access-Control-Allow-Origin: *");

require("functions.php");
require("db.php");

$isCurl = str_contains($_SERVER['HTTP_USER_AGENT'], 'curl');

$db = new Database();
if (count($_FILES) > 0) {
    $db->replacePaste($_FILES);
    die();
}

if (isset($_GET)) {
    if (isset($_GET['path'])) {
        die();
    } else if (isset($_GET['clip'])) {
        require("clip.php");
        die();
    } else if (isset($_GET['install'])) {
        require("install.html");
        die();
    } else if (isset($_GET['test'])) {
        include("test.php");
        die();
    } else if (isset($_GET['info'])) {
        include("info.php");
        die();
    }
}

// if curl -> return clipboard only
if ($isCurl) {
    require("clip.php");
    die();
}

require('app.php');

