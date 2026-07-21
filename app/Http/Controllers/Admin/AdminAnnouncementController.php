<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AdminAnnouncementController extends Controller
{
    private function ensureAdmin()
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('super_admin') && !$user->hasRole('admin'))) {
            abort(403, 'Unauthorized. Super Admin or Admin role required.');
        }
    }

    private function getWebsitePublicPath(): string
    {
        if (app()->environment('production')) {
            return '/home2/amisdavc/amis.edu.ph/public';
        }
        return base_path('../amis_website/public');
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $search = $request->query('search');

        $query = Announcement::query();

        if (filled($search)) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        $announcements = $query->latest('publish_date')->paginate(10)->withQueryString();

        return view('admin.website.announcements.index', compact('announcements', 'search'));
    }

    public function create()
    {
        $this->ensureAdmin();
        return view('admin.website.announcements.create');
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:50',
            'priority' => 'required|string|in:normal,high',
            'author' => 'required|string|max:255',
            'publish_date' => 'required|date',
            'image_files' => 'nullable|array',
            'image_files.*' => 'image|mimes:jpg,jpeg,png,webp|max:10240',
            'event_dates' => 'nullable|string|max:255',
            'event_venue' => 'nullable|string|max:255',
        ]);

        $validated['uuid'] = (string) \Illuminate\Support\Str::uuid();
        $validated['is_online'] = $request->has('is_online');

        $imagePaths = [];
        if ($request->hasFile('image_files')) {
            foreach ($request->file('image_files') as $file) {
                $filename = 'announcement_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
                
                $websitePublic = $this->getWebsitePublicPath();
                $targetDir = $websitePublic . '/images/announcements';
                
                if (!File::isDirectory($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true, true);
                }
                
                $file->move($targetDir, $filename);
                $imagePaths[] = '/images/announcements/' . $filename;
            }
            $validated['image'] = json_encode($imagePaths);
        } else {
            $validated['image'] = json_encode(['/coming-soon.png']);
        }

        Announcement::create($validated);

        return redirect()->route('admin.website.announcements.index')->with('success', 'Announcement published successfully.');
    }

    public function edit($id)
    {
        $this->ensureAdmin();
        $announcement = Announcement::findOrFail($id);
        return view('admin.website.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();
        $announcement = Announcement::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:50',
            'priority' => 'required|string|in:normal,high',
            'author' => 'required|string|max:255',
            'publish_date' => 'required|date',
            'image_files' => 'nullable|array',
            'image_files.*' => 'image|mimes:jpg,jpeg,png,webp|max:10240',
            'event_dates' => 'nullable|string|max:255',
            'event_venue' => 'nullable|string|max:255',
        ]);

        $validated['is_online'] = $request->has('is_online');

        $remainingImages = json_decode($request->input('remaining_images', '[]'), true);
        if (!is_array($remainingImages)) {
            $remainingImages = [];
        }

        // Delete old image files that were removed by the user
        $oldImages = json_decode($announcement->image, true);
        if (!is_array($oldImages)) {
            $oldImages = $announcement->image ? [$announcement->image] : [];
        }
        foreach ($oldImages as $oldImage) {
            if ($oldImage && !in_array($oldImage, $remainingImages)) {
                if (str_starts_with($oldImage, '/images/announcements/')) {
                    $oldFile = $this->getWebsitePublicPath() . $oldImage;
                    if (File::exists($oldFile)) {
                        @unlink($oldFile);
                    }
                }
            }
        }

        // Upload new image files if any
        if ($request->hasFile('image_files')) {
            foreach ($request->file('image_files') as $file) {
                $filename = 'announcement_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
                $websitePublic = $this->getWebsitePublicPath();
                $targetDir = $websitePublic . '/images/announcements';
                if (!File::isDirectory($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true, true);
                }
                $file->move($targetDir, $filename);
                $remainingImages[] = '/images/announcements/' . $filename;
            }
        }

        $validated['image'] = json_encode($remainingImages);

        $announcement->update($validated);

        return redirect()->route('admin.website.announcements.index')->with('success', 'Announcement updated successfully.');
    }

    public function destroy($id)
    {
        $this->ensureAdmin();
        $announcement = Announcement::findOrFail($id);

        // Delete associated image files
        $images = json_decode($announcement->image, true);
        if (!is_array($images)) {
            $images = $announcement->image ? [$announcement->image] : [];
        }
        foreach ($images as $img) {
            if ($img && str_starts_with($img, '/images/announcements/')) {
                $oldFile = $this->getWebsitePublicPath() . $img;
                if (File::exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
        }

        $announcement->delete();

        return redirect()->route('admin.website.announcements.index')->with('success', 'Announcement deleted successfully.');
    }
}
