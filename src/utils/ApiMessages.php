<?php
/**
 * Mensagens e Respostas Padronizadas da API
 * Centraliza todas as mensagens de erro e sucesso
 */

class ApiMessages {
    
    // URL Base da API
    const API_BASE_URL = 'http://localhost:8000';
    
    /************************* MENSAGENS DE ERRO *************************/
    
    const ERROR_REQUIRED_FIELDS = [
        'success' => false,
        'status_code' => 400,
        'message' => 'Campo obrigatório não preenchido ou ultrapassagem de caracteres'
    ];
    
    const ERROR_NOT_DELETE = [
        'success' => false,
        'status_code' => 400,
        'message' => 'Não foi possível deletar'
    ];
    
    const ERROR_NOT_FOUND = [
        'success' => false,
        'status_code' => 404,
        'message' => 'Conteúdo não encontrado'
    ];
    
    const ERROR_EMAIL_NOT_FOUND = [
        'success' => false,
        'status_code' => 404,
        'message' => 'Email não encontrado'
    ];
    
    const ERROR_NOT_FOUND_FOREIGN_KEY = [
        'success' => false,
        'status_code' => 404,
        'message' => 'ID da chave estrangeira não encontrado'
    ];
    
    const ERROR_CONTENT_TYPE = [
        'success' => false,
        'status_code' => 415,
        'message' => 'Não foi possível processar a requisição, pois o formato de dados não é suportado. Favor enviar apenas JSON.'
    ];
    
    const ERROR_INVALID_CREDENTIALS = [
        'success' => false,
        'status_code' => 401,
        'message' => 'Credenciais incorretas'
    ];
    
    const ERROR_EMAIL_ALREADY_EXISTS = [
        'success' => false,
        'status_code' => 409,
        'message' => 'Email já existente'
    ];
    
    const ERROR_PHONE_ALREADY_EXISTS = [
        'success' => false,
        'status_code' => 409,
        'message' => 'Número de celular já existente'
    ];
    
    const ERROR_INVALID_PHONE = [
        'success' => false,
        'status_code' => 400,
        'message' => 'Telefone fixo não pode ter WhatsApp ou SMS'
    ];
    
    const ERROR_INTERNAL_SERVER_MODEL = [
        'success' => false,
        'status_code' => 500,
        'message' => 'Não foi possível processar a requisição, pois ocorreram erros internos no model'
    ];
    
    const ERROR_INTERNAL_SERVER_CONTROLLER = [
        'success' => false,
        'status_code' => 500,
        'message' => 'Não foi possível processar a requisição, pois ocorreram erros internos no controller'
    ];
    
    const ERROR_INTERNAL_SERVER_SERVICES = [
        'success' => false,
        'status_code' => 500,
        'message' => 'Não foi possível processar a requisição, pois ocorreram erros internos no service'
    ];
    
    /************************* MENSAGENS DE SUCESSO *************************/
    
    const SUCCESS_CREATED_ITEM = [
        'success' => true,
        'status_code' => 201,
        'message' => 'Item criado com sucesso'
    ];
    
    const SUCCESS_DELETE_ITEM = [
        'success' => true,
        'status_code' => 200,
        'message' => 'Item deletado com sucesso'
    ];
    
    const SUCCESS_UPDATED_ITEM = [
        'success' => true,
        'status_code' => 200,
        'message' => 'Item atualizado com sucesso'
    ];
    
    const SUCCESS_LOGIN = [
        'success' => true,
        'status_code' => 200,
        'message' => 'Login realizado com sucesso'
    ];
    
    const SUCCESS_REQUEST = [
        'success' => true,
        'status_code' => 200,
        'message' => 'Requisição realizada com sucesso'
    ];
    
    /************************* MÉTODOS AUXILIARES *************************/
    
    /**
     * Cria uma resposta de sucesso com dados
     */
    public static function successWithData($data, $message = null, $statusCode = 200) {
        return [
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message ?? self::SUCCESS_REQUEST['message'],
            'data' => $data
        ];
    }
    
    /**
     * Cria uma resposta de erro personalizada
     */
    public static function error($message, $statusCode = 400) {
        return [
            'success' => false,
            'status_code' => $statusCode,
            'message' => $message
        ];
    }
    
    /**
     * Cria uma resposta de sucesso simples
     */
    public static function success($message, $statusCode = 200) {
        return [
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message
        ];
    }
    
    /**
     * Envia resposta JSON e encerra execução
     */
    public static function sendResponse($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
