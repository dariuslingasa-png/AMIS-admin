<?php

namespace App\Repositories;

use Illuminate\Support\Facades\File;

class TeacherRepository
{
    public function overrides(): array
    {
        $path = $this->path();
        if (! File::exists($path)) {
            return [];
        }

        $overrides = json_decode((string) File::get($path), true);

        return is_array($overrides) ? $overrides : [];
    }

    public function saveOverrides(array $overrides): void
    {
        $path = $this->path();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function saveTeacher(string $id, array $teacher): void
    {
        $overrides = $this->overrides();
        $overrides[$id] = $teacher;
        $this->saveOverrides($overrides);
    }

    public function findOverride(string $id): ?array
    {
        return $this->overrides()[$id] ?? null;
    }

    private function path(): string
    {
        return storage_path('app/academic_teacher_overrides.json');
    }
}
