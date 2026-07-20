<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\MerchantCategory;
use App\Models\MerchantOrder;
use App\Models\MerchantProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantRestaurantTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_merchant_is_displayed_by_slug()
    {
        [$merchant, $product] = $this->createMerchantWithProduct();

        $this->get('/'.$merchant->slug)
            ->assertOk()
            ->assertSee($merchant->name)
            ->assertSee($product->name);
    }

    public function test_missing_merchant_returns_404()
    {
        $this->get('/merchant-inexistant')->assertNotFound();
    }

    public function test_product_can_be_added_to_cart()
    {
        [, $product] = $this->createMerchantWithProduct();

        $this->post(route('merchant.cart.add', $product), ['quantity' => 2])
            ->assertRedirect();

        $cart = session('merchant_cart');

        $this->assertSame($product->merchant_id, $cart['merchant_id']);
        $this->assertSame(2, $cart['items'][$product->id]['quantity']);
        $this->assertSame(19.8, $cart['items'][$product->id]['line_total']);
    }

    public function test_cart_quantity_can_be_updated()
    {
        [, $product] = $this->createMerchantWithProduct();

        $this->post(route('merchant.cart.add', $product), ['quantity' => 1]);
        $this->patch(route('merchant.cart.update', $product), ['quantity' => 3])
            ->assertRedirect(route('merchant.cart.show'));

        $this->assertSame(3, session('merchant_cart.items.'.$product->id.'.quantity'));
    }

    public function test_product_can_be_removed_from_cart()
    {
        [, $product] = $this->createMerchantWithProduct();

        $this->post(route('merchant.cart.add', $product), ['quantity' => 1]);
        $this->delete(route('merchant.cart.remove', $product))
            ->assertRedirect(route('merchant.cart.show'));

        $this->assertNull(session('merchant_cart'));
    }

    public function test_checkout_requires_authentication()
    {
        $this->get(route('merchant.checkout.show'))
            ->assertRedirect(route('merchant.login'));
    }

    public function test_pending_order_is_created_from_checkout()
    {
        [$merchant, $product] = $this->createMerchantWithProduct();
        $user = $this->createClientUser(['contact_number' => '0600000001']);

        $this->post(route('merchant.cart.add', $product), ['quantity' => 2]);

        $this->actingAs($user)
            ->post(route('merchant.checkout.store'), $this->checkoutPayload())
            ->assertRedirect();

        $order = MerchantOrder::first();

        $this->assertNotNull($order);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame($merchant->id, $order->merchant_id);
        $this->assertSame(MerchantOrder::STATUS_PENDING, $order->status);
        $this->assertSame(19.8, (float) $order->total_amount);
        $this->assertCount(1, $order->items);
    }

    public function test_checkout_recalculates_prices_from_database()
    {
        [, $product] = $this->createMerchantWithProduct(['price' => 5.00]);
        $user = $this->createClientUser();

        $this->post(route('merchant.cart.add', $product), ['quantity' => 1]);
        $product->update(['price' => 9.25]);

        $this->actingAs($user)
            ->post(route('merchant.checkout.store'), $this->checkoutPayload())
            ->assertRedirect();

        $this->assertSame(9.25, (float) MerchantOrder::first()->total_amount);
    }

    public function test_cart_cannot_mix_products_from_two_merchants()
    {
        [, $firstProduct] = $this->createMerchantWithProduct();
        [, $secondProduct] = $this->createMerchantWithProduct([
            'merchant_slug' => 'autre-restaurant',
            'merchant_name' => 'Autre Restaurant',
            'product_name' => 'Autre produit',
        ]);

        $this->post(route('merchant.cart.add', $firstProduct), ['quantity' => 1]);

        $this->post(route('merchant.cart.add', $secondProduct), ['quantity' => 1])
            ->assertRedirect(route('merchant.cart.show'))
            ->assertSessionHasErrors();

        $this->assertArrayHasKey($firstProduct->id, session('merchant_cart.items'));
        $this->assertArrayNotHasKey($secondProduct->id, session('merchant_cart.items'));
    }

    public function test_cart_is_cleared_after_successful_order()
    {
        [, $product] = $this->createMerchantWithProduct();
        $user = $this->createClientUser();

        $this->post(route('merchant.cart.add', $product), ['quantity' => 1]);

        $this->actingAs($user)
            ->post(route('merchant.checkout.store'), $this->checkoutPayload())
            ->assertRedirect();

        $this->assertNull(session('merchant_cart'));
    }

    private function createMerchantWithProduct(array $overrides = []): array
    {
        $merchant = Merchant::create([
            'name' => $overrides['merchant_name'] ?? 'Kebab du Blanc-Mesnil',
            'slug' => $overrides['merchant_slug'] ?? 'kebab-blancmesnil',
            'description' => 'Restaurant de test',
            'address' => '1 rue de test',
            'phone' => '0100000000',
            'status' => 1,
        ]);

        $category = MerchantCategory::create([
            'merchant_id' => $merchant->id,
            'name' => 'Menus',
            'slug' => 'menus',
            'position' => 1,
            'status' => 1,
        ]);

        $product = MerchantProduct::create([
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => $overrides['product_name'] ?? 'Menu kebab',
            'description' => 'Produit de test',
            'price' => $overrides['price'] ?? 9.90,
            'status' => 1,
            'sort_order' => 1,
        ]);

        return [$merchant, $product];
    }

    private function createClientUser(array $overrides = []): User
    {
        $email = $overrides['email'] ?? 'client'.uniqid().'@example.com';

        return User::factory()->create(array_merge([
            'name' => 'Client Test',
            'username' => str_replace(['@', '.'], '_', $email),
            'email' => $email,
            'user_type' => 'client',
            'status' => 1,
        ], $overrides));
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Client Test',
            'customer_email' => 'client@example.com',
            'customer_phone' => '0600000000',
            'delivery_address' => '10 rue de Paris',
            'delivery_address_line2' => 'Bâtiment A',
            'delivery_city' => 'Le Blanc-Mesnil',
            'delivery_postal_code' => '93150',
            'delivery_instructions' => 'Sonner à l’entrée',
        ];
    }
}
