<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UsuarioModel;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
        $model = new UsuarioModel();

        // Senha padrão para todos os testes, conforme README
        $senhaPadrao = password_hash('Senha123', PASSWORD_BCRYPT);

        $usuarios = [
            // --- ARTESÃS ---
            [
                'nome'              => 'Maria das Graças',
                'cpf'               => '11111111111',
                'telefone'          => '27999991111',
                'email'             => 'maria.artesa@crochettei.com', // Mantido porque é usado nos outros seeders e não mencionado no README
                'senha'             => $senhaPadrao,
                'endereco_completo' => 'Rua das Flores, 123, Vila Rubim, Vitória - ES',
                'is_artesa'         => 1,
                'is_admin'          => 0,
            ],
            [
                'nome'              => 'Lourdes Oliveira',
                'cpf'               => '22222222222',
                'telefone'          => '27988882222',
                'email'             => 'lourdes.oliveira@email.com',
                'senha'             => $senhaPadrao,
                'endereco_completo' => 'Av. Central, 45, Laranjeiras, Serra - ES',
                'is_artesa'         => 1,
                'is_admin'          => 0,
            ],
            [
                'nome'              => 'Fátima Silva',
                'cpf'               => '33333333333',
                'telefone'          => '27977773333',
                'email'             => 'fatima.silva@email.com',
                'senha'             => $senhaPadrao,
                'endereco_completo' => 'Rua da Praia, 700, Itapuã, Vila Velha - ES',
                'is_artesa'         => 1,
                'is_admin'          => 0,
            ],

            // --- CLIENTES COMUNS ---
            [
                'nome'              => 'Fernanda Costa',
                'cpf'               => '44444444444',
                'telefone'          => '27966664444',
                'email'             => 'fernanda.costa@email.com',
                'senha'             => $senhaPadrao,
                'endereco_completo' => 'Rua dos Passarinhos, 55, Jardim da Penha, Vitória - ES',
                'is_artesa'         => 0,
                'is_admin'          => 0,
            ],
            [
                'nome'              => 'Ana Clara Marques',
                'cpf'               => '55555555555',
                'telefone'          => '27955555555',
                'email'             => 'ana.compras@exemplo.com',
                'senha'             => $senhaPadrao,
                'endereco_completo' => 'Av. Vitória, 1200, Centro, Vitória - ES',
                'is_artesa'         => 0,
                'is_admin'          => 0,
            ],

            // --- ADMIN ---
            [
                'nome'              => 'Administrador do Sistema',
                'cpf'               => '00000000000',
                'telefone'          => '27900000000',
                'email'             => 'admin@crochettei.com',
                'senha'             => $senhaPadrao,
                'endereco_completo' => 'Sede Crochettei',
                'is_artesa'         => 0,
                'is_admin'          => 1,
            ]
        ];

        foreach ($usuarios as $user) {
            // Evita duplicação de e-mail ou CPF
            if (!$model->where('email', $user['email'])->first()) {
                // Remove validação de CPF único global se estiver rodando várias vezes, mas o model deve lidar com isso
                $model->insert($user);
            }
        }
    }
}
