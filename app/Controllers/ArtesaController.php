<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProdutoModel;
use App\Models\PedidoModel;
use App\Models\CategoriaModel;
use App\UseCases\CadastrarProdutoUseCase;
use App\UseCases\EditarProdutoUseCase;
use CodeIgniter\Controller;

class ArtesaController extends Controller
{

    // Puxa as métricas de vendas e produtos para montar a tela inicial da artesã
    public function dashboard(): string
    {
        $artesaoId    = (int) session()->get('user_id');
        $pedidoModel  = new PedidoModel();
        $produtoModel = new ProdutoModel();

        $metricas      = $pedidoModel->metricsArtesa($artesaoId);
        $totalProdutos = count($produtoModel->listarPorArtesa($artesaoId));

        return view('artesa/dashboard', [
            'usuario'          => (object) [
                'nome'     => session()->get('user_nome'),
                'email'    => session()->get('user_email'),
            ],
            'totalProdutos'    => $totalProdutos,
            'totalPedidos'     => (int)   ($metricas->total_pedidos     ?? 0),
            'totalFaturado'    => (float)  ($metricas->total_faturado    ?? 0),
            'pedidosPendentes' => (int)   ($metricas->pedidos_pendentes  ?? 0),
            'pedidosEntregues' => (int)   ($metricas->pedidos_entregues  ?? 0),
        ]);
    }

    public function apoio(): string
    {
        return view('artesa/apoio', [
            'usuario' => (object) [
                'nome'  => session()->get('user_nome'),
                'email' => session()->get('user_email'),
            ],
        ]);
    }

    public function listarProdutos(): string
    {
        $artesaoId = (int) session()->get('user_id');
        $produtos  = (new ProdutoModel())->listarPorArtesa($artesaoId);

        return view('artesa/meus_produtos', ['produtos' => $produtos]);
    }

    public function exibirFormCriacao(): string
    {
        $categorias = (new CategoriaModel())->listarParaDropdown();

        return view('artesa/cadastro_produto', ['categorias' => $categorias]);
    }

    // Trata a foto enviada e salva o novo produto na loja
    public function criarProduto()
    {
        $artesaoId = (int) session()->get('user_id');
        $imagem    = $this->request->getFile('foto');

        $dados = [
            'categoria_id'    => $this->request->getPost('categoria_id'),
            'nome'            => $this->request->getPost('nome'),
            'descricao'       => $this->request->getPost('descricao'),
            'preco'           => $this->request->getPost('preco'),
            'prazo_confeccao' => $this->request->getPost('tipo') === 'pronta_entrega' ? 1 : $this->request->getPost('prazo_confeccao'),
            'estoque'         => $this->request->getPost('tipo') === 'sob_encomenda' ? 0 : $this->request->getPost('estoque'),
            'tipo'            => $this->request->getPost('tipo'),
        ];

        try {
            $useCase = new CadastrarProdutoUseCase(new ProdutoModel());
            $useCase->executar($dados, $artesaoId, $imagem);

            session()->setFlashdata('sucesso', '🧶 Produto publicado com sucesso na sua vitrine!');
            return redirect()->to('/artesa/produtos');

        } catch (\RuntimeException $e) {
            session()->setFlashdata('erro', $e->getMessage());
            return redirect()->to('/artesa/produtos/criar');
        }
    }

    public function exibirFormEdicao(int $id): string
    {
        $artesaoId    = (int) session()->get('user_id');
        $produtoModel = new ProdutoModel();
        $produto      = $produtoModel->buscarParaEdicao($id, $artesaoId);

        if ($produto === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Produto não encontrado ou você não tem permissão para editá-lo.'
            );
        }

        $categorias = (new CategoriaModel())->listarParaDropdown();

