<?php

namespace App\Repositories;

use App\Models\Produto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProdutoRepository
{
    
    public function search(array $filtros = [], int $porPagina = 10): LengthAwarePaginator
    {
        $query = Produto::query();

        if (!empty($filtros['nome'])) {
            $query->where('nome', 'like', '%' . $filtros['nome'] . '%');
        }

        if (isset($filtros['preco_min']) && $filtros['preco_min'] !== '') {
            $query->where('preco', '>=', $filtros['preco_min']);
        }

        if (isset($filtros['preco_max']) && $filtros['preco_max'] !== '') {
            $query->where('preco', '<=', $filtros['preco_max']);
        }

        if (isset($filtros['em_estoque']) && $filtros['em_estoque'] !== '') {
            $emEstoque = filter_var($filtros['em_estoque'], FILTER_VALIDATE_BOOLEAN);

            $emEstoque
                ? $query->where('quantidade', '>', 0)
                : $query->where('quantidade', '=', 0);
        }

        return $query->latest()->paginate($porPagina);
    }

    public function update(Produto $produto, array $dados): Produto
    {
        $produto->update($dados);

        return $produto;
    }

    public function delete(Produto $produto): bool
    {
        return (bool) $produto->delete();
    }
}
