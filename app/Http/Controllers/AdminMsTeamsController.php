<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\MsTeams\ManagesMsTeamSections;
use App\Http\Controllers\Admin\MsTeams\ManagesMsTeamSubjects;
use App\Http\Controllers\Admin\MsTeams\RepairsMsTeamsAccess;

class AdminMsTeamsController extends Controller
{
    use ManagesMsTeamSections;
    use ManagesMsTeamSubjects;
    use RepairsMsTeamsAccess;
}
