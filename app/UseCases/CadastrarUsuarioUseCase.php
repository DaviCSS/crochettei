<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Models\UsuarioModel;

class CadastrarUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioModel $usuarioModel
    ) {}

    public function executar(array $dados, bool $isArtesa = false): int
    {

        $dados['is_artesa'] = $isArtesa ? 1 : 0;

        if (isset($dados['cpf'])) {
            $dados['cpf'] = preg_replace('/\D/', '', $dados['cpf']);
        }

        if (isset($dados['telefone'])) {
            $dados['telefone'] = trim($dados['telefone']);
        }

        $id = $this->usuarioModel->insert($dados, true);

        if ($id === false) {

            $erros = $this->usuarioModel->errors();
            $mensagem = implode(' | ', $erros);
            throw new \RuntimeException($mensagem);
        }

        return (int) $id;
    }
}
