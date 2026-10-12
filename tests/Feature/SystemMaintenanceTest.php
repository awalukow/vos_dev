<?php

namespace Tests\Feature;

use App\Models\PortalUser;
use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemMaintenanceTest extends TestCase
{
    private function signIn(string $role): void
    {
        $user = new PortalUser(['name' => 'Maintenance user', 'is_active' => true]);
        $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => $role])]));
        $this->actingAs($user, 'portal');
    }

    public function test_guests_cannot_access_or_run_maintenance(): void
    {
        Artisan::shouldReceive('call')->never();
        $this->get('/system/maintenance')->assertRedirect(route('portal.login'));
        $this->post('/system/maintenance')->assertRedirect(route('portal.login'));
        $this->post('/system/migrate', ['force' => '1'])->assertRedirect(route('portal.login'));
    }

    public function test_non_administrators_cannot_access_or_run_maintenance(): void
    {
        $this->signIn('pengurus');
        Artisan::shouldReceive('call')->never();
        $this->get('/system/maintenance')->assertForbidden();
        $this->post('/system/maintenance')->assertForbidden();
        $this->post('/system/migrate', ['force' => '1'])->assertForbidden();
    }

    public function test_opening_the_page_does_not_run_commands(): void
    {
        $this->signIn('administrator');
        Artisan::shouldReceive('call')->never();
        $this->get('/system/maintenance')->assertOk()
            ->assertSee('Run optimize:clear and dump-autoload')
            ->assertSee('Run migrations')
            ->assertSee('Use --force')
            ->assertSee('name="_token"', false);
    }

    public function test_failed_cache_clear_stops_before_composer_and_escapes_output(): void
    {
        $this->signIn('administrator');
        Artisan::shouldReceive('call')->once()->with('optimize:clear')->andReturn(1);
        Artisan::shouldReceive('output')->once()->andReturn('<script>failed</script>');

        $this->post('/system/maintenance')->assertStatus(500)
            ->assertSee('Maintenance failed')
            ->assertSee('&lt;script&gt;failed&lt;/script&gt;', false)
            ->assertDontSee('$ composer dump-autoload');
    }

    /** @dataProvider migrationForceOptions */
    public function test_migrations_respect_force_option_and_clear_caches(array $input, bool $force): void
    {
        $this->signIn('administrator');
        Artisan::shouldReceive('call')->once()->with('migrate', [
            '--force' => $force, '--no-interaction' => true,
        ])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('optimize:clear')->andReturn(0);
        Artisan::shouldReceive('output')->twice()->andReturn('Migrations completed.', 'Caches cleared.');

        $this->post('/system/migrate', $input)->assertOk()
            ->assertSee('Maintenance completed')
            ->assertSee('$ php artisan migrate' . ($force ? ' --force' : ''))
            ->assertSee('Migrations completed.')
            ->assertSee('Caches cleared.');
    }

    public static function migrationForceOptions(): array
    {
        return [
            'without force' => [[], false],
            'with force' => [['force' => '1'], true],
        ];
    }

    public function test_failed_migration_does_not_clear_caches(): void
    {
        $this->signIn('administrator');
        Artisan::shouldReceive('call')->once()->with('migrate', [
            '--force' => false, '--no-interaction' => true,
        ])->andReturn(1);
        Artisan::shouldReceive('call')->with('optimize:clear')->never();
        Artisan::shouldReceive('output')->once()->andReturn('Migration cancelled.');

        $this->post('/system/migrate')->assertStatus(500)
            ->assertSee('Maintenance failed')
            ->assertSee('Migration cancelled.');
    }
}
