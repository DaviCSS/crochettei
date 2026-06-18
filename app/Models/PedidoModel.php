<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class PedidoModel extends Model
{

    protected $table      = 'pedidos';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'cliente_id',
        'artesao_id',
        'valor_total',
        'endereco_entrega',
        'status_pagamento',
        'status_entrega',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'cliente_id' => [
            'label'  => 'Cliente',
            'rules'  => 'required|integer',
            'errors' => ['required' => 'O pedido deve ter um cliente associado.'],
        ],
        'artesao_id' => [
            'label'  => 'Artesã',
            'rules'  => 'required|integer',
            'errors' => ['required' => 'O pedido deve ter uma artesã associada.'],
        ],
        'valor_total' => [
            'label'  => 'Valor Total',
            'rules'  => 'required|decimal|greater_than[0]',
            'errors' => [
                'required'     => 'O valor total do pedido é obrigatório.',
                'decimal'      => 'O valor total deve ser numérico.',
                'greater_than' => 'O valor total deve ser maior que zero.',
            ],
        ],
        'endereco_entrega' => [
            'label'  => 'Endereço de Entrega',
            'rules'  => 'required',
            'errors' => ['required' => 'O endereço de entrega é obrigatório.'],
        ],
    ];

    // Traz todos os pedidos feitos por um cliente específico
    public function listarPorCliente(int $clienteId): array
    {
        return $this->db->table('pedidos pe')
            ->select('pe.*, u.nome AS artesa_nome, u.telefone AS artesa_telefone')
            ->join('usuarios u', 'u.id = pe.artesao_id', 'left')
            ->where('pe.cliente_id', $clienteId)
            ->orderBy('pe.created_at', 'DESC')
            ->get()
            ->getResultObject();
    }

    public function listarPorArtesa(int $artesaoId): array
    {
        return $this->db->table('pedidos pe')
            ->select('pe.*, u.nome AS cliente_nome, u.telefone AS cliente_telefone')
            ->join('usuarios u', 'u.id = pe.cliente_id', 'left')
            ->where('pe.artesao_id', $artesaoId)
            ->orderBy('pe.created_at', 'DESC')
            ->get()
            ->getResultObject();
    }

    // Puxa o pedido junto com as peças que estão dentro dele
    public function buscarComItens(int $pedidoId): ?object
    {
        $pedido = $this->find($pedidoId);

        if ($pedido === null) {
            return null;
        }

        $pedido->itens = $this->db->table('itens_pedido ip')
            ->select('ip.*, p.nome AS produto_nome, p.imagem_path')
            ->join('produtos p', 'p.id = ip.produto_id', 'left')
            ->where('ip.pedido_id', $pedidoId)
            ->get()
            ->getResultObject();

        return $pedido;
    }

    public function atualizarStatusEntrega(int $pedidoId, string $novoStatus): bool
    {
        $statusValidos = ['em_producao', 'aguardando_coleta', 'enviado', 'entregue', 'cancelado'];

        if (!in_array($novoStatus, $statusValidos, true)) {
            return false;
        }

        return $this->update($pedidoId, ['status_entrega' => $novoStatus]);
    }

    public function atualizarStatusPagamento(int $pedidoId, string $status): bool
    {
        if (!in_array($status, ['pendente', 'pago'], true)) {
            return false;
        }

        return $this->update($pedidoId, ['status_pagamento' => $status]);
    }

    public function metricsArtesa(int $artesaoId): object
    {
        return $this->db->table('pedidos')
            ->select('
                COUNT(*) AS total_pedidos,
                SUM(valor_total) AS total_faturado,
                SUM(CASE WHEN status_pagamento = "pendente" THEN 1 ELSE 0 END) AS pedidos_pendentes,
                SUM(CASE WHEN status_entrega = "entregue" THEN 1 ELSE 0 END) AS pedidos_entregues
            ')
            ->where('artesao_id', $artesaoId)
            ->get()
            ->getRowObject();
    }

    public function metricsGlobais(): object
    {
        return $this->db->table('pedidos')
            ->select('
                COUNT(*) AS total_pedidos,
                SUM(valor_total) AS total_faturado,
                SUM(CASE WHEN status_pagamento = "pendente" THEN 1 ELSE 0 END) AS pedidos_pendentes,
                SUM(CASE WHEN status_entrega = "entregue" THEN 1 ELSE 0 END) AS pedidos_entregues
            ')
            ->get()
            ->getRowObject();
    }

    public function listarTodos(): array
    {
        return $this->db->table('pedidos pe')
            ->select('pe.*, c.nome AS cliente_nome, a.nome AS artesa_nome')
            ->join('usuarios c', 'c.id = pe.cliente_id', 'left')
            ->join('usuarios a', 'a.id = pe.artesao_id', 'left')
            ->orderBy('pe.created_at', 'DESC')
            ->get()
            ->getResultObject();
    }
}
