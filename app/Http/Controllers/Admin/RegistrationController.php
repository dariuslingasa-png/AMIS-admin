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

        $query1 = DB::table('contact_submissions')
            ->select(
                'id',
                'name',
                'email',
                'phone',
                'subject',
                'message',
                'status',
                'created_at',
                'responded_at',
                DB::raw("'contact_submissions' as source")
            )
            ->whereIn('subject', ['Halaqah Online Registration', 'Halaqah Qur’an']);

        $query2 = DB::table('halaqah_registrations')
            ->select(
                'id',
                'name',
                'email',
                'phone',
                DB::raw("'Halaqah Online Registration' as subject"),
                DB::raw("CONCAT(
                    COALESCE(message, ''),
                    '\n--- Halaqah Registration Details ---\n',
                    'Address: ', COALESCE(address, ''),
                    '\nMS Teams Account: ', COALESCE(ms_teams, ''),
                    '\nLearning Level: ', COALESCE(level, ''),
                    '\nGrade Level: ', COALESCE(grade_level, '')
                ) as message"),
                'status',
                'created_at',
                'responded_at',
                DB::raw("'halaqah_registrations' as source")
            );

        if ($search) {
            $query1->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });

            $query2->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('level', 'like', "%{$search}%")
                  ->orWhere('grade_level', 'like', "%{$search}%")
                  ->orWhere('ms_teams', 'like', "%{$search}%");
            });
        }

        // Build stats query matching search criteria but independent of status filter
        $statsQuery1 = DB::table('contact_submissions')
            ->select(
                'id',
                'name',
                'email',
                'phone',
                'subject',
                'message',
                'status',
                'created_at',
                'responded_at',
                DB::raw("'contact_submissions' as source")
            )
            ->whereIn('subject', ['Halaqah Online Registration', 'Halaqah Qur’an']);

        $statsQuery2 = DB::table('halaqah_registrations')
            ->select(
                'id',
                'name',
                'email',
                'phone',
                DB::raw("'Halaqah Online Registration' as subject"),
                DB::raw("CONCAT(
                    COALESCE(message, ''),
                    '\n--- Halaqah Registration Details ---\n',
                    'Address: ', COALESCE(address, ''),
                    '\nMS Teams Account: ', COALESCE(ms_teams, ''),
                    '\nLearning Level: ', COALESCE(level, ''),
                    '\nGrade Level: ', COALESCE(grade_level, '')
                ) as message"),
                'status',
                'created_at',
                'responded_at',
                DB::raw("'halaqah_registrations' as source")
            );

        if ($search) {
            $statsQuery1->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });

            $statsQuery2->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('level', 'like', "%{$search}%")
                  ->orWhere('grade_level', 'like', "%{$search}%")
                  ->orWhere('ms_teams', 'like', "%{$search}%");
            });
        }

        $combinedStats = $statsQuery1->union($statsQuery2);
        $baseStatsQuery = DB::table(DB::raw("({$combinedStats->toSql()}) as combined"))
            ->mergeBindings($combinedStats);

        $totalCount = $baseStatsQuery->count();
        $newCount = (clone $baseStatsQuery)->where('status', 'new')->count();
        $approvedCount = (clone $baseStatsQuery)->where('status', 'approved')->count();

        if ($status) {
            $query1->where('status', $status);
            $query2->where('status', $status);
        }

        $combinedQuery = $query1->union($query2);

        $finalQuery = DB::table(DB::raw("({$combinedQuery->toSql()}) as combined"))
            ->mergeBindings($combinedQuery);

        if ($request->has('print')) {
            $registrations = $finalQuery->orderBy('created_at', 'desc')->get();
            return view('admin.registrations.print_halaqah', compact('registrations'));
        }

        $registrations = $finalQuery->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.registrations.halaqah', compact(
            'registrations', 
            'search', 
            'status', 
            'totalCount', 
            'newCount', 
            'approvedCount'
        ));
    }

    public function toggleStatus(Request $request, $id)
    {
        $source = $request->input('source', 'contact_submissions');
        $table = $source === 'halaqah_registrations' ? 'halaqah_registrations' : 'contact_submissions';

        $submission = DB::table($table)->where('id', $id)->first();
        if (!$submission) {
            return back()->with('error', 'Registration not found.');
        }

        $newStatus = $submission->status === 'approved' ? 'new' : 'approved';
        
        DB::table($table)->where('id', $id)->update([
            'status' => $newStatus,
            'responded_at' => $newStatus === 'approved' ? now() : null,
        ]);

        return back()->with('status', 'Registration status updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $source = $request->input('source', 'contact_submissions');
        $table = $source === 'halaqah_registrations' ? 'halaqah_registrations' : 'contact_submissions';

        DB::table($table)->where('id', $id)->delete();
        return back()->with('status', 'Registration deleted successfully.');
    }
}
