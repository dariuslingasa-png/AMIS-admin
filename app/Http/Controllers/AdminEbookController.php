<?php

namespace App\Http\Controllers;

use App\Models\Ebook;
use App\Models\EbookAccessLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminEbookController extends Controller
{
    private const STORAGE_DISK = 'ebook_private';

    private const GRADE_LEVELS = [
        'Kinder 1',
        'Kinder 2',
        'Grade 1',
        'Grade 2',
        'Grade 3',
        'Grade 4',
        'Grade 5',
        'Grade 6',
        'Grade 7',
        'Grade 8',
        'Grade 9',
        'Grade 10',
        'K11',
        'K12',
    ];

    public function index(Request $request): View
    {
        $books = Ebook::query()
            ->with('creator')
            ->when($request->filled('grade'), fn ($query) => $query->where('grade_level', (string) $request->string('grade')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';

                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => Ebook::count(),
            'published' => Ebook::where('status', 'published')->count(),
            'drafts' => Ebook::where('status', 'draft')->count(),
            'downloads_enabled' => Ebook::where('is_downloadable', true)->count(),
            'views' => Schema::hasTable('ebook_access_logs') ? EbookAccessLog::where('action', 'view')->count() : 0,
            'streams' => Schema::hasTable('ebook_access_logs') ? EbookAccessLog::where('action', 'stream')->count() : 0,
        ];

        $recentLogs = Schema::hasTable('ebook_access_logs')
            ? EbookAccessLog::with(['ebook', 'user'])->latest('created_at')->limit(8)->get()
            : collect();

        return view('admin.ebook.index', [
            'books' => $books,
            'gradeLevels' => self::GRADE_LEVELS,
            'stats' => $stats,
            'recentLogs' => $recentLogs,
            'publicCatalogUrl' => rtrim((string) config('services.ebook.url'), '/').'/books',
        ]);
    }

    public function create(): View
    {
        return view('admin.ebook.create', [
            'book' => null,
            'gradeLevels' => self::GRADE_LEVELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request, requirePdf: true);
        $data['file_path'] = $this->storePdf($request);
        $data['created_by'] = Auth::id();

        Ebook::create($data);

        return redirect()->route('admin.ebook.index')->with('success', 'eBook uploaded successfully.');
    }

    public function edit(Ebook $ebook): View
    {
        return view('admin.ebook.edit', [
            'book' => $ebook,
            'gradeLevels' => self::GRADE_LEVELS,
        ]);
    }

    public function update(Request $request, Ebook $ebook): RedirectResponse
    {
        $data = $this->validatedData($request, requirePdf: false);

        if ($request->hasFile('pdf_file')) {
            $this->deletePdf($ebook);
            $data['file_path'] = $this->storePdf($request);
        }

        $ebook->update($data);

        return redirect()->route('admin.ebook.index')->with('success', 'eBook updated successfully.');
    }

    public function destroy(Ebook $ebook): RedirectResponse
    {
        $this->deletePdf($ebook);
        $ebook->delete();

        return redirect()->route('admin.ebook.index')->with('success', 'eBook deleted.');
    }

    private function validatedData(Request $request, bool $requirePdf): array
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'grade_level' => ['required', 'string', Rule::in(self::GRADE_LEVELS)],
            'pdf_file' => [$requirePdf ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:51200'],
            'status' => ['required', 'string', Rule::in(['published', 'draft'])],
            'is_downloadable' => ['nullable', 'boolean'],
        ]);

        return [
            'title' => (string) $request->string('title')->trim(),
            'description' => $request->filled('description') ? (string) $request->string('description')->trim() : null,
            'grade_level' => (string) $request->string('grade_level')->trim(),
            'status' => (string) $request->string('status'),
            'is_downloadable' => $request->boolean('is_downloadable'),
        ];
    }

    private function storePdf(Request $request): string
    {
        $file = $request->file('pdf_file');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        return $file->storeAs('private/ebooks', $filename, self::STORAGE_DISK);
    }

    private function deletePdf(Ebook $ebook): void
    {
        if ($ebook->file_path) {
            Storage::disk(self::STORAGE_DISK)->delete($ebook->file_path);
        }
    }
}
