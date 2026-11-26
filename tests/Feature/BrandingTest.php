<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_a_logo_the_store_name_is_shown_as_text(): void
    {
        $this->get('/')->assertOk()->assertSee('class="syro-logo"', false)->assertDontSee('syro-logo--img', false);
    }

    public function test_admin_can_upload_replace_resize_and_delete_the_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk()->assertSee('Logo &amp; branding', false);

        // Upload
        $this->actingAs($admin)->post(route('admin.settings.branding'), [
            'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
            'favicon' => UploadedFile::fake()->image('icon.png', 64, 64),
            'logo_height' => 56,
        ])->assertSessionHas('success');

        $first = setting('logo');
        $this->assertStringStartsWith('branding/', $first);
        Storage::disk('public')->assertExists([$first, setting('favicon')]);
        $this->assertSame('56', setting('logo_height'));

        // Shown on the storefront: header (dark) + footer (main logo inverted), size and favicon applied
        $this->get('/')->assertOk()
            ->assertSee('syro-logo--img', false)
            ->assertSee('/storage/'.$first, false)
            ->assertSee('height: 56px', false)
            ->assertSee('logo-invert', false)
            ->assertSee('/storage/'.setting('favicon'), false);

        // Replace: new file stored, old file removed
        $this->actingAs($admin)->post(route('admin.settings.branding'), [
            'logo' => UploadedFile::fake()->image('new-logo.png', 400, 120),
            'logo_height' => 40,
        ])->assertSessionHas('success');
        $this->assertNotSame($first, setting('logo'));
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists(setting('logo'));

        // Delete: file removed, site falls back to the store name
        $second = setting('logo');
        $this->actingAs($admin)->delete(route('admin.settings.branding.destroy', 'logo'))->assertSessionHas('success');
        $this->assertSame('', setting('logo'));
        Storage::disk('public')->assertMissing($second);
        $this->get('/')->assertOk()->assertDontSee('syro-logo--img', false);
    }

    public function test_logo_can_be_managed_from_the_banners_page(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.banners.index'))->assertOk()
            ->assertSee('Logo &amp; branding', false)
            ->assertSee('Homepage banners');

        $this->actingAs($admin)->from(route('admin.banners.index'))->post(route('admin.settings.branding'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
            'logo_height' => 40,
        ])->assertRedirect(route('admin.banners.index'));

        $this->actingAs($admin)->get(route('admin.banners.index'))->assertSee(media_url(setting('logo')), false)
            ->assertSee('form="delete-logo"', false);

        // Visitors see it
        $this->get('/')->assertSee(media_url(setting('logo')), false);
    }

    public function test_light_logo_is_used_on_dark_backgrounds(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.settings.branding'), [
            'logo' => UploadedFile::fake()->image('dark.png'),
            'logo_light' => UploadedFile::fake()->image('light.png'),
            'logo_height' => 40,
        ])->assertSessionHas('success');

        $this->get('/')->assertOk()
            ->assertSee('/storage/'.setting('logo_light'), false)
            ->assertDontSee('logo-invert', false);
    }

    public function test_invalid_files_and_non_admins_are_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.settings.branding'), [
            'logo' => UploadedFile::fake()->create('virus.exe', 10),
            'logo_height' => 40,
        ])->assertSessionHasErrors('logo');
        $this->assertSame('', setting('logo'));

        $this->actingAs(User::factory()->create())->post(route('admin.settings.branding'), [
            'logo' => UploadedFile::fake()->image('logo.png'), 'logo_height' => 40,
        ])->assertForbidden();

        $this->actingAs($admin)->delete('/admin/settings/branding/store_name')->assertNotFound();
    }
}
