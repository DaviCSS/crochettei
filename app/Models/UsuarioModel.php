<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{

    protected $table = 'usuarios';

    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'nome',
        'cpf',
        'telefone',
        'email',
        'senha',
        'endereco_completo',
        'is_artesa',
        'is_admin',
        'reset_token',
        'reset_expires_at',
    ];

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'nome' => [
            'label'  => 'Nome Completo',
            'rules'  => 'required|min_length[3]|max_length[150]',
            'errors' => [
                'required'   => 'O nome completo é obrigatório. (RN01)',
                'min_length' => 'O nome deve ter pelo menos 3 caracteres.',
                'max_length' => 'O nome não pode ultrapassar 150 caracteres.',
            ],
        ],
        'cpf' => [
            'label'  => 'CPF',
            // Garante que o CPF tem 11 números
            'rules'  => 'required|exact_length[11]|regex_match[/^[0-9]{11}$/]|is_unique[usuarios.cpf,id,{id}]',
            'errors' => [
                'required'      => 'O CPF é obrigatório. (RN01)',
                'exact_length'  => 'O CPF deve ter exatamente 11 dígitos numéricos (sem pontos ou traços).',
                'regex_match'   => 'O CPF deve conter apenas números (ex: 12345678901). (RN01)',
                'is_unique'     => 'Este CPF já está cadastrado no sistema.',
            ],
        ],
        'telefone' => [
            'label'  => 'Telefone / WhatsApp',
            'rules'  => 'required|max_length[20]',
            'errors' => [
                'required'   => 'O telefone é obrigatório (usado para suporte via WhatsApp).',
                'max_length' => 'O telefone não pode ultrapassar 20 caracteres.',
            ],
        ],
        'email' => [
            'label'  => 'E-mail',
            'rules'  => 'required|valid_email|max_length[150]|is_unique[usuarios.email,id,{id}]',
            'errors' => [
                'required'    => 'O e-mail é obrigatório.',
                'valid_email' => 'Informe um endereço de e-mail válido.',
                'max_length'  => 'O e-mail não pode ultrapassar 150 caracteres.',
                'is_unique'   => 'Este e-mail já está cadastrado no sistema.',
            ],
        ],
        'senha' => [
            'label'  => 'Senha',
            'rules'  => 'required|min_length[6]',
            'errors' => [
                'required'   => 'A senha é obrigatória.',
                'min_length' => 'A senha deve ter pelo menos 6 caracteres.',
            ],
        ],
        'endereco_completo' => [
            'label'  => 'Endereço Completo',
            'rules'  => 'required|min_length[10]',
            'errors' => [
                'required'   => 'O endereço completo é obrigatório. (RN01)',
                'min_length' => 'Informe um endereço mais detalhado (rua, número, bairro, cidade).',
            ],
        ],
    ];

    protected $beforeInsert = ['hashSenha'];

    protected $beforeUpdate = ['hashSenhaSeAlterada'];

    // Criptografa a senha antes de salvar no banco de dados para ninguém ver
    protected function hashSenha(array $data): array
    {
        if (isset($data['data']['senha'])) {
            $data['data']['senha'] = password_hash(
                $data['data']['senha'],
                PASSWORD_BCRYPT
            );
        }
        return $data;
    }

    protected function hashSenhaSeAlterada(array $data): array
    {
        if (!empty($data['data']['senha'])) {
            $data['data']['senha'] = password_hash(
                $data['data']['senha'],
                PASSWORD_BCRYPT
            );
        }
        return $data;
    }

    public function buscarPorEmail(string $email): ?object
    {
        return $this->where('email', $email)->first();
    }

    public function buscarPorCpf(string $cpf): ?object
    {
        return $this->where('cpf', $cpf)->first();
    }

    // Confere a senha e loga o usuário
    public function autenticar(string $email, string $senha): ?object
    {
        $usuario = $this->buscarPorEmail($email);

        if ($usuario === null) {
            return null;
        }

        if (!password_verify($senha, $usuario->senha)) {
            return null;
        }

        return $usuario;
    }

    public function listarArtesas(): array
    {
        return $this->where('is_artesa', 1)->findAll();
    }

    public function listarClientes(): array
    {
        return $this->where('is_artesa', 0)->findAll();
    }

    public function promoverParaArtesa(int $id): bool
    {
        return $this->update($id, ['is_artesa' => 1]);
    }

    public function regrasPerfil(int $id): self
    {
        $this->validationRules['cpf']['rules']   = "required|exact_length[11]|regex_match[/^[0-9]{11}$/]|is_unique[usuarios.cpf,id,{$id}]";
        $this->validationRules['email']['rules']  = "required|valid_email|max_length[150]|is_unique[usuarios.email,id,{$id}]";
        unset($this->validationRules['senha']);
        return $this;
    }
}
