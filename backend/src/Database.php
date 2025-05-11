<?php


class Database {

    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO('mysql:host=' . $_ENV["DATABASE_HOSTNAME"] . ';dbname='. $_ENV["DATABASE_NAME"], $_ENV["DATABASE_USERNAME"], $_ENV["DATABASE_PASSWORD"]);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Connection Error: '. $e->getMessage();
        }

        return $this->conn;
    }

}

?>