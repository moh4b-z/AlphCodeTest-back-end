<?php

require_once __DIR__ . '/../config/connection.php';

class Profissao {
    
    private $conn;
    
    public function __construct() {
        $this->conn = conectar();
    }
    
    public function getAll() {
        $sql = "SELECT * FROM profissoes ORDER BY nome ASC";
        $result = $this->conn->query($sql);
        
        if ($result->num_rows > 0) {
            $profissoes = [];
            while($row = $result->fetch_assoc()) {
                $profissoes[] = $row;
            }
            return $profissoes;
        }
        return [];
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM profissoes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    public function buscarPorNome($nome) {
        $sql = "SELECT * FROM profissoes WHERE nome LIKE ? ORDER BY nome";
        $stmt = $this->conn->prepare($sql);
        $termoBusca = "%$nome%";
        $stmt->bind_param("s", $termoBusca);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $profissoes = [];
            while($row = $result->fetch_assoc()) {
                $profissoes[] = $row;
            }
            return $profissoes;
        }
        return [];
    }
    
    public function __destruct() {
        $this->conn->close();
    }
}