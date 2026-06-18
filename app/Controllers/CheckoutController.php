<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProdutoModel;
use App\Models\PedidoModel;
use App\Models\CategoriaModel;
use App\UseCases\GerenciarCarrinhoUseCase;
use App\UseCases\CheckoutUseCase;
use App\Models\ItemPedidoModel;
use App\Models\UsuarioModel;
use CodeIgniter\Controller;

class CheckoutController extends Controller
{

    // Monta a vitrine principal listando todos os produtos ativos
    public function catalogo(): string
    {
        $categoriaId  = (int) ($this->request->getGet('categoria_id') ?? 0) ?: null;
        $produtoModel = new ProdutoModel();

        $produtos   = $produtoModel->listarParaVitrine($categoriaId);
        $categorias = (new CategoriaModel())->listarTodasOrdenadas();

        $carrinhoUseCase = new \App\UseCases\GerenciarCarrinhoUseCase(session());
        $qtdCarrinho = $carrinhoUseCase->contarItens();

        return view('catalogo/vitrine', [
            'produtos'             => $produtos,
            'categorias'           => $categorias,
            'categoriaSelecionada' => $categoriaId,
            'qtdCarrinho'          => $qtdCarrinho,
        ]);
    }

    public function detalhe(int $id): string
    {
        $produto = (new ProdutoModel())->buscarComDetalhes($id);

        if ($produto === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Produto não encontrado.'
            );
        }

        $carrinhoUseCase = new \App\UseCases\GerenciarCarrinhoUseCase(session());
        $qtdCarrinho = $carrinhoUseCase->contarItens();

