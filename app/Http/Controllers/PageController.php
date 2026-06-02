<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageRequest;
use App\Http\Services\PageService;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private readonly PageService $pages) {}

    public function index(Request $request): Response
    {
        return Inertia::render('CMS/Pages/Index', [
            'pages' => $this->pages->paginated($request),
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'rows']),
            'statusOptions' => $this->pages->formData()['statusOptions'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CMS/Pages/Create', $this->pages->formData());
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $this->pages->create($request);

        return redirect()->route('admin.pages.index')->with('success', 'صفحه ایجاد شد.');
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('CMS/Pages/Edit', [
            ...$this->pages->formData(),
            'page' => $this->pages->format($page, true),
        ]);
    }

    public function show(Page $page): RedirectResponse
    {
        return redirect()->route('admin.pages.edit', $page);
    }

    public function update(StorePageRequest $request, Page $page): RedirectResponse
    {
        $this->pages->update($request, $page);

        return redirect()->route('admin.pages.index')->with('success', 'صفحه ویرایش شد.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return back()->with('success', 'صفحه حذف شد.');
    }
}
