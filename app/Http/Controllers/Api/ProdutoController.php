<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProdutoResource;
use App\Models\Produto;
use App\Repositories\ProdutoRepository;
use App\Services\ProdutoService;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{

    public function __construct(
        private ProdutoService $service,
        private ProdutoRepository $repository,
    ) {}

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'nome' => ['sometimes', 'string', 'max:255'],
            'preco_min' => ['sometimes', 'numeric', 'min:0'],
            'preco_max' => ['sometimes', 'numeric', 'min:0'],
            'em_estoque' => ['sometimes', 'boolean'],
        ]);

        return ProdutoResource::collection($this->repository->search($filtros, 10));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => 'nullable|string',
            'preco_custo' => ['required', 'numeric', 'min:0'],
            'preco' => ['required', 'numeric', 'min:0'],
            'quantidade' => 'sometimes|integer|min:0',
        ]);

        $produto = $this->service->create($dados);

        return new ProdutoResource($produto);
    }

    public function show(Produto $produto)
    {
        return new ProdutoResource($produto);
    }

    public function update(Request $request, Produto $produto)
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'string', 'max:255'],
            'descricao' => 'nullable|string',
            'preco_custo' => ['sometimes', 'numeric', 'min:0'],
            'preco' => ['sometimes', 'numeric', 'min:0'],
            'quantidade' => 'sometimes|integer|min:0',
        ]);

        $produto = $this->service->update($produto, $dados);

        return new ProdutoResource($produto);
    }


    public function destroy(Produto $produto)
    {
        $this->repository->delete($produto);

        return response()->noContent();
    }
}