        return view('catalogo/detalhe_produto', [
            'produto' => $produto,
            'qtdCarrinho' => $qtdCarrinho,
        ]);
    }

    public function verCarrinho(): string
    {
        $carrinhoUseCase = new GerenciarCarrinhoUseCase(session());

        return view('checkout/carrinho', [
            'itens'    => $carrinhoUseCase->obterCarrinho(),
            'total'    => $carrinhoUseCase->calcularTotal(),
            'qtdItens' => $carrinhoUseCase->contarItens(),
        ]);
    }

    // Tenta colocar o produto no carrinho, mas antes checa se ainda tem estoque
    public function adicionarAoCarrinho()
    {
        $produtoId  = (int) $this->request->getPost('produto_id');
        $quantidade = max(1, (int) $this->request->getPost('quantidade'));

        $produto = (new ProdutoModel())->buscarComDetalhes($produtoId);

        if ($produto === null) {
            session()->setFlashdata('erro', 'Produto não encontrado.');
            return redirect()->to('/catalogo');
        }

        $useCase = new GerenciarCarrinhoUseCase(session());
        $carrinho = $useCase->obterCarrinho();
        $qtdAtual = isset($carrinho[$produtoId]) ? $carrinho[$produtoId]['quantidade'] : 0;

        if ($produto->tipo === 'pronta_entrega' && ($qtdAtual + $quantidade) > $produto->estoque) {
            session()->setFlashdata('erro', "Estoque insuficiente. Temos apenas {$produto->estoque} unidade(s) de '{$produto->nome}'.");
            return redirect()->back();
        }

        $carrinhoAntigo = $useCase->obterCarrinho();
        $artesaoAntigo = !empty($carrinhoAntigo) ? reset($carrinhoAntigo)['artesao_id'] : null;

        try {
            $useCase->adicionar([
                'produto_id'  => $produto->id,
                'nome'        => $produto->nome,
                'preco'       => $produto->preco,
                'artesao_id'  => $produto->artesao_id,
                'imagem_path' => $produto->imagem_path,
            ], $quantidade);

            if ($artesaoAntigo !== null && $artesaoAntigo !== $produto->artesao_id) {
                session()->setFlashdata('aviso', "Como você escolheu um produto de uma nova artesã, seu carrinho anterior foi esvaziado. '{$produto->nome}' foi adicionado!");
            } else {
                session()->setFlashdata('sucesso', "'{$produto->nome}' adicionado ao carrinho!");
            }

        } catch (\RuntimeException $e) {
            session()->setFlashdata('aviso', $e->getMessage());
        }

        return redirect()->to('/carrinho');
    }

    public function removerDoCarrinho(int $produtoId)
    {
        (new GerenciarCarrinhoUseCase(session()))->remover($produtoId);
        session()->setFlashdata('sucesso', 'Item removido do carrinho.');
        return redirect()->to('/carrinho');
    }

    public function atualizarCarrinho()
    {
        $produtoId  = (int) $this->request->getPost('produto_id');
        $quantidade = (int) $this->request->getPost('quantidade');

        $produto = (new ProdutoModel())->find($produtoId);

        if ($produto && $produto->tipo === 'pronta_entrega' && $quantidade > $produto->estoque) {
            session()->setFlashdata('erro', "Estoque insuficiente. Temos apenas {$produto->estoque} unidade(s) de '{$produto->nome}'.");
            return redirect()->to('/carrinho');
        }

        (new GerenciarCarrinhoUseCase(session()))->atualizarQuantidade($produtoId, $quantidade);
        return redirect()->to('/carrinho');
    }

    // Mostra a tela final de pagamento puxando os dados do cliente e do carrinho
    public function exibirCheckout(): string
    {
        $carrinhoUseCase = new GerenciarCarrinhoUseCase(session());

        if ($carrinhoUseCase->estaVazio()) {
            session()->setFlashdata('aviso', 'Seu carrinho está vazio. Adicione produtos primeiro.');
            return redirect()->to('/catalogo');
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find(session()->get('user_id'));

        return view('checkout/checkout', [
            'itens'  => $carrinhoUseCase->obterCarrinho(),
            'total'  => $carrinhoUseCase->calcularTotal(),
            'usuario' => $usuario,
        ]);
    }

    // Fecha a compra, limpa o carrinho e avisa a artesã
    public function finalizarPedido()
    {
        $clienteId = (int) session()->get('user_id');
        $post      = $this->request->getPost();

        $enderecoEntrega = trim($post['endereco_entrega'] ?? '');

        if (empty(trim($enderecoEntrega))) {
            session()->setFlashdata('erro', 'O endereço de entrega é obrigatório.');
            return redirect()->to('/checkout');
        }

        $carrinhoUseCase = new GerenciarCarrinhoUseCase(session());
        $itensCarrinho   = $carrinhoUseCase->obterCarrinho();

        try {
            $useCase  = new CheckoutUseCase(
                new PedidoModel(),
                new ProdutoModel(),
                new ItemPedidoModel(),
                \Config\Database::connect()
            );

            $pedidoId = $useCase->executar($clienteId, $itensCarrinho, $enderecoEntrega);

            $carrinhoUseCase->limpar();

            session()->setFlashdata(
                'sucesso',
                "✅ Pedido #{$pedidoId} realizado com sucesso! A artesã já foi notificada."
            );
            return redirect()->to('/cliente/pedidos');

        } catch (\RuntimeException $e) {
            session()->setFlashdata('erro', $e->getMessage());
            return redirect()->to('/checkout');
        }
    }

    public function dashboardCliente(): string
    {
        $clienteId = (int) session()->get('user_id');
        $pedidos   = (new PedidoModel())->listarPorCliente($clienteId);

        return view('cliente/dashboard', [
            'usuario' => (object) [
                'nome'  => session()->get('user_nome'),
                'email' => session()->get('user_email'),
            ],
            'pedidos' => array_slice($pedidos, 0, 5),
        ]);
    }

    public function meusPedidos(): string
    {
        $clienteId = (int) session()->get('user_id');
        $pedidos   = (new PedidoModel())->listarPorCliente($clienteId);

        return view('cliente/pedidos', ['pedidos' => $pedidos]);
    }
}
