<?


$db = new Database();
if(empty($_GET['clip'])) {
  $entry = $db->getGenericEntry();
}
else {
  $entry = $db->getEntry($_GET['clip']);
}
if($entry === null) {
  http_response_code(500);
  print("No such label");
  die();
}

if($forceDownload)
  header('Content-Disposition: attachment; filename="'.$entry["label"].'"');
else
  header('Content-Disposition: inline');

header("Content-Type: ".$entry["mime"]);
header("Content-Length:".$entry["size"]);

readfile_chunked('../data/'.$entry["id"]);

?>