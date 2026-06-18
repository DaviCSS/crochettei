<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PedidoModel;
use App\Models\ProdutoModel;

class AdminController extends BaseController
{

    // Puxa um resumão de todas as vendas e métricas do e-commerce para a administração
    public function dashboard()
    {
        $pedidoModel = new PedidoModel();
        $produtoModel = new ProdutoModel();

        $totalProdutos = $produtoModel->where('estoque >', 0)->countAllResults();

        $metrics = $pedidoModel->metricsGlobais();

        $totalFaturado = (float) ($metrics->total_faturado ?? 0);
        $pedidosAtivos = (int) ($metrics->pedidos_pendentes ?? 0);

        $saldoAReceber = $pedidoModel->selectSum('valor_total')
            ->where('status_pagamento', 'pago')
            ->get()->getRow()->valor_total ?? 0;

        $pedidos = $pedidoModel->listarTodos();

        $dados = [
            'totalProdutos' => $totalProdutos,
            'totalFaturado' => $totalFaturado,
            'pedidosAtivos' => $pedidosAtivos,
            'saldoAReceber' => (float) $saldoAReceber,
            'pedidos'       => $pedidos
        ];

        return view('admin/dashboard', $dados);
    }

    // Confirma que o dinheiro caiu e avisa a artesã para começar a fazer a peça
    public function aprovarPagamento(int $id)
    {
        $pedidoModel = new PedidoModel();
        if ($pedidoModel->update($id, ['status_pagamento' => 'pago'])) {
            session()->setFlashdata('sucesso', "Pagamento do pedido #{$id} aprovado com sucesso! A artesã será notificada.");
        } else {
            session()->setFlashdata('erro', 'Erro ao aprovar o pagamento.');
        }
        return redirect()->to('/admin/dashboard');
    }

    // Cancela o pedido à força em caso de problemas, refletindo para a artesã e o cliente
    public function forcarCancelamento(int $id)
    {
        $pedidoModel = new PedidoModel();
        if ($pedidoModel->update($id, ['status_entrega' => 'cancelado'])) {
            session()->setFlashdata('sucesso', "Pedido #{$id} cancelado com sucesso. A ação já refletiu no painel da artesã e do cliente.");
        } else {
            session()->setFlashdata('erro', 'Erro ao cancelar o pedido.');
        }
        return redirect()->to('/admin/dashboard');
    }
}