        return view('artesa/editar_produto', [
            'produto'    => $produto,
            'categorias' => $categorias,
        ]);
    }

    // Edita os dados e a foto do produto, garantindo que ele pertence à artesã logada
    public function atualizarProduto(int $id)
    {
        $artesaoId = (int) session()->get('user_id');
        $imagem    = $this->request->getFile('foto');

        $dados = [
            'categoria_id'    => $this->request->getPost('categoria_id'),
            'nome'            => $this->request->getPost('nome'),
            'descricao'       => $this->request->getPost('descricao'),
            'preco'           => $this->request->getPost('preco'),
            'prazo_confeccao' => $this->request->getPost('tipo') === 'pronta_entrega' ? 1 : $this->request->getPost('prazo_confeccao'),
            'estoque'         => $this->request->getPost('tipo') === 'sob_encomenda' ? 0 : $this->request->getPost('estoque'),
            'tipo'            => $this->request->getPost('tipo'),
        ];

        try {
            $useCase = new EditarProdutoUseCase(new ProdutoModel());
            $useCase->executar($id, $dados, $artesaoId, $imagem);

            session()->setFlashdata('sucesso', 'Produto atualizado com sucesso!');
            return redirect()->to('/artesa/produtos');

        } catch (\RuntimeException $e) {
            session()->setFlashdata('erro', $e->getMessage());
            return redirect()->to("/artesa/produtos/editar/{$id}");
        }
    }

    // Remove o produto da vitrine
    public function excluirProduto(int $id)
    {
        $artesaoId = (int) session()->get('user_id');

        try {
            $useCase = new EditarProdutoUseCase(new ProdutoModel());
            $useCase->excluir($id, $artesaoId);

            session()->setFlashdata('sucesso', 'Produto removido da vitrine com sucesso.');

        } catch (\RuntimeException $e) {
            session()->setFlashdata('erro', $e->getMessage());
        }

        return redirect()->to('/artesa/produtos');
    }

    public function listarPedidos(): string
    {
        $artesaoId = (int) session()->get('user_id');
        $pedidos   = (new PedidoModel())->listarPorArtesa($artesaoId);

        return view('artesa/pedidos', ['pedidos' => $pedidos]);
    }

    // Muda a etapa do pedido (ex: de 'em produção' para 'enviado aos correios')
    public function atualizarStatusPedido(int $id)
    {
        $novoStatus   = $this->request->getPost('status_entrega');
        $pedidoModel  = new PedidoModel();

        $pedido = $pedidoModel->find($id);
        if ($pedido === null || (int) $pedido->artesao_id !== (int) session()->get('user_id')) {
            session()->setFlashdata('erro', 'Ação não autorizada. (RN15)');
            return redirect()->to('/artesa/pedidos');
        }

        if ($pedidoModel->atualizarStatusEntrega($id, $novoStatus)) {
            session()->setFlashdata('sucesso', 'Status do pedido atualizado com sucesso!');
        } else {
            session()->setFlashdata('erro', 'Status inválido. Tente novamente.');
        }

        return redirect()->to('/artesa/pedidos');
    }

    public function atualizarPagamentoPedido(int $id)
    {
        $pedidoModel  = new PedidoModel();

        $pedido = $pedidoModel->find($id);
        if ($pedido === null || (int) $pedido->artesao_id !== (int) session()->get('user_id')) {
            session()->setFlashdata('erro', 'Ação não autorizada. (RN15)');
            return redirect()->to('/artesa/pedidos');
        }

        if ($pedidoModel->update($id, ['status_pagamento' => 'pago'])) {
            session()->setFlashdata('sucesso', 'Pagamento confirmado com sucesso!');
        } else {
            session()->setFlashdata('erro', 'Erro ao atualizar o pagamento. Tente novamente.');
        }

        return redirect()->to('/artesa/pedidos');
    }

    public function relatorio(): string
    {
        $artesaoId = (int) session()->get('user_id');
        $pedidos   = (new PedidoModel())->listarPorArtesa($artesaoId);
        $metricas  = (new PedidoModel())->metricsArtesa($artesaoId);

        return view('artesa/relatorio_vendas', [
            'pedidos'  => $pedidos,
            'metricas' => $metricas,
        ]);
    }
}
