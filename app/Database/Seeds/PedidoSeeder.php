<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\PedidoModel;
use App\Models\ItemPedidoModel;
use App\Models\UsuarioModel;
use App\Models\ProdutoModel;

class PedidoSeeder extends Seeder
{
    public function run()
    {
        $pedidoModel = new PedidoModel();
        $itemModel = new ItemPedidoModel();
        $usuarioModel = new UsuarioModel();
        $produtoModel = new ProdutoModel();

        $joao = $usuarioModel->where('email', 'fernanda.costa@email.com')->first();
        $ana = $usuarioModel->where('email', 'ana.compras@exemplo.com')->first();

        // Produtos variados
        $urso = $produtoModel->where('nome', 'Urso de Pelúcia Amigurumi')->first();
        $tapete = $produtoModel->where('nome', 'Tapete de Barbante Cru')->first();
        $blusa = $produtoModel->where('nome', 'Blusa de Frio em Crochê')->first();
        $polvo = $produtoModel->where('nome', 'Polvo Amigurumi para Prematuros')->first();

        if (!$joao || !$ana || !$urso || !$tapete || !$blusa || !$polvo) {
            echo "Aviso: Dependências para PedidoSeeder não encontradas.\n";
            return;
        }

        // Se já existem pedidos, evita duplicar toda vez que o seeder rodar
        if ($pedidoModel->countAllResults() > 0) {
            return;
        }

        // --- PEDIDO 1: João compra Urso da Maria (Pago, em produção) ---
        $pedido1_id = $pedidoModel->insert([
            'cliente_id' => $joao->id,
            'artesao_id' => $urso->artesao_id,
            'valor_total' => $urso->preco,
            'endereco_entrega' => $joao->endereco_completo,
            'status_pagamento' => 'pago',
            'status_entrega' => 'em_producao',
        ], true);

        $itemModel->insert([
            'pedido_id' => $pedido1_id,
            'produto_id' => $urso->id,
            'nome_produto_historico' => $urso->nome,
            'preco_unitario_historico' => $urso->preco,
            'quantidade' => 1,
            'subtotal' => $urso->preco
        ]);


        // --- PEDIDO 2: Ana compra Tapete da Maria (Pendente) ---
        $pedido2_id = $pedidoModel->insert([
            'cliente_id' => $ana->id,
            'artesao_id' => $tapete->artesao_id,
            'valor_total' => $tapete->preco,
            'endereco_entrega' => $ana->endereco_completo,
            'status_pagamento' => 'pendente',
            'status_entrega' => 'pendente',
        ], true);

        $itemModel->insert([
            'pedido_id' => $pedido2_id,
            'produto_id' => $tapete->id,
            'nome_produto_historico' => $tapete->nome,
            'preco_unitario_historico' => $tapete->preco,
            'quantidade' => 1,
            'subtotal' => $tapete->preco
        ]);


        // --- PEDIDO 3: João compra Blusa da Lurdes (Entregue) ---
        $pedido3_id = $pedidoModel->insert([
            'cliente_id' => $joao->id,
            'artesao_id' => $blusa->artesao_id,
            'valor_total' => $blusa->preco,
            'endereco_entrega' => $joao->endereco_completo,
            'status_pagamento' => 'pago',
            'status_entrega' => 'entregue',
        ], true);

        $itemModel->insert([
            'pedido_id' => $pedido3_id,
            'produto_id' => $blusa->id,
            'nome_produto_historico' => $blusa->nome,
            'preco_unitario_historico' => $blusa->preco,
            'quantidade' => 1,
            'subtotal' => $blusa->preco
        ]);


        // --- PEDIDO 4: Ana compra 2 Polvos da Fátima (Pago, Enviado) ---
        $valorPolvos = $polvo->preco * 2;
        $pedido4_id = $pedidoModel->insert([
            'cliente_id' => $ana->id,
            'artesao_id' => $polvo->artesao_id,
            'valor_total' => $valorPolvos,
            'endereco_entrega' => $ana->endereco_completo,
            'status_pagamento' => 'pago',
            'status_entrega' => 'enviado',
        ], true);

        $itemModel->insert([
            'pedido_id' => $pedido4_id,
            'produto_id' => $polvo->id,
            'nome_produto_historico' => $polvo->nome,
            'preco_unitario_historico' => $polvo->preco,
            'quantidade' => 2,
            'subtotal' => $valorPolvos
        ]);
    }
}
