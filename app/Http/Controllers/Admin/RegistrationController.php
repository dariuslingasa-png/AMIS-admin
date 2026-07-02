<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function halaqah(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = DB::table('contact_submissions')
            ->where('subject', 'Halaqah Online Registration');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($request->has('print')) {
            $registrations = $query->orderBy('created_at', 'desc')->get();
            return view('admin.registrations.print_halaqah', compact('registrations'));
        }

        $registrations = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.registrations.halaqah', compact('registrations', 'search', 'status'));
    }

    public function toggleStatus($id)
    {
        $submission = DB::table('contact_submissions')->where('id', $id)->first();
        if (!$submission) {
            return back()->with('error', 'Registration not found.');
        }

        $newStatus = $submission->status === 'contacted' ? 'new' : 'contacted';
        
        DB::table('contact_submissions')->where('id', $id)->update([
            'status' => $newStatus,
            'responded_at' => $newStatus === 'contacted' ? now() : null,
        ]);

        return back()->with('status', 'Registration status updated successfully.');
    }

    public function destroy($id)
    {
        DB::table('contact_submissions')->where('id', $id)->delete();
        return back()->with('status', 'Registration deleted successfully.');
    }
}
