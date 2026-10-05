<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', [
            'categories' => Category::withCount('assets')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Category::create($request->validate([
            'name'        => 'required|string|max:100|unique:categories,name',
            'code_prefix' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:255',
        ]));

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate([
            'name'        => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'code_prefix' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:255',
        ]));

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        if ($category->assets()->withTrashed()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih dipakai oleh aset.');
        }

        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}