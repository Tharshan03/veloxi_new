<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\MerchantOrder;
use App\Models\MerchantOrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MerchantOrderDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_merchant_login()
    {
        $this->get(route('merchant.orders.index'))
            ->assertRedirect(route('merchant.dashboard.login'));
    }

    public function test_merchant_without_active_merchant_gets_controlled_response()
    {
        $merchantUser = $this->createMerchantUser();

        $this->actingAs($merchantUser)
            ->get(route('merchant.orders.index'))
            ->assertStatus(403)
            ->assertSee('Aucun commerce actif');
    }

    public function test_merchant_sees_only_orders_from_owned_merchant()
    {
        [$merchantUser, $merchant] = $this->createMerchantWithOwner();
        [, $otherMerchant] = $this->createMerchantWithOwner([
            'email' => 'other-merchant@example.com',
            'merchant_slug' => 'autre-merchant',
            'merchant_name' => 'Autre Merchant',
        ]);

        $visibleOrder = $this->createOrder($merchant, ['customer_name' => 'Client visible']);
        $hiddenOrder = $this->createOrder($otherMerchant, ['customer_name' => 'Client caché']);

        $this->actingAs($merchantUser)
            ->get(route('merchant.orders.index'))
            ->assertOk()
            ->assertSee('#'.$visibleOrder->id)
            ->assertSee('Client visible')
            ->assertDontSee('#'.$hiddenOrder->id)
            ->assertDontSee('Client caché');

        $this->actingAs($merchantUser)
            ->get(route('merchant.orders.show', $hiddenOrder))
            ->assertNotFound();
    }

    public function test_pending_order_can_be_accepted()
    {
        [$merchantUser, $merchant] = $this->createMerchantWithOwner();
        $order = $this->createOrder($merchant, ['status' => MerchantOrder::STATUS_PENDING]);

        $this->actingAs($merchantUser)
            ->post(route('merchant.orders.accept', $order))
            ->assertRedirect(route('merchant.orders.show', $order));

        $order->refresh();

        $this->assertSame(MerchantOrder::STATUS_ACCEPTED, $order->status);
        $this->assertNotNull($order->accepted_at);
    }

    public function test_invalid_status_transition_is_rejected_without_changing_order()
    {
        [$merchantUser, $merchant] = $this->createMerchantWithOwner();
        $order = $this->createOrder($merchant, ['status' => MerchantOrder::STATUS_REFUSED]);

        $this->actingAs($merchantUser)
            ->from(route('merchant.orders.show', $order))
            ->post(route('merchant.orders.accept', $order))
            ->assertRedirect(route('merchant.orders.show', $order))
            ->assertSessionHasErrors();

        $this->assertSame(MerchantOrder::STATUS_REFUSED, $order->fresh()->status);
    }

    private function createMerchantWithOwner(array $overrides = []): array
    {
        $owner = $this->createMerchantUser([
            'email' => $overrides['email'] ?? 'merchant'.uniqid().'@example.com',
        ]);

        $merchant = Merchant::create([
            'owner_user_id' => $owner->id,
            'name' => $overrides['merchant_name'] ?? 'Kebab du Blanc-Mesnil',
            'slug' => $overrides['merchant_slug'] ?? 'kebab-blancmesnil-'.uniqid(),
            'description' => 'Restaurant de test',
            'address' => '1 rue de test',
            'phone' => '0100000000',
            'status' => 1,
        ]);

        return [$owner, $merchant];
    }

    private function createMerchantUser(array $overrides = []): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'merchant', 'guard_name' => 'web']);

        $email = $overrides['email'] ?? 'merchant'.uniqid().'@example.com';

        $user = User::factory()->create(array_merge([
            'name' => 'Merchant Test',
            'username' => str_replace(['@', '.'], '_', $email),
            'email' => $email,
            'user_type' => 'merchant',
            'status' => 1,
        ], $overrides));

        $user->assignRole('merchant');

        return $user;
    }

    private function createOrder(Merchant $merchant, array $overrides = []): MerchantOrder
    {
        $client = User::factory()->create([
            'name' => $overrides['customer_name'] ?? 'Client Test',
            'username' => 'client_'.uniqid(),
            'email' => 'client'.uniqid().'@example.com',
            'user_type' => 'client',
            'status' => 1,
        ]);

        $order = MerchantOrder::create(array_merge([
            'user_id' => $client->id,
            'merchant_id' => $merchant->id,
            'status' => MerchantOrder::STATUS_PENDING,
            'fulfillment_type' => 'delivery',
            'subtotal_amount' => 19.80,
            'subtotal' => 19.80,
            'delivery_fee' => 3.50,
            'total_amount' => 23.30,
            'total' => 23.30,
            'delivery_address' => '10 rue de Paris',
            'delivery_city' => 'Le Blanc-Mesnil',
            'delivery_postal_code' => '93150',
            'customer_name' => $client->name,
            'customer_phone' => '0600000000',
        ], $overrides));

        MerchantOrderItem::create([
            'merchant_order_id' => $order->id,
            'merchant_product_id' => null,
            'product_name' => 'Menu kebab historique',
            'quantity' => 2,
            'unit_price' => 9.90,
            'total_price' => 19.80,
        ]);

        return $order;
    }
}
