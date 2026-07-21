<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function halaqah(Request $request)
    {
        $tab = $request->input('tab', 'submissions');
        if ($tab === 'rosters') {
            $tab = 'teams';
        }
        if (!in_array($tab, ['submissions', 'students', 'teams'])) {
            $tab = 'submissions';
        }

        $search = $request->input('search');
        $levelFilter = $request->input('level');

        if ($tab === 'students') {
            $status = 'approved';
        } else {
            $status = $request->input('status');
        }

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

        if ($levelFilter && $levelFilter !== 'all') {
            if (strtolower($levelFilter) === 'beginner') {
                $query1->where(function($q) {
                    $q->where('message', 'like', '%beginner%')
                      ->orWhere('message', 'like', '%cannot%');
                });
                $query2->where(function($q) {
                    $q->where('level', 'like', '%beginner%')
                      ->orWhere('level', 'like', '%cannot%')
                      ->orWhere('message', 'like', '%beginner%')
                      ->orWhere('message', 'like', '%cannot%');
                });
            } else {
                $query1->where('message', 'like', "%{$levelFilter}%");
                $query2->where(function($q) use ($levelFilter) {
                    $q->where('level', 'like', "%{$levelFilter}%")
                      ->orWhere('message', 'like', "%{$levelFilter}%");
                });
            }
        }

        // Build stats query matching search/level criteria but independent of status filter
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

        if ($levelFilter && $levelFilter !== 'all') {
            if (strtolower($levelFilter) === 'beginner') {
                $statsQuery1->where(function($q) {
                    $q->where('message', 'like', '%beginner%')
                      ->orWhere('message', 'like', '%cannot%');
                });
                $statsQuery2->where(function($q) {
                    $q->where('level', 'like', '%beginner%')
                      ->orWhere('level', 'like', '%cannot%')
                      ->orWhere('message', 'like', '%beginner%')
                      ->orWhere('message', 'like', '%cannot%');
                });
            } else {
                $statsQuery1->where('message', 'like', "%{$levelFilter}%");
                $statsQuery2->where(function($q) use ($levelFilter) {
                    $q->where('level', 'like', "%{$levelFilter}%")
                      ->orWhere('message', 'like', "%{$levelFilter}%");
                });
            }
        }

        // Direct, bug-free query calculations for union counts
        $q1Stats = DB::table('contact_submissions')
            ->whereIn('subject', ['Halaqah Online Registration', 'Halaqah Qur’an']);
        $q2Stats = DB::table('halaqah_registrations');

        if ($search) {
            $q1Stats->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
            $q2Stats->where(function($q) use ($search) {
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

        if ($levelFilter && $levelFilter !== 'all') {
            if (strtolower($levelFilter) === 'beginner') {
                $q1Stats->where(function($q) {
                    $q->where('message', 'like', '%beginner%')
                      ->orWhere('message', 'like', '%cannot%');
                });
                $q2Stats->where(function($q) {
                    $q->where('level', 'like', '%beginner%')
                      ->orWhere('level', 'like', '%cannot%')
                      ->orWhere('message', 'like', '%beginner%')
                      ->orWhere('message', 'like', '%cannot%');
                });
            } else {
                $q1Stats->where('message', 'like', "%{$levelFilter}%");
                $q2Stats->where(function($q) use ($levelFilter) {
                    $q->where('level', 'like', "%{$levelFilter}%")
                      ->orWhere('message', 'like', "%{$levelFilter}%");
                });
            }
        }

        $totalCount = $q1Stats->count() + $q2Stats->count();
        $newCount = (clone $q1Stats)->where('status', 'new')->count() + (clone $q2Stats)->where('status', 'new')->count();
        $approvedCount = (clone $q1Stats)->where('status', 'approved')->count() + (clone $q2Stats)->where('status', 'approved')->count();

        $q1CannotRead = clone $q1Stats;
        $q1CannotRead->where('message', 'like', '%Cannot read%');
        $q2CannotRead = clone $q2Stats;
        $q2CannotRead->where(function($q) {
            $q->where('level', 'like', '%Cannot read%')->orWhere('message', 'like', '%Cannot read%');
        });
        $cannotReadCount = $q1CannotRead->count() + $q2CannotRead->count();

        $q1CanRead = clone $q1Stats;
        $q1CanRead->where('message', 'like', '%Can read%');
        $q2CanRead = clone $q2Stats;
        $q2CanRead->where(function($q) {
            $q->where('level', 'like', '%Can read%')->orWhere('message', 'like', '%Can read%');
        });
        $canReadCount = $q1CanRead->count() + $q2CanRead->count();

        if ($status) {
            $query1->where('status', $status);
            $query2->where('status', $status);
        }

        $combinedQuery = $query1->union($query2);

        $finalQuery = DB::table(DB::raw("({$combinedQuery->toSql()}) as combined"))
            ->mergeBindings($combinedQuery);

        if ($request->has('print')) {
            $registrations = $finalQuery->orderBy('created_at', 'desc')->get();
            return view('admin.registrations.print_halaqah', compact('registrations', 'tab'));
        }

        $registrations = $finalQuery->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $selectedTeamId = $request->input('team_id');
        $team = null;
        $memberships = null;
        $teams = collect();
        
        if ($selectedTeamId) {
            $team = \App\Models\MicrosoftTeam::with(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section'])
                ->where('team_category', 'halaqah')
                ->find($selectedTeamId);
            if ($team) {
                $memberships = \App\Models\MicrosoftTeamMembership::with(['student', 'faculty'])
                    ->where('microsoft_team_local_id', $team->id)
                    ->when($request->filled('member_search'), function ($q) use ($request) {
                        $search = $request->string('member_search')->trim();
                        $q->where(function ($sub) use ($search) {
                            $sub->where('display_name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('user_principal_name', 'like', '%'.$search.'%')
                                ->orWhereHas('student', function ($s) use ($search) {
                                    $s->where('student_number', 'like', '%'.$search.'%');
                                });
                        });
                    })
                    ->orderBy('display_name')
                    ->paginate(30)
                    ->withQueryString();
            }
        }
        
        if (!$team) {
            $teams = \App\Models\MicrosoftTeam::with(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section'])
                ->where('team_category', 'halaqah')
                ->when($request->filled('team_search'), function ($q) use ($request) {
                    $q->where('display_name', 'like', '%'.$request->string('team_search')->trim().'%');
                })
                ->orderBy('display_name')
                ->paginate(20)
                ->withQueryString();
        }

        return view('admin.registrations.halaqah', compact(
            'tab',
            'registrations', 
            'search', 
            'status', 
            'levelFilter',
            'totalCount', 
            'newCount', 
            'approvedCount',
            'cannotReadCount',
            'canReadCount',
            'teams',
            'team',
            'memberships'
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

        return redirect()->route('admin.registrations.halaqah', [
            'tab' => $request->input('tab', 'submissions'),
            'level' => $request->input('level')
        ])->with('status', 'Registration status updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $source = $request->input('source', 'contact_submissions');
        $table = $source === 'halaqah_registrations' ? 'halaqah_registrations' : 'contact_submissions';

        DB::table($table)->where('id', $id)->delete();
        return redirect()->route('admin.registrations.halaqah', [
            'tab' => $request->input('tab', 'submissions'),
            'level' => $request->input('level')
        ])->with('status', 'Registration deleted successfully.');
    }

}
