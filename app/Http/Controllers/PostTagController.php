<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostTagRequest;
use App\Http\Services\CatalogService;
use App\Models\PostTag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostTagController extends Controller
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    public function index(): Response
    {
        return Inertia::render('CMS/Tags/Index', [
            'tags' => $this->catalog->postTags(),
        ]);
    }

    public function store(StorePostTagRequest $request): RedirectResponse
    {
        PostTag::create($request->validated());

        return back()->with('success', 'برچسب ایجاد شد.');
    }

    public function update(StorePostTagRequest $request, PostTag $postTag): RedirectResponse
    {
        $postTag->update($request->validated());

        return back()->with('success', 'برچسب ویرایش شد.');
    }

    public function destroy(PostTag $postTag): RedirectResponse
    {
        $postTag->delete();

        return back()->with('success', 'برچسب حذف شد.');
    }
}
