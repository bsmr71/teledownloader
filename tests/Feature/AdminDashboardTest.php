<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $regularUser;

    protected Plan $weeklyPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default plans & settings
        $this->seed(PlanSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->adminUser = User::factory()->create([
            'name' => 'Bismar Admin',
            'email' => 'bismar71@gmail.com',
            'password' => bcrypt('zabuaz71'),
            'role' => 'admin',
        ]);

        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $this->weeklyPlan = Plan::where('code', 'weekly')->first();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_login_successfully(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'bismar71@gmail.com',
            'password' => 'zabuaz71',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->adminUser);
    }

    public function test_admin_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Overview');
    }

    public function test_admin_can_grant_direct_subscription_to_user(): void
    {
        $response = $this->actingAs($this->adminUser)->post("/admin/users/{$this->regularUser->id}/grant", [
            'plan_id' => $this->weeklyPlan->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($this->regularUser->fresh()->isPro());
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->regularUser->id,
            'plan_id' => $this->weeklyPlan->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_extend_subscription(): void
    {
        $sub = $this->regularUser->grantSubscription($this->weeklyPlan);
        $originalExpiry = $sub->expires_at->copy();

        $response = $this->actingAs($this->adminUser)->post("/admin/subscriptions/{$sub->id}/extend", [
            'action_type' => 'add_30',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($sub->fresh()->expires_at->greaterThan($originalExpiry));
    }

    public function test_admin_can_manually_approve_transaction_and_activate_pro(): void
    {
        $tx = Transaction::create([
            'user_id' => $this->regularUser->id,
            'plan_id' => $this->weeklyPlan->id,
            'order_id' => 'ORDER-TEST-12345',
            'gross_amount' => $this->weeklyPlan->price,
            'payment_gateway' => 'manual',
            'payment_status' => 'pending',
        ]);

        $this->assertFalse($this->regularUser->fresh()->isPro());

        $response = $this->actingAs($this->adminUser)->post("/admin/transactions/{$tx->id}/approve");

        $response->assertSessionHas('success');
        $this->assertEquals('paid', $tx->fresh()->payment_status);
        $this->assertTrue($this->regularUser->fresh()->isPro());
    }

    public function test_admin_can_update_settings(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/settings', [
            'app_name' => 'Custom Downloader Brand',
            'free_daily_limit' => 25,
            'enable_batch_download' => '1',
            'allow_free_download' => '0',
            '__has_booleans' => '1',
            'plans' => [
                $this->weeklyPlan->id => [
                    'name' => 'Paket Mingguan 50 Foto',
                    'price' => 12000,
                    'duration_days' => 7,
                    'daily_download_limit' => 50,
                    'features' => "50 Foto per hari\nResolusi HD",
                    'is_active' => '1',
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('Custom Downloader Brand', Setting::get('app_name'));
        $this->assertEquals(25, Setting::get('free_daily_limit'));
        $this->assertFalse(Setting::get('allow_free_download'));

        $this->weeklyPlan->refresh();
        $this->assertEquals('Paket Mingguan 50 Foto', $this->weeklyPlan->name);
        $this->assertEquals(12000, $this->weeklyPlan->price);
        $this->assertEquals(50, $this->weeklyPlan->daily_download_limit);
        $this->assertEquals(7, $this->weeklyPlan->duration_days);
    }

    public function test_public_config_api_returns_correct_settings(): void
    {
        Setting::set('free_daily_limit', 15, 'general', 'integer');

        $response = $this->getJson('/api/v1/config');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'free_daily_limit' => 15,
        ]);
    }

    public function test_lowest_tier_weekly_plan_enforces_50_downloads_limit_per_day(): void
    {
        $this->assertEquals(50, $this->weeklyPlan->daily_download_limit);

        // Assign weekly plan to user
        $this->regularUser->grantSubscription($this->weeklyPlan);
        $token = $this->regularUser->createToken('test')->plainTextToken;

        // Check status returns daily_limit = 50 and remaining = 50
        $statusResponse = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/v1/status');
        $statusResponse->assertStatus(200);
        $statusResponse->assertJson([
            'is_pro' => true,
            'plan' => 'weekly',
            'daily_limit' => 50,
            'remaining_today' => 50,
        ]);

        // Simulate 50 downloads
        $trackResponse = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/download/track', [
            'count' => 50,
        ]);
        $trackResponse->assertStatus(200);
        $trackResponse->assertJson([
            'used_today' => 50,
            'remaining' => 0,
        ]);

        // 51st download should be blocked with 403
        $blockedResponse = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/download/track', [
            'count' => 1,
        ]);
        $blockedResponse->assertStatus(403);
        $blockedResponse->assertJson([
            'success' => false,
        ]);
    }

    public function test_user_can_view_web_register_form(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Daftar Akun Baru');
    }

    public function test_user_can_register_via_web(): void
    {
        $response = $this->post('/register', [
            'name' => 'Member Baru',
            'email' => 'memberbaru@gmail.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/member');
        $this->assertDatabaseHas('users', [
            'email' => 'memberbaru@gmail.com',
            'role' => 'user',
        ]);
    }

    public function test_member_can_access_member_portal(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get('/member');
        $response->assertStatus(200);
        $response->assertSee('Portal Akun &amp; Langganan Pengguna', false);
    }

    public function test_user_can_register_via_api(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Chrome Extension User',
            'email' => 'extuser@example.com',
            'password' => 'secret123',
            'device_id' => 'device_abc_123',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'user' => [
                'email' => 'extuser@example.com',
                'is_pro' => false,
            ],
        ]);
        $this->assertNotNull($response->json('token'));
        $this->assertDatabaseHas('users', [
            'email' => 'extuser@example.com',
            'device_id' => 'device_abc_123',
        ]);
    }

    public function test_guest_can_view_landing_page(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Tele Downloader PRO');
        $response->assertSee('Download Media Telegram Web Sekali Klik', false);
    }
}
