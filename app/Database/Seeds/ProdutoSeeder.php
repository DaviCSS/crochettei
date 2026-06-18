<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\ProdutoModel;
use App\Models\UsuarioModel;
use App\Models\CategoriaModel;

class ProdutoSeeder extends Seeder
{
    public function run()
    {
        $produtoModel = new ProdutoModel();
        $usuarioModel = new UsuarioModel();
        $categoriaModel = new CategoriaModel();

        // Buscar IDs para referenciar corretamente
        $maria = $usuarioModel->where('email', 'maria.artesa@crochettei.com')->first();
        $lurdes = $usuarioModel->where('email', 'lourdes.oliveira@email.com')->first();
        $fatima = $usuarioModel->where('email', 'fatima.silva@email.com')->first();

        $catAmigurumi = $categoriaModel->where('nome', 'Amigurumis')->first();
        $catDecoracao = $categoriaModel->where('nome', 'Decoração')->first();
        $catModa = $categoriaModel->where('nome', 'Roupas')->first();

        // Se por algum motivo as dependências não existirem, abortamos o seed
        if (!$maria || !$catAmigurumi) {
            echo "Aviso: Usuários ou Categorias não encontrados. Rode os seeders anteriores primeiro.\n";
            return;
        }

        $produtos = [
            [
                'artesao_id'      => $maria->id,
                'categoria_id'    => $catAmigurumi->id,
                'nome'            => 'Urso de Pelúcia Amigurumi',
                'descricao'       => 'Lindo ursinho feito à mão com fio 100% algodão e enchimento antialérgico. Ideal para presentear bebês e crianças.',
                'preco'           => 120.00,
                'prazo_confeccao' => 7,
                'estoque'         => 3,
                'tipo'            => 'pronta_entrega',
                'imagem_path'     => 'https://loremflickr.com/600/400/amigurumi',
            ],
            [
                'artesao_id'      => $maria->id,
                'categoria_id'    => $catDecoracao->id,
                'nome'            => 'Tapete de Barbante Cru',
                'descricao'       => 'Tapete rústico de crochê feito em barbante cru, tamanho 1,20m x 0,80m. Fica lindo na sala ou quarto.',
                'preco'           => 150.00,
                'prazo_confeccao' => 10,
                'estoque'         => 1,
                'tipo'            => 'pronta_entrega',
                'imagem_path'     => 'https://loremflickr.com/600/400/rug',
            ],
            [
                'artesao_id'      => $lurdes->id,
                'categoria_id'    => $catModa->id,
                'nome'            => 'Blusa de Frio em Crochê',
                'descricao'       => 'Blusa de lã quentinha e muito charmosa. Feita sob medida em até 15 dias.',
                'preco'           => 210.00,
                'prazo_confeccao' => 15,
                'estoque'         => 0, // Sem estoque pronto, depende de confecção
                'tipo'            => 'sob_encomenda',
                'imagem_path'     => 'https://loremflickr.com/600/400/sweater,crochet',
            ],
            [
                'artesao_id'      => $lurdes->id,
                'categoria_id'    => $catDecoracao->id,
                'nome'            => 'Manta para Sofá Colorida',
                'descricao'       => 'Manta super colorida feita com sobras de lã. Estilo Boho para dar vida à sua sala de estar.',
                'preco'           => 180.00,
                'prazo_confeccao' => 20,
                'estoque'         => 2,
                'tipo'            => 'pronta_entrega',
                'imagem_path'     => 'https://loremflickr.com/600/400/blanket,crochet',
            ],
            [
                'artesao_id'      => $fatima->id,
                'categoria_id'    => $catAmigurumi->id,
                'nome'            => 'Polvo Amigurumi para Prematuros',
                'descricao'       => 'Polvo feito com pontos bem fechados, perfeito para transmitir segurança aos recém-nascidos.',
                'preco'           => 45.00,
                'prazo_confeccao' => 5,
                'estoque'         => 10,
                'tipo'            => 'pronta_entrega',
                'imagem_path'     => 'https://loremflickr.com/600/400/octopus,amigurumi',
            ],
            [
                'artesao_id'      => $fatima->id,
                'categoria_id'    => $catDecoracao->id,
                'nome'            => 'Sousplat Natalino (Jogo de 4)',
                'descricao'       => 'Deixe sua mesa de Natal deslumbrante com este jogo de sousplats vermelhos com fio dourado.',
                'preco'           => 100.00,
                'prazo_confeccao' => 8,
                'estoque'         => 5,
                'tipo'            => 'pronta_entrega',
                'imagem_path'     => 'https://loremflickr.com/600/400/tablemat,crochet',
            ],
        ];

        foreach ($produtos as $prod) {
            if (!$produtoModel->where('nome', $prod['nome'])->first()) {
                $produtoModel->insert($prod);
            }
        }
    }
}
