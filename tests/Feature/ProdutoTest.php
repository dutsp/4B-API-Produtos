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
}