<?php

class Database {
    private $pdo;
    private $pageName;

    function __construct() {
        $this->pageName = "/" . ($_GET['p'] ?? "");
        // die($this->pageName);
        if ($this->pdo == null) {
            $filename = __DIR__ . "/db.sqlite";
            if (!file_exists($filename)) {
                touch($filename, strtotime('-1 days'));
                $this->pdo = new PDO("sqlite:" . $filename);

                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->provision();
            } else {
                $this->pdo = new PDO("sqlite:" . $filename);
                $this->removeOldDocuments();
            }
        }
        return $this->pdo;
    }

    private function staticQuery($sql, $bindP = false) {
        $stmt = $this->pdo->prepare($sql);
        $this->printErrors($stmt);
        if ($bindP)
            $stmt->bindParam(':p', $this->pageName);
        $stmt->execute();
    }

    private function provision() {
        if ($this->pdo == null) {
            print("NO CONNECTION");
            return;
        }

        if (!file_exists('../data')) {
            mkdir('../data', 0777, true);
        }

        $this->staticQuery(
            "CREATE TABLE 
                items ( 
                    id INTEGER PRIMARY KEY AUTOINCREMENT, 
                    document VARCHAR(256), 
                    label VARCHAR(100), 
                    mime VARCHAR(32),
                    hash VARCHAR(256),
                    size INT,
                    uploadTime INT
                );");
    }

    public function removeOldDocuments() {
        $stmt = $this->pdo->prepare("SELECT id FROM items WHERE uploadTime < :t");
        $this->printErrors($stmt);
        $yesterday = time() - 60 * 60 * 24;
        $stmt->bindParam(':t', $yesterday);
        if ($stmt->execute()) {
            $id = null;
            $stmt->bindColumn(1, $id);
            while ($stmt->fetch(PDO::FETCH_BOUND)) {
                unlink('../data/' . $id);
            }
        }

        // Delete documents
        $sql = "DELETE FROM items WHERE uploadTime < :t";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':t', time() - 60 * 60 * 24);
        $stmt->execute();
    }

    public function insertDocument($label, $mime, $path, $size) {
        $sql = "INSERT INTO items(document,label,mime,hash,size,uploadTime) "
            . "VALUES(:p,:label,:mime,:hash,:size,:t)";

        $stmt = $this->pdo->prepare($sql);
        $this->printErrors($stmt);
        $hash = hash_file('md5', $path);

        $stmt->bindParam(':p', $this->pageName);
        $stmt->bindParam(':label', $label);
        $stmt->bindParam(':mime', $mime);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':size', $size);
        $stmt->bindValue(':t', time());
        $stmt->execute();

        $id = $this->pdo->lastInsertId();
        move_uploaded_file($path, '../data/' . $id);

        return $this->pdo->lastInsertId();
    }

    public function replacePaste($files) {
        $stmt = $this->pdo->prepare("SELECT id FROM items WHERE document = :p");
        $this->printErrors($stmt);
        $stmt->bindParam(':p', $this->pageName);
        if ($stmt->execute()) {
            $id = null;
            $stmt->bindColumn(1, $id);
            while ($stmt->fetch(PDO::FETCH_BOUND)) {
                unlink('../data/' . $id);
            }
        }
        $this->staticQuery("DELETE FROM items WHERE document = :p;", true);


        $files = array_values($files);
        for ($i = 0; $i < count($files); $i++) {
            if (startsWith($files[$i]['name'], "LABEL_")) {
                $name = urldecode($files[$i]['name']);
                $name = substr($name, strlen("LABEL_"));
                $mime = $name;
            } else {
                $mime = mime_content_type($files[$i]['tmp_name']);
                $name = $files[$i]['name'] ?? $mime;
            }
            $this->insertDocument($name, $mime, $files[$i]['tmp_name'], $files[$i]['size']);
        }
    }

    public function getLabels() {
        $stmt = $this->pdo->prepare("SELECT label, mime, size, hash, uploadTime FROM items WHERE document = :p");
        $this->printErrors($stmt);
        $stmt->bindParam(':p', $this->pageName);
        if ($stmt->execute()) {
            $label = null;
            $mime = null;
            $size = null;
            $hash = null;
            $time = null;
            $stmt->bindColumn(1, $label);
            $stmt->bindColumn(2, $mime);
            $stmt->bindColumn(3, $size);
            $stmt->bindColumn(4, $hash);
            $stmt->bindColumn(5, $time);
            $l = [];
            while ($stmt->fetch(PDO::FETCH_BOUND)) {
                $l[] = ["label" => $label, "mime" => $mime, "size" => $size, "hash" => $hash, "time" => $time];
            }
            return $l;
        } else {
            return [];
        }
    }

    public function getEntry($label) {
        $stmt = $this->pdo->prepare("SELECT id, label, mime, size, hash FROM items WHERE label = :label AND document = :p");
        $this->printErrors($stmt);
        if ($stmt->execute([":label" => $label, ":p" => $this->pageName])) {
            $id = null;
            $label = null;
            $mime = null;
            $size = null;
            $hash = null;
            $stmt->bindColumn(1, $id);
            $stmt->bindColumn(2, $label);
            $stmt->bindColumn(3, $mime);
            $stmt->bindColumn(4, $size);
            $stmt->bindColumn(5, $hash);

            return $stmt->fetch(PDO::FETCH_BOUND) ? ["id" => $id, "label" => $label, "mime" => $mime, "hash" => $hash, "size" => $size] : null;
        } else {
            return null;
        }
    }

    public function getGenericEntry() {
        $stmt = $this->pdo->prepare("SELECT id, label, mime, size, hash FROM items WHERE document = :p ORDER BY size DESC LIMIT 1");
        $this->printErrors($stmt);
        $stmt->bindParam(':p', $this->pageName);
        if ($stmt->execute()) {
            $id = null;
            $label = null;
            $mime = null;
            $size = null;
            $hash = null;
            $stmt->bindColumn(1, $id);
            $stmt->bindColumn(2, $label);
            $stmt->bindColumn(3, $mime);
            $stmt->bindColumn(4, $size);
            $stmt->bindColumn(5, $hash);

            return $stmt->fetch(PDO::FETCH_BOUND) ? ["id" => $id, "label" => $label, "mime" => $mime, "hash" => $hash, "size" => $size] : null;
        } else {
            return null;
        }
    }

    private function printErrors($stmt) {
        if (!$stmt) {
            var_dump($this->pdo->errorCode());
            var_dump($this->pdo->errorInfo());
            die();
        }
    }

}
