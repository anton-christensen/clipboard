<?


$db = new Database();
$entry = $db->getEntry($_GET['clip']);
if($entry === null) {
  http_response_code(500);
  print("No such label");
  die();
}

if($forceDownload)
  header('Content-Disposition: attachment; filename="clipboard"');
else
  header('Content-Disposition: inline');

header("Content-Type: ".$entry["mime"]);
header("Content-Length:".$entry["size"]);
print($entry["blob"]);
// var_dump($entry);

?>