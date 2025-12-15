<?php

require_once __DIR__ . '/../models/Profissao.php';

class ProfissaoController {
    
    private $model;
    
    public function __construct() {
        $this->model = new Profissao();
    }
    
    public function index() {
        $profissoes = $this->model->getAll();
        $this->sendResponse(200, [
            'success' => true,
            'data' => $profissoes
        ]);
    }
    
    public function buscarPorNome($nome) {
        $profissoes = $this->model->buscarPorNome($nome);
        $this->sendResponse(200, [
            'success' => true,
            'data' => $profissoes
        ]);
    }
    
    public function show($id) {
        $profissao = $this->model->getById($id);
        
        if ($profissao) {
            $this->sendResponse(200, [
                'success' => true,
                'data' => $profissao
            ]);
        } else {
            $this->sendResponse(404, [
                'success' => false,
                'message' => 'Profissão não encontrada'
            ]);
        }
    }
    
    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['nome'])) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'Nome é obrigatório'
            ]);
            return;
        }
        
        $profissao = $this->model->create($data);
        
        if ($profissao) {
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Profissão criada com sucesso',
                'data' => $profissao
            ]);
        } else {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Erro ao criar profissão'
            ]);
        }
    }
    
    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['nome'])) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'Nome é obrigatório'
            ]);
            return;
        }
        
        $profissao = $this->model->update($id, $data);
        
        if ($profissao) {
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Profissão atualizada com sucesso',
                'data' => $profissao
            ]);
        } else {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Erro ao atualizar profissão'
            ]);
        }
    }
    
    public function destroy($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Profissão deletada com sucesso'
            ]);
        } else {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Erro ao deletar profissão'
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
