<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Services\ProdutoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProdutoTest extends TestCase
{
 

    use RefreshDatabase;


    public function test_cria_produto_com_sucesso(): void
    {
        $this->postJson('/api/produtos', [
            'nome' => 'Lajota',
            'preco_custo' => 100,
            'preco' => 110,
        ])->assertCreated();

        $this->assertDatabaseHas('produtos', [
            'nome' => 'Lajota']);
    }

    public function test_nao_cria_produto_com_preco_abaixo_da_margem_minima(): void
    {
 
        $this->postJson('/api/produtos', [
            'nome' => 'Lajota',
            'preco_custo' => 100,
            'preco' => 109,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('preco');

        $this->assertDatabaseMissing('produtos', [
            'nome' => 'Lajota']);
    }

    public function test_service_atualiza_produto_com_sucesso(): void
    {
        $produto = Produto::create([
            'nome' => 'Lajota',
            'preco_custo' => 100,
            'preco' => 110,
        ]);

        $atualizado = (new ProdutoService())->update($produto, [
            'preco' => 150,
        ]);

        $this->assertEquals(150, $atualizado->preco);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'nome' => 'Lajota',
            'preco' => 150,
        ]);
    }

    public function test_service_nao_atualiza_produto_com_preco_abaixo_da_margem_minima(): void
    {
        $produto = Produto::create([
            'nome' => 'Lajota',
            'preco_custo' => 100,
            'preco' => 110,
        ]);

        try {
            (new ProdutoService())->update($produto, [
                'preco' => 105,
            ]);

            $this->fail('Era esperado ValidationException para preço abaixo da margem mínima.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('preco', $e->errors());
        }

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'preco' => 110,
        ]);
    }

    private function criarProdutos(): void
    {
        Produto::create(['nome' => 'Lajota', 'preco_custo' => 50, 'preco' => 60, 'quantidade' => 10]);
        Produto::create(['nome' => 'Cimento', 'preco_custo' => 20, 'preco' => 30, 'quantidade' => 0]);
        Produto::create(['nome' => 'Areia lavada', 'preco_custo' => 100, 'preco' => 120, 'quantidade' => 5]);
    }

    public function test_search_filtra_por_nome(): void
    {
        $this->criarProdutos();

        $this->getJson('/api/produtos?nome=laj')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Lajota');
    }

    public function test_search_filtra_por_faixa_de_preco(): void
    {
        $this->criarProdutos();

        $this->getJson('/api/produtos?preco_min=50&preco_max=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Lajota');
    }

    public function test_search_filtra_por_estoque(): void
    {
        $this->criarProdutos();

        $this->getJson('/api/produtos?em_estoque=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/produtos?em_estoque=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Cimento');
    }

    public function test_atualiza_produto_pela_api(): void
    {
        $produto = Produto::create(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

        $this->putJson("/api/produtos/{$produto->id}", ['preco' => 150])
            ->assertOk()
            ->assertJsonPath('data.preco', 150);

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'preco' => 150]);
    }

    public function test_nao_atualiza_produto_com_preco_abaixo_da_margem_pela_api(): void
    {
        $produto = Produto::create(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

        $this->putJson("/api/produtos/{$produto->id}", ['preco' => 105])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('preco');

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'preco' => 110]);
    }

    public function test_remove_produto(): void
    {
        $produto = Produto::create(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

        $this->deleteJson("/api/produtos/{$produto->id}")->assertNoContent();

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
    }

    public function test_remover_produto_inexistente_retorna_404(): void
    {
        $this->deleteJson('/api/produtos/999')->assertNotFound();
    }
}
