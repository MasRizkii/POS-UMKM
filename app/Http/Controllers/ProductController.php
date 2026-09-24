<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    public function index(Request $request): Response
    {
        $query = Product::with('category:id,name')->latest('id');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Pagination mulai 15 item per halaman (PRD PROD-5)
        $products = $query->paginate(15)->withQueryString();
        $categories = Category::all(['id', 'name', 'icon', 'is_active']);

        $metrics = [
            'total_products' => Product::count(),
            'available_products' => Product::where('status', 'tersedia')->count(),
            'unavailable_products' => Product::where('status', 'tidak_tersedia')->count(),
            'total_categories' => Category::count(),
        ];

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'metrics' => $metrics,
            'filters' => [
                'search' => $request->input('search', ''),
                'category_id' => $request->input('category_id', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:tersedia,tidak_tersedia'],
        ]);

        $validated['image_url'] = $request->hasFile('image') ? '/storage/'.$request->file('image')->store('products', 'public') : null;
        unset($validated['image']);
        $product = Product::create($validated);

        $this->auditLogger->log(
            action: 'CREATE_PRODUCT',
            entity: 'Product',
            entityId: $product->id,
            newValues: $validated
        );

        return back()->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:tersedia,tidak_tersedia'],
        ]);

        $oldValues = $product->only(['name', 'price', 'status', 'category_id']);
        if ($request->hasFile('image')) {
            $validated['image_url'] = '/storage/'.$request->file('image')->store('products', 'public');
        }
        unset($validated['image']);
        $product->update($validated);

        // Audit Log khusus perubahan harga (PRD AUDIT-1)
        if ($oldValues['price'] != $validated['price']) {
            $this->auditLogger->log(
                action: 'UPDATE_PRODUCT_PRICE',
                entity: 'Product',
                entityId: $product->id,
                oldValues: ['price' => $oldValues['price']],
                newValues: ['price' => $validated['price']]
            );
        }

        return back()->with('success', 'Produk berhasil diperbarui.');
    }

    public function toggleStatus($id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $newStatus = $product->status === 'tersedia' ? 'tidak_tersedia' : 'tersedia';
        $product->status = $newStatus;
        $product->save();

        $this->auditLogger->log(
            action: 'TOGGLE_PRODUCT_STATUS',
            entity: 'Product',
            entityId: $product->id,
            newValues: ['status' => $newStatus]
        );

        return back()->with('success', "Status produk diubah menjadi {$newStatus}.");
    }

    public function destroy($id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        // Soft delete sesuai PRD PROD-6 & PROD-8
        $product->delete();

        $this->auditLogger->log(
            action: 'SOFT_DELETE_PRODUCT',
            entity: 'Product',
            entityId: $product->id
        );

        return back()->with('success', 'Produk dinonaktifkan (Soft Delete) tanpa merusak histori transaksi.');
    }
}
