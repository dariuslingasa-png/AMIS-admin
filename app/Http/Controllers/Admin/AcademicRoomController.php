<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicRoomRequest;
use App\Models\AcademicRoom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AcademicRoomController extends Controller
{
    public function store(AcademicRoomRequest $request)
    {
        DB::transaction(fn () => AcademicRoom::create($request->validated()));

        return back()->with('status', 'Room configured successfully.');
    }

    public function update(AcademicRoomRequest $request, AcademicRoom $room)
    {
        DB::transaction(fn () => $room->update($request->validated()));

        return back()->with('status', 'Room configuration updated.');
    }

    public function destroy(AcademicRoom $room)
    {
        Gate::authorize('manage-academic');
        DB::transaction(fn () => $room->update(['status' => 'inactive']));

        return back()->with('status', 'Room archived. Existing schedules were preserved.');
    }
}
