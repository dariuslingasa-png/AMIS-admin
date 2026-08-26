<?php

namespace App\Repositories;

use App\Models\Section;
use App\Models\SectionSubject;
use Illuminate\Support\Collection;

class ClassScheduleRepository
{
    public function sections(): Collection
    {
        return Section::withCount('students')->with('subjects')->orderBy('id')->get();
    }

    public function timetableEntries(Collection $sections): Collection
    {
        return SectionSubject::whereIn('section_id', $sections->pluck('id'))->get();
    }

    public function scheduledEntries(?int $ignoreId = null): Collection
    {
        return SectionSubject::whereNotNull('schedule')
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get();
    }

    public function create(array $data): SectionSubject
    {
        return SectionSubject::create($data);
    }
}
