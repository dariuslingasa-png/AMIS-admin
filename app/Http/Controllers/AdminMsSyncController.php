<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\MsSync\CleansMsSyncTestAccounts;
use App\Http\Controllers\Admin\MsSync\ManagesAzureStudentSync;
use App\Http\Controllers\Admin\MsSync\RetriesMsTeamsSync;

class AdminMsSyncController extends Controller
{
    use CleansMsSyncTestAccounts;
    use ManagesAzureStudentSync;
    use RetriesMsTeamsSync;
}
