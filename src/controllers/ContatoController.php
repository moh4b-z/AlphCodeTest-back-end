<?php

require_once __DIR__ . '/../models/Contato.php';
require_once __DIR__ . '/../utils/ApiMessages.php';

class ContatoController {
    
    private $model;
    
    public function __construct() {
        $this->model = new Contato();
    }
    
    public function index() {
        $contatos = $this->model->getAll();
        $response = ApiMessages::successWithData($contatos);
        ApiMessages::sendResponse($response['status_code'], $response);
    }
    
    public function buscarPorNome($nome) {
        $contatos = $this->model->buscarPorNome($nome);
        $response = ApiMessages::successWithData($contatos);
        ApiMessages::sendResponse($response['status_code'], $response);
    }
    
    public function show($id) {
        $contato = $this->model->getById($id);
        
        if ($contato) {
            $response = ApiMessages::successWithData($contato);
            ApiMessages::sendResponse($response['status_code'], $response);
        } else {
            $response = ApiMessages::ERROR_NOT_FOUND;
            ApiMessages::sendResponse($response['status_code'], $response);
        }
    }
    
    public function store() {
        // Pega o JSON do body (similar ao req.body no Express)
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validação básica
        if (!isset($data['nome']) || !isset($data['email']) || 
            !isset($data['data_nascimento']) || !isset($data['profissao']) ||
            !isset($data['telefone_celular']['numero'])) {
            $response = ApiMessages::ERROR_REQUIRED_FIELDS;
            ApiMessages::sendResponse($response['status_code'], $response);
            return;
        }
        
        try {
            $contato = $this->model->create($data);
            
            if ($contato) {
                $response = ApiMessages::successWithData($contato, 'Contato criado com sucesso', 201);
                ApiMessages::sendResponse($response['status_code'], $response);
            } else {
                $response = ApiMessages::ERROR_INTERNAL_SERVER_MODEL;
                ApiMessages::sendResponse($response['status_code'], $response);
            }
        } catch (Exception $e) {
            // Verifica se é erro de email duplicado
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'email') !== false) {
                $response = ApiMessages::ERROR_EMAIL_ALREADY_EXISTS;
            } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $response = ApiMessages::ERROR_PHONE_ALREADY_EXISTS;
            } else {
                $response = ApiMessages::ERROR_INTERNAL_SERVER_MODEL;
            }
            ApiMessages::sendResponse($response['status_code'], $response);
        }
    }
    
    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validação: pelo menos um campo deve ser enviado
        if (empty($data)) {
            $response = ApiMessages::ERROR_REQUIRED_FIELDS;
            ApiMessages::sendResponse($response['status_code'], $response);
            return;
        }
        
        try {
            $contato = $this->model->update($id, $data);
            
            if ($contato) {
                $response = ApiMessages::successWithData($contato, 'Contato atualizado com sucesso');
                ApiMessages::sendResponse($response['status_code'], $response);
            } else {
                $response = ApiMessages::ERROR_INTERNAL_SERVER_MODEL;
                ApiMessages::sendResponse($response['status_code'], $response);
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'email') !== false) {
                $response = ApiMessages::ERROR_EMAIL_ALREADY_EXISTS;
            } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $response = ApiMessages::ERROR_PHONE_ALREADY_EXISTS;
            } else {
                $response = ApiMessages::ERROR_INTERNAL_SERVER_MODEL;
            }
            ApiMessages::sendResponse($response['status_code'], $response);
        }
    }
    
    public function destroy($id) {
        $result = $this->model->delete($id);
        
        if ($result) {
            $response = ApiMessages::SUCCESS_DELETE_ITEM;
            ApiMessages::sendResponse($response['status_code'], $response);
        } else {
            $response = ApiMessages::ERROR_NOT_DELETE;
            ApiMessages::sendResponse($response['status_code'], $response);
        }
    }
}
