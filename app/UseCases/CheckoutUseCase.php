<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Models\PedidoModel;
use App\Models\ProdutoModel;
use App\Models\ItemPedidoModel;
use CodeIgniter\Database\BaseConnection;

class CheckoutUseCase
{
    public function __construct(
        private readonly PedidoModel      $pedidoModel,
        private readonly ProdutoModel     $produtoModel,
        private readonly ItemPedidoModel  $itemPedidoModel,
        private readonly BaseConnection   $db
    ) {}

    // Tenta finalizar a compra: checa o estoque, debita as peças e salva o pedido completo
    public function executar(
        int    $clienteId,
        array  $itensCarrinho,
        string $enderecoEntrega
    ): int {

        if (empty($itensCarrinho)) {
            throw new \RuntimeException('Seu carrinho está vazio. Adicione produtos antes de finalizar.');
        }

        $produtosVerificados = [];
        $artesaoId           = null;
        $valorTotal          = 0.0;

        foreach ($itensCarrinho as $item) {
            $produto = $this->produtoModel->find((int) $item['produto_id']);

            if ($produto === null) {
                throw new \RuntimeException(
                    "Produto '{$item['nome']}' não encontrado ou foi removido do catálogo."
                );
            }

            if ($produto->estoque <= 0 && $produto->tipo === 'pronta_entrega') {
                throw new \RuntimeException(
                    "O produto '{$produto->nome}' está esgotado e não pode ser comprado. (RN19)"
                );
            }

            if (
                $produto->tipo === 'pronta_entrega' &&
                $produto->estoque < (int) $item['quantidade']
            ) {
                throw new \RuntimeException(
                    "Estoque insuficiente para '{$produto->nome}'. " .
                    "Disponível: {$produto->estoque} | Solicitado: {$item['quantidade']}. (RN19)"
                );
            }

            if ($artesaoId === null) {
                $artesaoId = (int) $produto->artesao_id;
            }

            $precoReal    = (float) $produto->preco;
            $quantidade   = (int) $item['quantidade'];
            $valorTotal  += round($precoReal * $quantidade, 2);

            $produtosVerificados[] = [
                'produto'        => $produto,
                'preco_real'     => $precoReal,
                'quantidade'     => $quantidade,
            ];
        }

        $this->db->transStart();

        try {

            $pedidoId = $this->pedidoModel->insert([
                'cliente_id'       => $clienteId,
                'artesao_id'       => $artesaoId,
                'valor_total'      => round($valorTotal, 2),
                'endereco_entrega' => $enderecoEntrega,
                'status_pagamento' => 'pendente',
                'status_entrega'   => 'em_producao',
            ], true);

            if (!$pedidoId) {
                throw new \RuntimeException('Erro ao registrar o pedido. Tente novamente.');
            }

            $itensBanco = [];
            foreach ($produtosVerificados as $itemVerificado) {
                $qtd      = $itemVerificado['quantidade'];
                $preco    = $itemVerificado['preco_real'];
                $itensBanco[] = [
                    'pedido_id'               => (int) $pedidoId,
                    'produto_id'              => (int) $itemVerificado['produto']->id,

                    'nome_produto_historico'  => $itemVerificado['produto']->nome,

                    'preco_unitario_historico' => $preco,
                    'quantidade'              => $qtd,
                    'subtotal'                => round($preco * $qtd, 2),
                ];
            }

            if (!$this->itemPedidoModel->inserirLote($itensBanco)) {
                throw new \RuntimeException('Erro ao registrar os itens do pedido.');
            }

            foreach ($produtosVerificados as $itemVerificado) {
                if ($itemVerificado['produto']->tipo === 'pronta_entrega') {
                    $sucesso = $this->produtoModel->decrementarEstoque(
                        (int) $itemVerificado['produto']->id,
                        $itemVerificado['quantidade']
                    );

                    if (!$sucesso) {
                        throw new \RuntimeException(
                            "Falha na baixa de estoque para '{$itemVerificado['produto']->nome}'. " .
                            'O produto pode ter sido esgotado por outro cliente. (RN11)'
                        );
                    }
                }

            }

        } catch (\Throwable $e) {

            $this->db->transRollback();
            throw new \RuntimeException($e->getMessage());
        }

        $this->db->transComplete();

        return (int) $pedidoId;
    }
}
