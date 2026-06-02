<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchLogController extends Controller
{
    public function index(Request $request): Response
    {
        $rows = (int) $request->integer('rows', 30);

        $query = SearchLog::query()
            ->with('user:id,name')
            ->when($request->filled('query'), fn ($builder) => $builder->where('query', 'like', '%' . $request->string('query') . '%'))
            ->when($request->filled('type'), fn ($builder) => $builder->where('type', $request->string('type')))
            ->when($request->filled('matched_type'), fn ($builder) => $builder->where('matched_type', $request->string('matched_type')))
            ->when($request->boolean('no_result'), fn ($builder) => $builder->where('results_count', 0))
            ->when($request->filled('from'), fn ($builder) => $builder->whereDate('searched_at', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($builder) => $builder->whereDate('searched_at', '<=', $request->string('to')))
            ->orderByDesc('searched_at');

        $top = SearchLog::query()
            ->selectRaw('query, COUNT(*) as hits')
            ->groupBy('query')
            ->orderByDesc('hits')
            ->first();

        return Inertia::render('Search/Logs', [
            'logs' => $query->paginate($rows)->withQueryString(),
            'stats' => [
                'total' => SearchLog::query()->count(),
                'top_query' => $top?->query,
                'top_query_hits' => (int) ($top?->hits ?? 0),
                'no_result' => SearchLog::query()->where('results_count', 0)->count(),
                'top_brand' => SearchLog::query()->where('matched_type', 'brand')->selectRaw('query, COUNT(*) as hits')->groupBy('query')->orderByDesc('hits')->value('query'),
                'top_category' => SearchLog::query()->where('matched_type', 'category')->selectRaw('query, COUNT(*) as hits')->groupBy('query')->orderByDesc('hits')->value('query'),
            ],
            'filters' => $request->only(['query', 'type', 'matched_type', 'from', 'to', 'no_result', 'rows']),
            'types' => [
                ['label' => 'محصول', 'value' => 'product'],
                ['label' => 'دسته‌بندی', 'value' => 'category'],
                ['label' => 'برند', 'value' => 'brand'],
                ['label' => 'متن آزاد', 'value' => 'free_text'],
            ],
            'matchedTypes' => [
                ['label' => 'محصول', 'value' => 'product'],
                ['label' => 'دسته‌بندی', 'value' => 'category'],
                ['label' => 'برند', 'value' => 'brand'],
            ],
        ]);
    }

    public function show(SearchLog $searchLog): Response
    {
        return Inertia::render('Search/LogShow', ['log' => $searchLog->load('user:id,name')]);
    }

    public function destroy(SearchLog $searchLog): RedirectResponse
    {
        $searchLog->delete();

        return back()->with('success', 'لاگ حذف شد.');
    }
}
