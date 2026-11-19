<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $operatore;
    private Customer $cliente;
    private Product $caffe;
    private Product $latte;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operatore = User::factory()->create(['role' => 'operatore']);
        $this->cliente = Customer::factory()->create();
        $this->caffe = Product::factory()->create(['nome' => 'Caffè', 'prezzo_cents' => 1650, 'iva' => 22, 'giacenza' => 10]);
        $this->latte = Product::factory()->create(['nome' => 'Latte', 'prezzo_cents' => 1000, 'iva' => 4, 'giacenza' => 3]);
        Sanctum::actingAs($this->operatore);
    }

    private function createOrder(array $items): Order
    {
        $res = $this->postJson('/api/orders', ['customer_id' => $this->cliente->id, 'items' => $items])->assertCreated();

        return Order::findOrFail($res->json('data.id'));
    }

    public function test_guests_cannot_use_the_api(): void
    {
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_an_order_calculates_totals_and_number(): void
    {
        $res = $this->postJson('/api/orders', [
            'customer_id' => $this->cliente->id,
            'items' => [['product_id' => $this->caffe->id, 'quantita' => 2], ['product_id' => $this->latte->id, 'quantita' => 1]],
        ])->assertCreated();

        $res->assertJsonPath('data.stato', 'bozza')
            ->assertJsonPath('data.imponibile_cents', 4300)   // 2×16,50 + 10,00
            ->assertJsonPath('data.iva_cents', 766)           // 7,26 + 0,40
            ->assertJsonPath('data.totale_cents', 5066)
            ->assertJsonPath('data.numero', sprintf('ORD-%d-0001', now()->year));
    }

    public function test_confirming_an_order_takes_stock_and_logs_movements(): void
    {
        $order = $this->createOrder([['product_id' => $this->caffe->id, 'quantita' => 4]]);

        $this->postJson("/api/orders/{$order->id}/stato", ['stato' => 'confermato'])->assertOk()->assertJsonPath('data.stato', 'confermato');

        $this->assertSame(6, $this->caffe->refresh()->giacenza);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $this->caffe->id, 'order_id' => $order->id, 'quantita' => -4, 'tipo' => 'ordine']);
    }

    public function test_an_order_cannot_be_confirmed_without_enough_stock(): void
    {
        $order = $this->createOrder([['product_id' => $this->caffe->id, 'quantita' => 2], ['product_id' => $this->latte->id, 'quantita' => 5]]);

        $this->postJson("/api/orders/{$order->id}/stato", ['stato' => 'confermato'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('stock');

        // Nessuna modifica parziale: la transazione annulla tutto
        $this->assertSame(10, $this->caffe->refresh()->giacenza);
        $this->assertSame(3, $this->latte->refresh()->giacenza);
        $this->assertSame('bozza', $order->refresh()->stato->value);
    }

    public function test_cancelling_a_confirmed_order_returns_the_stock(): void
    {
        $order = $this->createOrder([['product_id' => $this->caffe->id, 'quantita' => 4]]);
        $this->postJson("/api/orders/{$order->id}/stato", ['stato' => 'confermato']);
        $this->postJson("/api/orders/{$order->id}/stato", ['stato' => 'annullato'])->assertOk();

        $this->assertSame(10, $this->caffe->refresh()->giacenza);
        $this->assertDatabaseHas('stock_movements', ['order_id' => $order->id, 'quantita' => 4, 'tipo' => 'annullo']);
    }

    public function test_invalid_status_changes_are_rejected(): void
    {
        $order = $this->createOrder([['product_id' => $this->caffe->id, 'quantita' => 1]]);

        $this->postJson("/api/orders/{$order->id}/stato", ['stato' => 'consegnato'])->assertUnprocessable();

        foreach (['confermato', 'spedito', 'consegnato'] as $s) {
            $this->postJson("/api/orders/{$order->id}/stato", ['stato' => $s])->assertOk();
        }
        $this->postJson("/api/orders/{$order->id}/stato", ['stato' => 'annullato'])->assertUnprocessable();
    }

    public function test_only_drafts_can_be_edited_or_deleted(): void
    {
        $order = $this->createOrder([['product_id' => $this->caffe->id, 'quantita' => 1]]);
        $this->postJson("/api/orders/{$order->id}/stato", ['stato' => 'confermato']);

        $this->putJson("/api/orders/{$order->id}", ['customer_id' => $this->cliente->id, 'items' => [['product_id' => $this->caffe->id, 'quantita' => 9]]])
            ->assertUnprocessable();
        $this->deleteJson("/api/orders/{$order->id}")->assertUnprocessable();
    }

    public function test_order_validation_messages(): void
    {
        $this->postJson('/api/orders', ['customer_id' => null, 'items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id', 'items']);

        $this->postJson('/api/orders', ['customer_id' => $this->cliente->id, 'items' => [
            ['product_id' => $this->caffe->id, 'quantita' => 1], ['product_id' => $this->caffe->id, 'quantita' => 2],
        ]])->assertJsonValidationErrors('items.1.product_id');
    }

    public function test_operators_cannot_manage_products_or_users(): void
    {
        $this->postJson('/api/products', ['sku' => 'X-1', 'nome' => 'X', 'prezzo_cents' => 100, 'iva' => 22, 'scorta_minima' => 0, 'giacenza' => 1])->assertForbidden();
        $this->postJson("/api/products/{$this->caffe->id}/carico", ['quantita' => 5])->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
    }

    public function test_admin_can_load_stock_but_not_below_zero(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson("/api/products/{$this->latte->id}/carico", ['quantita' => 12, 'nota' => 'Fornitore'])->assertOk()->assertJsonPath('data.giacenza', 15);
        $this->postJson("/api/products/{$this->latte->id}/carico", ['quantita' => -20])->assertJsonValidationErrors('quantita');
    }

    public function test_customer_vat_number_is_validated(): void
    {
        $this->postJson('/api/customers', ['ragione_sociale' => 'Bar Test', 'piva' => '12345678901'])->assertJsonValidationErrors('piva');
        $this->postJson('/api/customers', ['ragione_sociale' => 'Bar Test', 'piva' => '12345678903'])->assertCreated();
    }

    public function test_order_pdf_is_generated(): void
    {
        $order = $this->createOrder([['product_id' => $this->caffe->id, 'quantita' => 1]]);

        $res = $this->get("/api/orders/{$order->id}/pdf")->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
    }

    public function test_dashboard_returns_kpis(): void
    {
        $order = $this->createOrder([['product_id' => $this->caffe->id, 'quantita' => 2]]);
        $this->postJson("/api/orders/{$order->id}/stato", ['stato' => 'confermato']);

        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('kpi.fatturato_mese_cents', 3300)
            ->assertJsonPath('kpi.da_spedire', 1)
            ->assertJsonCount(6, 'mesi');
    }
}
