<? 
// print("Request ");
// print_r($_REQUEST);

// print("Server ");
// ksort($_SERVER);
// print_r($_SERVER);

// print("Post ");
// print_r($_POST);

// print("Get ");
// print_r($_GET);

// print("Files ");
// print_r($_FILES);

// print("Headers ");
// print_r(getallheaders());

print("Modules ");
print_r(get_loaded_extensions());

print("User ");
print_r(getcwd());

// phpinfo();
print_r(SQLite3::version());
?>