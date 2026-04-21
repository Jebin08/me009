<?php

class Database {

    private $host = "localhost";
    private $db   = "registration_db";
    private $user = "root";
    private $pass = "";

    public function connect(){

        try {

            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4",
                $this->user,
                $this->pass
            );

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;

        } catch (PDOException $e) {

            // simple error (no expose)
            die(json_encode([
                "status" => false,
                "message" => "DB connection failed"
            ]));
        }
    }
}

?>