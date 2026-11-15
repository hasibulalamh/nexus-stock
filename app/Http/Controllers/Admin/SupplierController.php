<?php

namespace App\Http\Controllers\Admin;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->withCount('purchaseOrders')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('code', 'like', $term)->orWhere('phone', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('admin.suppliers.index', ['suppliers' => $suppliers]);
    }

    public function create()
    {
        return view('admin.suppliers.form', ['supplier' => new Supplier(['status' => 'active'])]);
    }

    public function store(Request $request)
    {
        $supplier = Supplier::create($this->validated($request));

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier '.$supplier->name.' created.');
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.form', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        $hasOpenOrders = $supplier->purchaseOrders()
            ->whereIn('status', ['draft', 'ordered', 'partial'])->exists();
        if ($hasOpenOrders) {
            return back()->withErrors(['supplier' => 'This supplier has open purchase orders and cannot be deleted.']);
        }
        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', Rule::unique('suppliers', 'code')->ignore($supplier?->id)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
