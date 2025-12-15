<?php

require_once __DIR__ . '/../models/Contato.php';

class ContatoController {
    
    private $model;
    
    public function __construct() {
        $this->model = new Contato();
    }
    
    public function index() {
        $contatos = $this->model->getAll();
        $this->sendResponse(200, [
            'success' => true,
            'data' => $contatos
        ]);
    }
    
    public function buscarPorNome($nome) {
        $contatos = $this->model->buscarPorNome($nome);
        $this->sendResponse(200, [
            'success' => true,
            'data' => $contatos
        ]);
    }
    
    public function show($id) {
        $contato = $this->model->getById($id);
        
        if ($contato) {
            $this->sendResponse(200, [
                'success' => true,
                'data' => $contato
            ]);
        } else {
            $this->sendResponse(404, [
                'success' => false,
                'message' => 'Contato não encontrado'
            ]);
        }
    }
    
    public function store() {
        // Pega o JSON do body (similar ao req.body no Express)
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validação básica
        if (!isset($data['nome']) || !isset($data['email']) || 
            !isset($data['data_nascimento']) || !isset($data['profissao']) ||
            !isset($data['telefone_celular']['numero'])) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'Nome, email, data de nascimento, profissão e telefone celular são obrigatórios'
            ]);
            return;
        }
        
        $contato = $this->model->create($data);
        
        if ($contato) {
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Contato criado com sucesso',
                'data' => $contato
            ]);
        } else {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Erro ao criar contato'
            ]);
        }
    }
    
    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validação: pelo menos um campo deve ser enviado
        if (empty($data)) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'Nenhum campo foi enviado para atualização'
            ]);
            return;
        }
        
        $contato = $this->model->update($id, $data);
        
        if ($contato) {
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Contato atualizado com sucesso',
                'data' => $contato
            ]);
        } else {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Erro ao atualizar contato'
            ]);
        }
    }
    
    public function destroy($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Contato deletado com sucesso'
            ]);
        } else {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Erro ao deletar contato'
            ]);
        }
    }
    
    private function sendResponse($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
