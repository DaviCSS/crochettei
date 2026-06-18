<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Models\UsuarioModel;
use CodeIgniter\Session\Session;

class AutenticarUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioModel $usuarioModel,
        private readonly Session      $session
    ) {}

    public function login(string $email, string $senha): object
    {

        $email = strtolower(trim($email));

        $usuario = $this->usuarioModel->autenticar($email, $senha);

        if ($usuario === null) {
            throw new \RuntimeException('E-mail ou senha incorretos. Verifique suas credenciais.');
        }

        $this->session->regenerate(true);

        $this->session->set([
            'logado'         => true,
            'user_id'        => (int) $usuario->id,
            'user_nome'      => $usuario->nome,
            'user_email'     => $usuario->email,
            'user_is_artesa' => (bool) $usuario->is_artesa,
            'user_is_admin'  => (bool) ($usuario->is_admin ?? false),
        ]);

        return $usuario;
    }

    public function logout(): void
    {

        $this->session->destroy();
    }

    public function estaLogado(): bool
    {
        return (bool) $this->session->get('logado');
    }

    public function eArtesa(): bool
    {
        return (bool) $this->session->get('user_is_artesa');
    }
}
