<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAllTables extends Migration
{
    public function up()
    {
        // 1. Usuarios
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'nome'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'cpf'               => ['type' => 'VARCHAR', 'constraint' => 11, 'unique' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true],
            'senha'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'telefone'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'endereco_completo' => ['type' => 'TEXT', 'null' => true],
            'is_artesa'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_admin'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('usuarios', true);

        // 2. Categorias
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'nome'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('categorias', true);

        // 3. Produtos
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'auto_increment' => true],
            'artesao_id'      => ['type' => 'INT'],
            'categoria_id'    => ['type' => 'INT'],
            'nome'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'descricao'       => ['type' => 'TEXT', 'null' => true],
            'preco'           => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'prazo_confeccao' => ['type' => 'INT', 'default' => 1],
            'estoque'         => ['type' => 'INT', 'default' => 0],
            'tipo'            => ['type' => 'ENUM', 'constraint' => ['pronta_entrega', 'sob_encomenda'], 'default' => 'pronta_entrega'],
            'imagem_path'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('artesao_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('categoria_id', 'categorias', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('produtos', true);

        // 4. Pedidos
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'auto_increment' => true],
            'cliente_id'       => ['type' => 'INT'],
            'artesao_id'       => ['type' => 'INT'],
            'valor_total'      => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'endereco_entrega' => ['type' => 'TEXT'],
            'status_pagamento' => ['type' => 'ENUM', 'constraint' => ['pendente', 'pago'], 'default' => 'pendente'],
            'status_entrega'   => ['type' => 'ENUM', 'constraint' => ['em_producao', 'aguardando_coleta', 'enviado', 'entregue', 'cancelado'], 'default' => 'em_producao'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('cliente_id', 'usuarios', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('artesao_id', 'usuarios', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('pedidos', true);

        // 5. Itens Pedido
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'auto_increment' => true],
            'pedido_id'                => ['type' => 'INT'],
            'produto_id'               => ['type' => 'INT', 'null' => true],
            'nome_produto_historico'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'preco_unitario_historico' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'quantidade'               => ['type' => 'INT', 'default' => 1],
            'subtotal'                 => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('pedido_id', 'pedidos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('produto_id', 'produtos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('itens_pedido', true);
    }

    public function down()
    {
        $this->forge->dropTable('itens_pedido', true);
        $this->forge->dropTable('pedidos', true);
        $this->forge->dropTable('produtos', true);
        $this->forge->dropTable('categorias', true);
        $this->forge->dropTable('usuarios', true);
    }
}
