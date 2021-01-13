<?php

class Database {
    private $pdo;
    private $pageName;

    function __construct() {
        $this->pageName = "/" . (isset($_GET['p']) ? $_GET['p'] : "");
        // die($this->pageName);
        if ($this->pdo == null) {
            if(!file_exists("/var/www/data/db.sqlite")) {
                touch("/var/www/data/db.sqlite", strtotime('-1 days'));
                $this->pdo = new \PDO("sqlite:/var/www/data/db.sqlite");

                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->provision();
            }
            else {
                $this->pdo = new \PDO("sqlite:/var/www/data/db.sqlite");
                $this->removeOldDocuments();
            }
        }
        return $this->pdo;
    }

    private function staticQuery($sql, $bindP = false) {
        $stmt = $this->pdo->prepare($sql);
        $this->printErrors($stmt);
        if($bindP)
            $stmt->bindParam(':p', $this->pageName);
        $stmt->execute();
    }

    private function provision() {
        if($this->pdo == null)
            print("NO CONNECTION");
        else
            print("SUCCESSFULLY CONNECTED");
        $this->staticQuery("CREATE TABLE metadata ( id INTEGER PRIMARY KEY AUTOINCREMENT, document VARCHAR(256), uploadTime INT );");
        $this->staticQuery("CREATE TABLE items ( id INTEGER PRIMARY KEY AUTOINCREMENT, document VARCHAR(256), label VARCHAR(100), mime VARCHAR(32), data BLOB, size INT );");
    }

    public function removeOldDocuments() {
        // Delete documents
        $sql = "DELETE FROM items
        WHERE id IN (
            SELECT i.id FROM items i
            JOIN metadata m
            ON m.document = i.document
            WHERE m.uploadTime < :t
        )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':t', time() - 60*60*24);
        $value = $stmt->execute();

        // Delete metadata file
        $sql = "DELETE FROM metadata WHERE uploadTime < :t";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':t', time() - 60*60*24); // *60*24
        $value = $stmt->execute();
    }

    public function insertDocument($label, $mime, $path, $size) {
        $sql = "INSERT INTO items(document,label,mime,data,size) "
                . "VALUES(:p,:label,:mime,:blob,:size)";

        $fh = fopen($path, "r+");
        $stmt = $this->pdo->prepare($sql);
        $this->printErrors($stmt);

        $stmt->bindParam(':p', $this->pageName);
        $stmt->bindParam(':label', $label);
        $stmt->bindParam(':mime', $mime);
        $stmt->bindParam(':blob', $fh, \PDO::PARAM_LOB);
        $stmt->bindParam(':size', $size);
        $stmt->execute();

        // unlink($path);

        return $this->pdo->lastInsertId();
    }

    public function replacePaste($files) {
        $this->staticQuery("DELETE FROM items WHERE document = :p;",true);
        $this->staticQuery("DELETE FROM metadata WHERE document = :p;",true);
        $this->staticQuery("INSERT INTO metadata(document, uploadTime) VALUES(:p, ".time().")",true);

        $files = array_values($files);
        for ($i=0; $i < count($files); $i++) { 
            if(startsWith($files[$i]['name'], "LABEL_")) {
                $name = urldecode($files[$i]['name']);
                $name = substr($name, strlen("LABEL_"));
                $mime = $name;
            }
            else {
                $mime = mime_content_type($files[$i]['tmp_name']);
                $name = $mime;
            }
            $this->insertDocument($name, $mime, $files[$i]['tmp_name'], $files[$i]['size']);
        }
    }

    public function getLabels() {
        $stmt = $this->pdo->prepare("SELECT	label, size FROM items WHERE document = :p");
        $this->printErrors($stmt);
        $stmt->bindParam(':p', $this->pageName);
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
        $stmt = $this->pdo->prepare("SELECT	uploadTime FROM metadata WHERE document = :p");
        $this->printErrors($stmt);
        $stmt->bindParam(':p', $this->pageName);

        if ($stmt->execute()) {
            $time = null;
            $stmt->bindColumn(1, $time);

            return $stmt->fetch(\PDO::FETCH_BOUND) ? ["time" => $time, "labels" => $this->getLabels()] : null;
        } else {
            return null;
        }
    }    

    public function getEntry($label) {
        $stmt = $this->pdo->prepare("SELECT	label, mime, data, size FROM items WHERE label = :label AND document = :p");
        $this->printErrors($stmt);
        if ($stmt->execute([":label" => $label, ":p" => $this->pageName])) {
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

    public function getGenericEntry() {
        $stmt = $this->pdo->prepare("SELECT	label, mime, data, size FROM items WHERE document = :p ORDER BY size DESC LIMIT 1");
        $this->printErrors($stmt);
        $stmt->bindParam(':p', $this->pageName);
        if ($stmt->execute()) {
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

    private function printErrors($stmt) {
        if($stmt == false) {
            var_dump($this->pdo->errorCode());
            var_dump($this->pdo->errorInfo());
            die();
        }
    }

}
?>