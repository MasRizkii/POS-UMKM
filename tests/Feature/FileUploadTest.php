<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    public static function validImages(): array
    {
        return ['jpg' => ['jpg'], 'jpeg' => ['jpeg'], 'png at 5 MB' => ['png'], 'webp' => ['webp']];
    }

    public static function invalidImages(): array
    {
        return ['disguised text' => ['mime'], 'unsupported gif' => ['gif'], 'over 5 MB' => ['size']];
    }

    private function productData(): array
    {
        return [
            'category_id' => Category::create(['name' => 'Minuman', 'is_active' => true])->id,
            'name' => 'Kopi', 'price' => 12000, 'status' => 'tersedia',
        ];
    }

    private function settingsData(): array
    {
        return [
            'store_name' => 'Kedai', 'invoice_prefix' => 'INV', 'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta', 'tax_enabled' => false, 'tax_percentage' => 0,
            'service_charge_enabled' => false, 'service_charge_percentage' => 0,
            'cash_enabled' => true, 'qris_enabled' => true,
        ];
    }

    private function invalidFile(string $kind): UploadedFile
    {
        return match ($kind) {
            'mime' => UploadedFile::fake()->createWithContent('photo.jpg', 'This is not an image.')->mimeType('text/plain'),
            'gif' => UploadedFile::fake()->image('photo.gif'),
            'size' => UploadedFile::fake()->image('photo.png')->size(5121),
        };
    }

    #[DataProvider('validImages')]
    public function test_product_image_is_stored_with_its_public_path(string $extension): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $image = UploadedFile::fake()->image("photo.{$extension}")->size(5120);

        $this->actingAs($admin)->post('/products', $this->productData() + ['image' => $image])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['name' => 'Kopi', 'image_url' => '/storage/'.$image->hashName('products')]);
        Storage::disk('public')->assertExists($image->hashName('products'));
    }

    #[DataProvider('validImages')]
    public function test_store_logo_is_stored_with_its_public_path(string $extension): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $logo = UploadedFile::fake()->image("logo.{$extension}")->size(5120);

        $this->actingAs($admin)->post('/settings', $this->settingsData() + ['_method' => 'put', 'store_logo' => $logo])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['id' => 1, 'store_logo' => '/storage/'.$logo->hashName('logos')]);
        Storage::disk('public')->assertExists($logo->hashName('logos'));
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_product_image_does_not_create_a_product_or_file(string $kind): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/products', $this->productData() + ['image' => $this->invalidFile($kind)])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('products', 0);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_logo_preserves_settings_and_storage(string $kind): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::current();
        $before = $setting->fresh()->getAttributes();

        $this->actingAs($admin)->post('/settings', $this->settingsData() + ['_method' => 'put', 'store_logo' => $this->invalidFile($kind)])
            ->assertSessionHasErrors('store_logo');

        $this->assertSame($before, $setting->fresh()->getAttributes());
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_product_edit_without_a_new_file_preserves_existing_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $data = $this->productData();
        $product = Product::create($data + ['image_url' => '/storage/'.$oldPath]);

        $this->actingAs($admin)->post("/products/{$product->id}", array_replace($data, ['_method' => 'put', 'image' => null, 'name' => 'Kopi Baru']))
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Kopi Baru', 'image_url' => '/storage/'.$oldPath]);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_settings_edit_without_a_new_file_preserves_existing_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('logos', 'public');
        Setting::current()->update(['store_logo' => '/storage/'.$oldPath]);

        $this->actingAs($admin)->post('/settings', $this->settingsData() + ['_method' => 'put', 'store_logo' => null])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['id' => 1, 'store_name' => 'Kedai', 'store_logo' => '/storage/'.$oldPath]);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_replacement_product_image_stores_a_new_file_and_path(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $data = $this->productData();
        $product = Product::create($data + ['image_url' => '/storage/'.$oldPath]);
        $image = UploadedFile::fake()->image('replacement.webp');

        $this->actingAs($admin)->post("/products/{$product->id}", $data + ['_method' => 'put', 'image' => $image])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'image_url' => '/storage/'.$image->hashName('products')]);
        $this->assertNotSame('/storage/'.$oldPath, $product->fresh()->image_url);
        Storage::disk('public')->assertExists($image->hashName('products'));
        $this->assertSame($image->getContent(), Storage::disk('public')->get($image->hashName('products')));
    }

    public function test_replacement_logo_stores_a_new_file_and_path(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('logos', 'public');
        $setting = Setting::current();
        $setting->update(['store_logo' => '/storage/'.$oldPath]);
        $logo = UploadedFile::fake()->image('replacement.png');

        $this->actingAs($admin)->post('/settings', $this->settingsData() + ['_method' => 'put', 'store_logo' => $logo])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['id' => 1, 'store_logo' => '/storage/'.$logo->hashName('logos')]);
        $this->assertNotSame('/storage/'.$oldPath, $setting->fresh()->store_logo);
        Storage::disk('public')->assertExists($logo->hashName('logos'));
        $this->assertSame($logo->getContent(), Storage::disk('public')->get($logo->hashName('logos')));
    }

    public function test_cashier_cannot_upload_product_image_or_store_logo(): void
    {
        Storage::fake('public');
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->post('/products', $this->productData() + ['image' => UploadedFile::fake()->image('photo.jpg')])->assertForbidden();
        $this->post('/settings', $this->settingsData() + ['_method' => 'put', 'store_logo' => UploadedFile::fake()->image('logo.jpg')])->assertForbidden();

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('settings', 0);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }
}
