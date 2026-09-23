<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'icon' => ['nullable', 'string', 'max:20'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? '🥤',
            'is_active' => true,
        ]);

        $this->auditLogger->log(
            action: 'CREATE_CATEGORY',
            entity: 'Category',
            entityId: $category->id,
            newValues: $validated
        );

        return back()->with('success', 'Kategori baru berhasil dibuat.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,' . $category->id],
            'icon' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $category->update($validated);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        // Validasi PRD PROD-7 & 7.7: Kategori yang masih digunakan produk aktif tidak boleh dihapus
        $activeProductsCount = Product::where('category_id', $category->id)
            ->where('status', 'tersedia')
            ->count();

        if ($activeProductsCount > 0) {
            return back()->with('error', "Kategori tidak dapat dihapus karena masih digunakan oleh {$activeProductsCount} produk aktif. Pindahkan produk terlebih dahulu.");
        }

        $category->delete(); // Soft delete sesuai PRD 7.7

        $this->auditLogger->log(
            action: 'SOFT_DELETE_CATEGORY',
            entity: 'Category',
            entityId: $category->id
        );

        return back()->with('success', 'Kategori dinonaktifkan (Soft Delete).');
    }
}
