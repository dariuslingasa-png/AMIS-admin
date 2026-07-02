<?php

namespace App\Services\Admin\Academic;

use Illuminate\Support\Str;

/**
 * Matches a raw teacher name string (e.g. "Ust. Saliha", "Tchr. Ayah")
 * against the existing teacher database, following the 9 matching rules.
 *
 * Rules:
 *  1. Use existing Teacher Database — never create new teachers.
 *  2. No duplicates during import.
 *  3. Strip titles before matching.
 *  4. Match by actual name only.
 *  5. Case-insensitive, ignore periods, ignore extra spaces, ignore titles.
 *  6. Exactly 1 match → status = matched.
 *  7. Multiple matches → status = manual (flag for manual selection).
 *  8. No match → status = unmatched, do NOT auto-create.
 *  9. Save teacher_key (slug ID), not teacher_name.
 */
class TeacherMatcherService
{
    private const TITLE_PATTERNS = [
        '/^tchr\.?\s+/i',
        '/^teacher\s+/i',
        '/^ust\.?\s+/i',
        '/^ustadh\s+/i',
        '/^ustdz\.?\s+/i',
        '/^ustadza\s+/i',
        '/^ustadha\s+/i',
        '/^sir\.?\s+/i',
        '/^ma\'?am\.?\s+/i',
        '/^maam\.?\s+/i',
        '/^ms\.?\s+/i',
        '/^mrs\.?\s+/i',
        '/^mr\.?\s+/i',
    ];

    private ?array $teacherList = null;

    /**
     * Match a raw teacher name string against the teacher database.
     *
     * @param  string $rawName  e.g. "Ust. Saliha", "Tchr. Ayah", "Ayah"
     * @return array{key: string|null, display: string, status: string}
     */
    public function match(string $rawName): array
    {
        $rawName = trim($rawName);

        if (blank($rawName)) {
            return ['key' => null, 'display' => null, 'status' => 'unmatched'];
        }

        $bare = $this->stripTitle($rawName);
        $normalized = $this->normalize($bare);

        $teachers = $this->allTeachers();

        $matches = [];
        foreach ($teachers as $teacher) {
            $teacherNormalized = $this->normalize($teacher['name']);
            if ($teacherNormalized === $normalized) {
                $matches[] = $teacher;
            }
        }

        // Rule 6: Exactly 1 match
        if (count($matches) === 1) {
            return [
                'key'     => $matches[0]['id'],
                'display' => $rawName,
                'status'  => 'matched',
            ];
        }

        // Rule 7: Multiple matches — flag for manual selection
        if (count($matches) > 1) {
            return [
                'key'     => null,
                'display' => $rawName,
                'status'  => 'manual',
            ];
        }

        // Rule 8: No match — do NOT auto-create
        return [
            'key'     => null,
            'display' => $rawName,
            'status'  => 'unmatched',
        ];
    }

    /**
     * Resolve a teacher_key to display name from the teacher database.
     */
    public function resolveDisplay(string $key): ?string
    {
        $teacher = collect($this->allTeachers())->firstWhere('id', $key);
        return $teacher['name'] ?? null;
    }

    /**
     * Return all teachers (base + overrides) as flat array.
     */
    public function allTeachers(): array
    {
        if ($this->teacherList !== null) {
            return $this->teacherList;
        }

        /** @var \App\Repositories\TeacherRepository $repo */
        $repo = app(\App\Repositories\TeacherRepository::class);
        $overrides = $repo->overrides();

        // Base teachers from advisories
        $advisory = collect(config('class_advisories', []))
            ->flatMap(fn ($rows) => collect($rows))
            ->map(fn ($row) => [
                'id'   => Str::slug(Str::of($row['teacher'] ?? '')->ascii()),
                'name' => $row['teacher'] ?? '',
            ]);

        // Teachers from overrides JSON
        $fromOverrides = collect($overrides)->map(fn ($data, $id) => [
            'id'   => $id,
            'name' => $data['name'] ?? '',
        ])->values();

        $this->teacherList = $advisory
            ->merge($fromOverrides)
            ->filter(fn ($t) => filled($t['name']))
            ->unique('id')
            ->values()
            ->all();

        return $this->teacherList;
    }

    /**
     * Strip teacher title prefix from name.
     * e.g. "Ust. Saliha" → "Saliha", "Tchr. Ayah" → "Ayah"
     */
    public function stripTitle(string $name): string
    {
        foreach (self::TITLE_PATTERNS as $pattern) {
            $stripped = preg_replace($pattern, '', $name);
            if ($stripped !== $name) {
                return trim($stripped);
            }
        }
        return trim($name);
    }

    /**
     * Normalize for comparison: lowercase, remove periods, collapse spaces.
     */
    private function normalize(string $name): string
    {
        $name = mb_strtolower($name);
        $name = str_replace('.', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }
}
