<?php

class Database {
    private $pdo;
    private $pageName;

    function __construct() {
        if($this->pdo != null) return;
        
        $this->pageName = "/" . (isset($_GET['p']) ? $_GET['p'] : "");
        
        $conStr = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", 
                        "database", 
                        5432, 
                        getenv("POSTGRES_DB"), 
                        getenv("POSTGRES_USER"), 
                        getenv("POSTGRES_PASSWORD"));
        $this->pdo = new \PDO($conStr);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);


        $this->provision();
        $this->removeOldDocuments();

        return $this->pdo;
    }

    private function staticQuery($sql, $bindP = false) {
        $stmt = $this->pdo->prepare($sql);
        $this->printErrors($stmt);
        if($bindP)
            $stmt->bindParam(':p', $this->pageName);
        $stmt->execute();
    }

    private function tableExists($tableName) {
        
        $sql = "SELECT EXISTS (
            SELECT FROM 
                pg_tables
            WHERE 
                schemaname = 'public' AND 
                tablename  = :table
            );";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':table', $tableName);
        return $stmt->execute();
    }

    private function provision() {
        if($this->pdo == null)
            print("NO CONNECTION");
        
        $this->staticQuery(
            "CREATE TABLE IF NOT EXISTS items (
                id SERIAL PRIMARY KEY,
                document varchar(255) NOT NULL,
                label    varchar(255) NOT NULL,
                mime     varchar(63)  NOT NULL,
                hash     varchar(512) NOT NULL,
                data     BYTEA        NOT NULL,
                size     BIGINT       NOT NULL,
                uploadTime TIMESTAMP DEFAULT NOW()
            );");
    }

    public function removeOldDocuments() {
        // Delete documents
        $stmt = $this->pdo->prepare("SELECT	data FROM items WHERE uploadTime < NOW() - INTERVAL '1 DAY'");
        $this->printErrors($stmt);
        if ($stmt->execute()) {
            $oid = null;
            $stmt->bindColumn(1, $oid);
            while($stmt->fetch(\PDO::FETCH_BOUND)) {
                $this->pdo->pgsqlLOBUnlink($oid);
            }
        }
        $this->staticQuery("DELETE FROM items WHERE uploadTime < NOW() - INTERVAL '1 DAY'");
    }

    public function insertDocument($label, $mime, $path, $size) {
        $sql = "INSERT INTO items(document,label,mime,hash,data,size) "
                . "VALUES(:p,:label,:mime,:hash,:blob,:size)";

        $stmt = $this->pdo->prepare($sql);
        $this->printErrors($stmt);
        $hash = hash_file('sha256', $path);
        $fh = fopen($path, "r+");

        $oid = $this->pdo->pgsqlLOBCreate();
        $stream = $this->pdo->pgsqlLOBOpen($oid, 'w');
        
        // read data from the file and copy the the stream
        $fh = fopen($path, 'rb');
        stream_copy_to_stream($fh, $stream);

        $stmt->bindParam(':p', $this->pageName);
        $stmt->bindParam(':label', $label);
        $stmt->bindParam(':mime', $mime);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':blob', $oid);
        $stmt->bindParam(':size', $size);
        $stmt->execute();

        return $this->pdo->lastInsertId();
    }

    public function replacePaste($files) {
        $stmt = $this->pdo->prepare("SELECT	data FROM items WHERE document = :p");
        $stmt->bindParam(':p', $this->pageName);
        $this->printErrors($stmt);
        if ($stmt->execute()) {
            $oid = null;
            $stmt->bindColumn(1, $oid);
            while($stmt->fetch(\PDO::FETCH_BOUND)) {
                $this->pdo->pgsqlLOBUnlink($oid);
            }
        }
        $this->staticQuery("DELETE FROM items WHERE document = :p;",true);
        

        $files = array_values($files);
        for ($i=0; $i < count($files); $i++) { 
            if(startsWith($files[$i]['name'], "LABEL_")) {
                $name = urldecode($files[$i]['name']);
                $name = substr($name, strlen("LABEL_"));
                $mime = $name;
            }
            else {
                $mime = mime_content_type($files[$i]['tmp_name']);
                $name = isset($files[$i]['name']) ? $files[$i]['name'] : $mime;
            }
            $this->insertDocument($name, $mime, $files[$i]['tmp_name'], $files[$i]['size']);
        }
    }

    public function getLabels() {
        $stmt = $this->pdo->prepare("SELECT	label, size, hash FROM items WHERE document = :p");
        $this->printErrors($stmt);
        $stmt->bindParam(':p', $this->pageName);
        if ($stmt->execute()) {
            $label = null;
            $size = null;
            $hash = null;
            $stmt->bindColumn(1, $label);
            $stmt->bindColumn(2, $size);
            $stmt->bindColumn(3, $hash);
            $l = [];
            while($stmt->fetch(\PDO::FETCH_BOUND)) {
                $l[] = ["label" => $label, "size" => $size, "hash" => $hash]; 
            }
            return $l;
        } else {
            return [];
        }
    } 

    public function getEntry($label) {
        $stmt = $this->pdo->prepare("SELECT	label, mime, data, size, hash FROM items WHERE label = :label AND document = :p");
        $this->printErrors($stmt);
        if ($stmt->execute([":label" => $label, ":p" => $this->pageName])) {
            $label = null;
            $mime = null;
            $blob = null;
            $size = null;
            $hash = null;
            $stmt->bindColumn(1, $label);
            $stmt->bindColumn(2, $mime);
            $stmt->bindColumn(3, $blob, \PDO::PARAM_LOB);
            $stmt->bindColumn(4, $size);
            $stmt->bindColumn(5, $hash);

            return $stmt->fetch(\PDO::FETCH_BOUND) ? ["label" => $label, "mime" => $mime, "hash" => $hash, "blob" => $blob, "size" => $size] : null;
        } else {
            return null;
        }
    }

    public function getGenericEntry() {
        $stmt = $this->pdo->prepare("SELECT	label, mime, data, size, hash FROM items WHERE document = :p ORDER BY size DESC LIMIT 1");
        $this->printErrors($stmt);
        $stmt->bindParam(':p', $this->pageName);
        if ($stmt->execute()) {
            $label = null;
            $mime = null;
            $blob = null;
            $size = null;
            $hash = null;
            $stmt->bindColumn(1, $label);
            $stmt->bindColumn(2, $mime);
            $stmt->bindColumn(3, $blob, \PDO::PARAM_LOB);
            $stmt->bindColumn(4, $size);
            $stmt->bindColumn(5, $hash);

            return $stmt->fetch(\PDO::FETCH_BOUND) ? ["label" => $label, "mime" => $mime, "hash" => $hash, "blob" => $blob, "size" => $size] : null;
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