<?php

require_once __DIR__ . '/../models/Profissao.php';
require_once __DIR__ . '/../utils/ApiMessages.php';

class ProfissaoController {
    
    private $model;
    
    public function __construct() {
        $this->model = new Profissao();
    }
    
    public function index() {
        $profissoes = $this->model->getAll();
        $response = ApiMessages::successWithData($profissoes);
        ApiMessages::sendResponse($response['status_code'], $response);
    }
    
    public function buscarPorNome($nome) {
        $profissoes = $this->model->buscarPorNome($nome);
        $response = ApiMessages::successWithData($profissoes);
        ApiMessages::sendResponse($response['status_code'], $response);
    }
    
    public function show($id) {
        $profissao = $this->model->getById($id);
        
        if ($profissao) {
            $response = ApiMessages::successWithData($profissao);
            ApiMessages::sendResponse($response['status_code'], $response);
        } else {
            $response = ApiMessages::ERROR_NOT_FOUND;
            ApiMessages::sendResponse($response['status_code'], $response);
        }
    }
}
