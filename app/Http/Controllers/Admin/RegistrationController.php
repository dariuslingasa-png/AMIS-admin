<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MicrosoftTeam;
use App\Models\MicrosoftTeamMembership;
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
        if (! in_array($tab, ['submissions', 'students', 'teams'])) {
            $tab = 'submissions';
        }

        $search = $request->input('search');
        $levelFilter = $request->input('level');
        $category = $request->input('category', 'all');

        if ($tab === 'students') {
            $status = 'approved';
            if ($category === 'all') {
                $category = 'student';
            }
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
            ->whereIn('subject', ['Halaqah Online Registration', 'Halaqah Qur’an', 'Halaqah Parents Registration']);

        $query2 = DB::table('halaqah_registrations')
            ->select(
                'id',
                'name',
                'email',
                'phone',
                DB::raw("CASE 
                    WHEN grade_level LIKE '%PARENTS%' OR type = 'parents' OR type LIKE '%Parents%' OR message LIKE '%Halaqah Parents%' THEN 'Halaqah Parents Registration'
                    ELSE 'Halaqah Online Registration' 
                END as subject"),
                DB::raw("CONCAT(
                    COALESCE(message, ''),
                    '\n--- Halaqah Registration Details ---\n',
                    'Address: ', COALESCE(address, ''),
                    '\nMS Teams Account: ', COALESCE(ms_teams, ''),
                    '\nFB Account Link: ', COALESCE(fb_account, COALESCE(ms_teams, '')),
                    '\nMobile Number: ', COALESCE(mobile, COALESCE(phone, '')),
                    '\nLearning Level: ', COALESCE(level, ''),
                    '\nGrade Level: ', COALESCE(grade_level, '')
                ) as message"),
                'status',
                'created_at',
                'responded_at',
                DB::raw("'halaqah_registrations' as source")
            );

        if ($category === 'student') {
            $query1->where(function ($q) {
                $q->where('subject', 'Halaqah Qur’an')
                  ->orWhere(function ($sub) {
                      $sub->where('subject', 'Halaqah Online Registration')
                          ->where('message', 'NOT LIKE', '%Registration Type:%');
                  });
            });
            $query2->where(function ($q) {
                $q->whereNull('grade_level')
                  ->orWhere(function ($sub) {
                      $sub->where('grade_level', 'NOT LIKE', '%REGISTRATION%')
                          ->where('grade_level', 'NOT LIKE', '%PARENTS%');
                  });
            })
            ->where(function ($q) {
                $q->whereNull('type')
                  ->orWhere('type', '!=', 'parents');
            });
        } elseif ($category === 'parents') {
            $query1->where(function ($q) {
                $q->where('subject', 'Halaqah Parents Registration')
                  ->orWhere(function ($sub) {
                      $sub->where('subject', 'Halaqah Online Registration')
                          ->where('message', 'LIKE', '%Registration Type:%');
                  });
            });
            $query2->where(function ($q) {
                $q->where('grade_level', 'LIKE', '%PARENTS%')
                  ->orWhere('grade_level', 'LIKE', '%ONLINE REGISTRATION%')
                  ->orWhere('type', 'parents')
                  ->orWhere('type', 'LIKE', '%Parents%')
                  ->orWhere('type', 'LIKE', '%Online%')
                  ->orWhere('message', 'LIKE', '%Halaqah Parents%')
                  ->orWhere('message', 'LIKE', '%Registration Type:%');
            });
        }

        if ($search) {
            $query1->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });

            $query2->where(function ($q) use ($search) {
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
                $query1->where(function ($q) {
                    $q->where('message', 'like', '%beginner%')
                        ->orWhere('message', 'like', '%cannot%');
                });
                $query2->where(function ($q) {
                    $q->where('level', 'like', '%beginner%')
                        ->orWhere('level', 'like', '%cannot%')
                        ->orWhere('message', 'like', '%beginner%')
                        ->orWhere('message', 'like', '%cannot%');
                });
            } else {
                $query1->where('message', 'like', "%{$levelFilter}%");
                $query2->where(function ($q) use ($levelFilter) {
                    $q->where('level', 'like', "%{$levelFilter}%")
                        ->orWhere('message', 'like', "%{$levelFilter}%");
                });
            }
        }

        // Direct query calculations for category & stats counts
        $q1Base = DB::table('contact_submissions')
            ->whereIn('subject', ['Halaqah Online Registration', 'Halaqah Qur’an', 'Halaqah Parents Registration']);
        $q2Base = DB::table('halaqah_registrations');

        if ($search) {
            $q1Base->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
            $q2Base->where(function ($q) use ($search) {
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
                $q1Base->where(function ($q) {
                    $q->where('message', 'like', '%beginner%')
                        ->orWhere('message', 'like', '%cannot%');
                });
                $q2Base->where(function ($q) {
                    $q->where('level', 'like', '%beginner%')
                        ->orWhere('level', 'like', '%cannot%')
                        ->orWhere('message', 'like', '%beginner%')
                        ->orWhere('message', 'like', '%cannot%');
                });
            } else {
                $q1Base->where('message', 'like', "%{$levelFilter}%");
                $q2Base->where(function ($q) use ($levelFilter) {
                    $q->where('level', 'like', "%{$levelFilter}%")
                        ->orWhere('message', 'like', "%{$levelFilter}%");
                });
            }
        }

        // Category-specific counts
        $q1Student = (clone $q1Base)->where(function ($q) {
            $q->where('subject', 'Halaqah Qur’an')
              ->orWhere(function ($sub) {
                  $sub->where('subject', 'Halaqah Online Registration')
                      ->where('message', 'NOT LIKE', '%Registration Type:%');
              });
        });
        $q2Student = (clone $q2Base)->where(function ($q) {
            $q->whereNull('grade_level')
              ->orWhere(function ($sub) {
                  $sub->where('grade_level', 'NOT LIKE', '%REGISTRATION%')
                      ->where('grade_level', 'NOT LIKE', '%PARENTS%');
              });
        })->where(function ($q) {
            $q->whereNull('type')->orWhere('type', '!=', 'parents');
        });
        $studentCount = $q1Student->count() + $q2Student->count();

        $q1Parents = (clone $q1Base)->where(function ($q) {
            $q->where('subject', 'Halaqah Parents Registration')
              ->orWhere(function ($sub) {
                  $sub->where('subject', 'Halaqah Online Registration')
                      ->where('message', 'LIKE', '%Registration Type:%');
              });
        });
        $q2Parents = (clone $q2Base)->where(function ($q) {
            $q->where('grade_level', 'LIKE', '%PARENTS%')
              ->orWhere('grade_level', 'LIKE', '%ONLINE REGISTRATION%')
              ->orWhere('type', 'parents')
              ->orWhere('type', 'LIKE', '%Parents%')
              ->orWhere('type', 'LIKE', '%Online%')
              ->orWhere('message', 'LIKE', '%Halaqah Parents%')
              ->orWhere('message', 'LIKE', '%Registration Type:%');
        });
        $parentsCount = $q1Parents->count() + $q2Parents->count();

        $totalAllCount = $q1Base->count() + $q2Base->count();

        // Stats calculation matching current active query filters
        $q1Stats = clone $query1;
        $q2Stats = clone $query2;

        $totalCount = (clone $q1Stats)->count() + (clone $q2Stats)->count();
        $newCount = (clone $q1Stats)->where('status', 'new')->count() + (clone $q2Stats)->where('status', 'new')->count();
        $approvedCount = (clone $q1Stats)->where('status', 'approved')->count() + (clone $q2Stats)->where('status', 'approved')->count();

        $q1CannotRead = clone $q1Stats;
        $q1CannotRead->where('message', 'like', '%Cannot read%');
        $q2CannotRead = clone $q2Stats;
        $q2CannotRead->where(function ($q) {
            $q->where('level', 'like', '%Cannot read%')->orWhere('message', 'like', '%Cannot read%');
        });
        $cannotReadCount = $q1CannotRead->count() + $q2CannotRead->count();

        $q1CanRead = clone $q1Stats;
        $q1CanRead->where('message', 'like', '%Can read%');
        $q2CanRead = clone $q2Stats;
        $q2CanRead->where(function ($q) {
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
            $team = MicrosoftTeam::with(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section'])
                ->where('team_category', 'halaqah')
                ->find($selectedTeamId);
            if ($team) {
                $memberships = MicrosoftTeamMembership::with(['student', 'faculty'])
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

        if (! $team) {
            $teams = MicrosoftTeam::with(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section'])
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
            'category',
            'totalAllCount',
            'studentCount',
            'onlinePublicCount',
            'parentsCount',
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
        if (! $submission) {
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

    public function halaqahParents(Request $request)
    {
        $tab = $request->input('tab', 'submissions');
        $search = $request->input('search');
        $status = $request->input('status');
        $levelFilter = $request->input('level');

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
            ->where('subject', 'Halaqah Parents Registration');

        $query2 = DB::table('halaqah_registrations')
            ->select(
                'id',
                'name',
                'email',
                'phone',
                DB::raw("'Halaqah Parents Registration' as subject"),
                DB::raw("CONCAT(
                    COALESCE(message, ''),
                    '\n--- Halaqah Registration Details ---\n',
                    'Address: ', COALESCE(address, ''),
                    '\nFB Account Link: ', COALESCE(fb_account, COALESCE(ms_teams, '')),
                    '\nMobile Number: ', COALESCE(mobile, COALESCE(phone, '')),
                    '\nLearning Level: ', COALESCE(level, ''),
                    '\nAge: ', COALESCE(CAST(age AS CHAR), ''),
                    '\nSex: ', COALESCE(sex, ''),
                    '\nCivil Status: ', COALESCE(status, '')
                ) as message"),
                'status',
                'created_at',
                'responded_at',
                DB::raw("'halaqah_registrations' as source")
            )
            ->where(function ($q) {
                $q->where('grade_level', 'LIKE', '%PARENTS%')
                  ->orWhere('type', 'parents')
                  ->orWhere('message', 'LIKE', '%Parents%');
            });

        if ($search) {
            $query1->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });

            $query2->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('level', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%")
                    ->orWhere('fb_account', 'like', "%{$search}%");
            });
        }

        if ($levelFilter && $levelFilter !== 'all') {
            $query1->where('message', 'like', "%{$levelFilter}%");
            $query2->where(function ($q) use ($levelFilter) {
                $q->where('level', 'like', "%{$levelFilter}%")
                    ->orWhere('message', 'like', "%{$levelFilter}%");
            });
        }

        // Stats calculation for union
        $q1Stats = DB::table('contact_submissions')
            ->where('subject', 'Halaqah Parents Registration');
        $q2Stats = DB::table('halaqah_registrations')
            ->where(function ($q) {
                $q->where('grade_level', 'LIKE', '%PARENTS%')
                  ->orWhere('type', 'parents')
                  ->orWhere('message', 'LIKE', '%Parents%');
            });

        if ($search) {
            $q1Stats->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
            $q2Stats->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('level', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%")
                    ->orWhere('fb_account', 'like', "%{$search}%");
            });
        }

        if ($levelFilter && $levelFilter !== 'all') {
            $q1Stats->where('message', 'like', "%{$levelFilter}%");
            $q2Stats->where(function ($q) use ($levelFilter) {
                $q->where('level', 'like', "%{$levelFilter}%")
                    ->orWhere('message', 'like', "%{$levelFilter}%");
            });
        }

        $totalCount = $q1Stats->count() + $q2Stats->count();
        $newCount = (clone $q1Stats)->where('status', 'new')->count() + (clone $q2Stats)->where('status', 'new')->count();
        $approvedCount = (clone $q1Stats)->where('status', 'approved')->count() + (clone $q2Stats)->where('status', 'approved')->count();

        $q1Cannot = clone $q1Stats;
        $q1Cannot->where('message', 'like', '%BEGINNER%');
        $q2Cannot = clone $q2Stats;
        $q2Cannot->where(function ($q) {
            $q->where('level', 'like', '%BEGINNER%')->orWhere('message', 'like', '%BEGINNER%');
        });
        $cannotReadCount = $q1Cannot->count() + $q2Cannot->count();

        $q1Can = clone $q1Stats;
        $q1Can->where('message', 'like', '%ADVANCE%');
        $q2Can = clone $q2Stats;
        $q2Can->where(function ($q) {
            $q->where('level', 'like', '%ADVANCE%')->orWhere('message', 'like', '%ADVANCE%');
        });
        $canReadCount = $q1Can->count() + $q2Can->count();

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

        $isParents = true;
        $pageTitle = 'Halaqah Parents Registrations';
        $pageDesc = 'Manage parent and guardian registration submissions for the AMIS Halaqah Parents Islamic education program.';

        return view('admin.registrations.halaqah_parents', compact(
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
            'isParents',
            'pageTitle',
            'pageDesc'
        ));
    }
}
