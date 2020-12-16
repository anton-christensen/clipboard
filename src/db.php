<?php

class Database {
    private $pdo;

    function __construct() {
        if ($this->pdo == null) {
            if(!file_exists("/var/www/data/db.sqlite")) {
                touch("/var/www/data/db.sqlite", strtotime('-1 days'));
                $this->pdo = new \PDO("sqlite:/var/www/data/db.sqlite");

                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->provision();
            }
            else {
                $this->pdo = new \PDO("sqlite:/var/www/data/db.sqlite");
            }
        }
        return $this->pdo;
    }

    private function staticQuery($sql) {
        $stmt = $this->pdo->prepare($sql);
        if($stmt == false) {
            var_dump($this->pdo->errorCode());
            var_dump($this->pdo->errorInfo());
        }
        $stmt->execute();


    }

    private function provision() {
        if($this->pdo == null)
            print("NO CONNECTION");
        else
            print("SUCCESSFULLY CONNECTED");
        $this->staticQuery("CREATE TABLE metadata ( id INTEGER PRIMARY KEY AUTOINCREMENT, uploadTime TEXT );");
        $this->staticQuery("CREATE TABLE items ( id INTEGER PRIMARY KEY AUTOINCREMENT, label VARCHAR(100), mime VARCHAR(32), data BLOB, size INT );");
    }

    public function emptyTable() {
        $this->staticQuery("DELETE FROM items;");
        $this->staticQuery("DELETE FROM metadata;");
        $this->staticQuery("INSERT INTO metadata(uploadTime) VALUES(".time().")");
    }

    public function insertDocument($label, $mime, $path, $size) {
        $sql = "INSERT INTO items(label,mime,data,size) "
                . "VALUES(:label,:mime,:blob,:size)";

        $fh = fopen($path, "r+");
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindParam(':label', $label);
        $stmt->bindParam(':mime', $mime);
        $stmt->bindParam(':blob', $fh, \PDO::PARAM_LOB);
        $stmt->bindParam(':size', $size);
        $stmt->execute();

        // unlink($path);

        return $this->pdo->lastInsertId();
    }

    public function replacePaste($files) {
        $this->emptyTable();
        $files = array_values($files);
        var_dump($files);
        for ($i=0; $i < count($files); $i++) { 
            $name = urldecode($files[$i]['name']);
            $name = substr($name, strlen("LABEL_"));
            // $mime = mime_content_type($files[$i]['tmp_name']);
            $mime = $name;
            $this->insertDocument($name, $mime, $files[$i]['tmp_name'], $files[$i]['size']);
        }
    }

    public function getLabels() {
        $stmt = $this->pdo->prepare("SELECT	label, size FROM items");
        if ($stmt->execute()) {
            $label = null;
            $size = null;
            $stmt->bindColumn(1, $label);
            $stmt->bindColumn(2, $size);
            $l = [];
            while($stmt->fetch(\PDO::FETCH_BOUND)) {
                $l[] = ["label" => $label, "size" => $size]; 
            }
            return $l;
        } else {
            return [];
        }
    }
    
    public function getMetadata() {
        $stmt = $this->pdo->prepare("SELECT	uploadTime FROM metadata");
        if ($stmt->execute()) {
            $time = null;
            $stmt->bindColumn(1, $time);

            return $stmt->fetch(\PDO::FETCH_BOUND) ? ["time" => $time, "labels" => $this->getLabels()] : null;
        } else {
            return null;
        }
    }    

    public function getEntry($label) {
        $stmt = $this->pdo->prepare("SELECT	label, mime, data, size FROM items WHERE label = :label");
        if ($stmt->execute([":label" => $label])) {
            $label = null;
            $mime = null;
            $blob = null;
            $size = null;
            $stmt->bindColumn(1, $label);
            $stmt->bindColumn(2, $mime);
            $stmt->bindColumn(3, $blob, \PDO::PARAM_LOB);
            $stmt->bindColumn(4, $size);

            return $stmt->fetch(\PDO::FETCH_BOUND) ? ["label" => $label, "mime" => $mime, "blob" => $blob, "size" => $size] : null;
        } else {
            return null;
        }
    }

}
?>