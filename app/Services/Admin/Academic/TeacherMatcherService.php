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
        '/^ustadz\.?\s+/i',
        '/^alim\.?\s+/i',
        '/^alima\.?\s+/i',
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
        // Exact Match (with title stripped from both query and database name)
        foreach ($teachers as $teacher) {
            $teacherBare = $this->stripTitle($teacher['name']);
            $teacherNormalized = $this->normalize($teacherBare);
            if ($teacherNormalized === $normalized) {
                $matches[] = $teacher;
            }
        }

        // Fallback: If no exact match, try matching by first name, slug, or substring
        if (empty($matches)) {
            // Pass 1: Match by exact first name, compact name, or word match in first name
            foreach ($teachers as $teacher) {
                $firstName = $teacher['first_name'] ?? '';
                if (empty($firstName)) {
                    $nameWithoutTitle = $this->stripTitle($teacher['name']);
                    $parts = explode(' ', $nameWithoutTitle);
                    $firstName = $parts[0] ?? '';
                }
                
                $normalizedFirstName = $this->normalize($firstName);
                $normalizedId = str_replace('-', ' ', $this->normalize($teacher['id']));
                
                $compactNormalized = str_replace(' ', '', $normalized);
                $compactFirstName = str_replace(' ', '', $normalizedFirstName);
                $compactId = str_replace(' ', '', $normalizedId);

                // Handle special spelling variations (e.g., Normayla matching Normylah)
                $isNormaMatch = (str_starts_with($compactNormalized, 'norma') || str_starts_with($compactNormalized, 'normy')) && 
                               (str_starts_with($compactId, 'teachernormylah') || str_starts_with($compactId, 'normylah'));

                $firstNameWords = explode(' ', $normalizedFirstName);
                $idWords = explode(' ', $normalizedId);

                if (
                    $normalizedFirstName === $normalized ||
                    $compactFirstName === $compactNormalized ||
                    $normalizedId === $normalized ||
                    $compactId === $compactNormalized ||
                    in_array($normalized, $firstNameWords) ||
                    in_array($normalized, $idWords) ||
                    $isNormaMatch
                ) {
                    $matches[] = $teacher;
                }
            }

            // Pass 2: Looser substring match (only if no matches found in Pass 1)
            if (empty($matches)) {
                foreach ($teachers as $teacher) {
                    $normalizedName = $this->normalize($teacher['name']);
                    $compactName = str_replace(' ', '', $normalizedName);

                    if (
                        str_contains($normalizedName, $normalized) ||
                        str_contains($compactName, $compactNormalized)
                    ) {
                        $matches[] = $teacher;
                    }
                }
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
                'first_name' => explode(' ', str_replace(['TEACHER ', 'USTADZ ', 'USTADHA ', 'ALIM '], '', $row['teacher'] ?? ''))[0] ?? '',
            ]);

        // Teachers from overrides JSON
        $fromOverrides = collect($overrides)->map(fn ($data, $id) => [
            'id'   => $id,
            'name' => $data['name'] ?? '',
            'first_name' => $data['first_name'] ?? '',
        ])->values();

        // Teachers from database
        $dbTeachers = \App\Models\User::where('role', 'teacher')
            ->get()
            ->map(fn ($u) => [
                'id'   => $u->username ?: Str::slug(Str::of($u->name)->ascii()),
                'name' => $u->name,
                'first_name' => explode(' ', str_replace(['TEACHER ', 'USTADZ ', 'USTADHA ', 'ALIM '], '', $u->name))[0] ?? '',
            ]);

        $getPhotoUrl = function ($id, $name) use ($overrides) {
            $photoPath = $overrides[$id]['photo'] ?? null;
            if (empty($photoPath)) {
                $cleanName = trim($name);
                while (preg_match('/^(TEACHER|TCHR\.?|UST\.?|USTADZ|USTADH|USTADHA|ALIM|SIR|MA\'AM|MAAM)\s+/i', $cleanName, $matches)) {
                    $cleanName = trim(substr($cleanName, strlen($matches[0])));
                }
                $teacherKey = Str::slug($cleanName);
                $possiblePaths = [
                    "images/teachers/{$teacherKey}.jpg",
                    "images/teachers/teacher-{$teacherKey}.jpg",
                    "images/teachers/{$teacherKey}.png",
                    "images/teachers/teacher-{$teacherKey}.png",
                    "images/teachers/{$teacherKey}.jpeg",
                    "images/teachers/teacher-{$teacherKey}.jpeg",
                ];
                foreach ($possiblePaths as $path) {
                    if (file_exists(public_path($path))) {
                        $photoPath = $path;
                        break;
                    }
                }
            }
            if ($photoPath) {
                return str_contains($photoPath, 'images/teachers/') ? asset($photoPath) : asset(\App\Support\ImageHelper::thumb($photoPath, 'medium'));
            }
            return null;
        };

        $this->teacherList = $advisory
            ->merge($fromOverrides)
            ->merge($dbTeachers)
            ->filter(fn ($t) => filled($t['name']))
            ->sortByDesc(fn ($t) => str_contains($t['id'], '-') ? 0 : 1)
            ->unique(fn ($t) => $this->getShortName($t['name']))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function ($t) use ($getPhotoUrl) {
                $t['photo_url'] = $getPhotoUrl($t['id'], $t['name']);
                $t['short_name'] = $this->getShortName($t['name']);
                return $t;
            })
            ->all();

        return $this->teacherList;
    }

    /**
     * Helper to format full name to standard short display name format (matched with blade helper).
     */
    private function getShortName(string $fullName): string
    {
        $fullName = trim($fullName);
        if (empty($fullName)) {
            return '';
        }
        $bareName = $fullName;
        $matchedPrefix = 'Teacher';

        foreach (self::TITLE_PATTERNS as $pattern) {
            if (preg_match($pattern, $fullName)) {
                if (preg_match('/^ust/i', $fullName)) $matchedPrefix = 'Ustadz';
                elseif (preg_match('/^ustadh/i', $fullName)) $matchedPrefix = 'Ustadz';
                elseif (preg_match('/^ustadza/i', $fullName)) $matchedPrefix = 'Ustadha';
                elseif (preg_match('/^ustadha/i', $fullName)) $matchedPrefix = 'Ustadha';
                elseif (preg_match('/^alim/i', $fullName)) $matchedPrefix = 'Alim';
                elseif (preg_match('/^alima/i', $fullName)) $matchedPrefix = 'Alima';
                elseif (preg_match('/^sir/i', $fullName)) $matchedPrefix = 'Sir';
                elseif (preg_match('/^ma/i', $fullName)) $matchedPrefix = 'Maam';
                elseif (preg_match('/^ms/i', $fullName)) $matchedPrefix = 'Ms';
                elseif (preg_match('/^mrs/i', $fullName)) $matchedPrefix = 'Mrs';
                elseif (preg_match('/^mr/i', $fullName)) $matchedPrefix = 'Mr';

                $bareName = preg_replace($pattern, '', $fullName);
                break;
            }
        }

        $words = explode(' ', trim($bareName));
        $firstName = ucfirst(strtolower($words[0] ?? ''));

        return trim($matchedPrefix . ' ' . $firstName);
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
