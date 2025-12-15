<?php

require_once __DIR__ . '/../controllers/ContatoController.php';
require_once __DIR__ . '/../controllers/ProfissaoController.php';

class Router {
    
    private $contatoController;
    private $profissaoController;
    
    public function __construct() {
        $this->contatoController = new ContatoController();
        $this->profissaoController = new ProfissaoController();
    }
    
    public function route($url, $method) {
        
        // Remove barras extras e divide a URL em partes
        $url = trim($url, '/');
        $urlParts = explode('/', $url);
        
        // Verifica se é uma rota da API
        if ($urlParts[0] !== 'api' || !isset($urlParts[1])) {
            $this->sendError(404, 'Rota não encontrada');
            return;
        }
        
        $resource = $urlParts[1]; 
        if ($resource === 'contatos') {
            switch ($method) {
                case 'GET':
                    // GET /api/contatos/buscar?nome=...
                    if (isset($urlParts[2]) && $urlParts[2] === 'buscar' && isset($_GET['nome'])) {
                        $this->contatoController->buscarPorNome($_GET['nome']);
                    }
                    // GET /api/contatos/:id
                    elseif (isset($urlParts[2]) && is_numeric($urlParts[2])) {
                        $this->contatoController->show($urlParts[2]);
                    }
                    // GET /api/contatos
                    else {
                        $this->contatoController->index();
                    }
                    break;
                    
                case 'POST':
                    // POST /api/contatos
                    $this->contatoController->store();
                    break;
                    
                case 'PUT':
                    if (isset($urlParts[2]) && is_numeric($urlParts[2])) {
                        // PUT /api/contatos/:id
                        $this->contatoController->update($urlParts[2]);
                    } else {
                        $this->sendError(400, 'ID é obrigatório para atualizar');
                    }
                    break;
                    
                case 'DELETE':
                    if (isset($urlParts[2]) && is_numeric($urlParts[2])) {
                        // DELETE /api/contatos/:id
                        $this->contatoController->destroy($urlParts[2]);
                    } else {
                        $this->sendError(400, 'ID é obrigatório para deletar');
                    }
                    break;
                    
                default:
                    $this->sendError(405, 'Método não permitido');
            }
        }
        
        elseif ($resource === 'profissoes') {
            switch ($method) {
                case 'GET':
                    // GET /api/profissoes/buscar?nome=...
                    if (isset($urlParts[2]) && $urlParts[2] === 'buscar' && isset($_GET['nome'])) {
                        $this->profissaoController->buscarPorNome($_GET['nome']);
                    }
                    // GET /api/profissoes/:id
                    elseif (isset($urlParts[2]) && is_numeric($urlParts[2])) {
                        $this->profissaoController->show($urlParts[2]);
                    }
                    // GET /api/profissoes
                    else {
                        $this->profissaoController->index();
                    }
                    break;
                    
                case 'POST':
                    // POST /api/profissoes
                    $this->profissaoController->store();
                    break;
                    
                case 'PUT':
                    if (isset($urlParts[2]) && is_numeric($urlParts[2])) {
                        // PUT /api/profissoes/:id
                        $this->profissaoController->update($urlParts[2]);
                    } else {
                        $this->sendError(400, 'ID é obrigatório para atualizar');
                    }
                    break;
                    
                case 'DELETE':
                    if (isset($urlParts[2]) && is_numeric($urlParts[2])) {
                        // DELETE /api/profissoes/:id
                        $this->profissaoController->destroy($urlParts[2]);
                    } else {
                        $this->sendError(400, 'ID é obrigatório para deletar');
                    }
                    break;
                    
                default:
                    $this->sendError(405, 'Método não permitido');
            }
        }
        
        else {
            $this->sendError(404, 'Recurso não encontrado');
        }
    }
    
    private function sendError($statusCode, $message) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
