<?php

require_once __DIR__ . '/../config/connection.php';

class Contato {
    
    private $conn;
    
    public function __construct() {
        $this->conn = conectar();
    }
    
    public function getAll() {
        $sql = "SELECT * FROM vw_contatos_completo ORDER BY id DESC";
        $result = $this->conn->query($sql);
        
        if ($result->num_rows > 0) {
            $contatos = [];
            while($row = $result->fetch_assoc()) {
                $contatos[] = $this->montarContatoCompleto($row);
            }
            return $contatos;
        }
        return [];
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM vw_contatos_completo WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $this->montarContatoCompleto($row);
        }
        return null;
    }
    
    public function buscarPorNome($nome) {
        $sql = "SELECT * FROM vw_contatos_completo WHERE nome LIKE ? ORDER BY nome";
        $stmt = $this->conn->prepare($sql);
        $termoBusca = "%$nome%";
        $stmt->bind_param("s", $termoBusca);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $contatos = [];
            while($row = $result->fetch_assoc()) {
                $contatos[] = $this->montarContatoCompleto($row);
            }
            return $contatos;
        }
        return [];
    }
    
    public function create($data) {
        $json = json_encode([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'data_nascimento' => $data['data_nascimento'],
            'permite_notificacao_email' => $data['permite_notificacao_email'] ?? true,
            'profissao' => $data['profissao'],
            'telefone_celular' => [
                'numero' => $data['telefone_celular']['numero'],
                'tem_whatsapp' => $data['telefone_celular']['tem_whatsapp'] ?? false,
                'permite_sms' => $data['telefone_celular']['permite_sms'] ?? false
            ],
            'telefone_fixo' => isset($data['telefone_fixo']['numero']) 
                ? ['numero' => $data['telefone_fixo']['numero']] 
                : null
        ]);
        
        // Chama a procedure
        $sql = "CALL sp_cadastrar_contato(?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $json);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $idInserido = $row['id'];
            
            // Limpa resultados pendentes da procedure
            $stmt->close();
            while ($this->conn->more_results()) {
                $this->conn->next_result();
            }
            
            // Retorna o contato completo
            return $this->getById($idInserido);
        }
        return false;
    }
    
    public function update($id, $data) {
        // Monta JSON apenas com os campos que foram enviados
        $jsonData = [];
        
        if (isset($data['nome'])) $jsonData['nome'] = $data['nome'];
        if (isset($data['email'])) $jsonData['email'] = $data['email'];
        if (isset($data['data_nascimento'])) $jsonData['data_nascimento'] = $data['data_nascimento'];
        if (isset($data['permite_notificacao_email'])) $jsonData['permite_notificacao_email'] = $data['permite_notificacao_email'];
        if (isset($data['profissao'])) $jsonData['profissao'] = $data['profissao'];
        
        if (isset($data['telefone_celular'])) {
            $jsonData['telefone_celular'] = [
                'numero' => $data['telefone_celular']['numero'],
                'tem_whatsapp' => $data['telefone_celular']['tem_whatsapp'] ?? false,
                'permite_sms' => $data['telefone_celular']['permite_sms'] ?? false
            ];
        }
        
        if (isset($data['telefone_fixo'])) {
            $jsonData['telefone_fixo'] = ['numero' => $data['telefone_fixo']['numero']];
        }
        
        $json = json_encode($jsonData);
        
        // Chama a procedure
        $sql = "CALL sp_editar_contato(?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $id, $json);
        
        if ($stmt->execute()) {
            // Limpa resultados pendentes da procedure
            $stmt->close();
            while ($this->conn->more_results()) {
                $this->conn->next_result();
            }
            
            return $this->getById($id);
        }
        return false;
    }

    public function delete($id) {
        // Primeiro deleta os telefones associados
        $sql = "DELETE FROM contatos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    private function montarContatoCompleto($row) {
        $contato = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'email' => $row['email'],
            'data_nascimento' => $row['data_nascimento_br'],
            'idade' => (int)$row['idade'],
            'permite_notificacao_email' => (bool)$row['permite_notificacao_email'],
            'profissao' => [
                'id' => $row['profissao_id'],
                'nome' => $row['profissao_nome']
            ],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
        
        // Busca os telefones do contato
        $contato['telefones'] = $this->buscarTelefonesDoContato($row['id']);
        
        return $contato;
    }
    
    private function buscarTelefonesDoContato($idContato) {
        $sql = "SELECT t.* FROM telefones t 
                INNER JOIN contatos_telefones ct ON t.id = ct.id_telefone 
                WHERE ct.id_contato = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $idContato);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $telefones = [];
        while($row = $result->fetch_assoc()) {
            $telefones[] = [
                'id' => $row['id'],
                'numero' => $row['numero'],
                'e_celular' => (bool)$row['e_celular'],
                'tem_whatsapp' => (bool)$row['tem_whatsapp'],
                'permite_sms' => (bool)$row['permite_sms']
            ];
        }
        
        return $telefones;
    }
    
    public function __destruct() {
        $this->conn->close();
    }
}