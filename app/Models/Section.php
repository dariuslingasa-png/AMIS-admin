<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = [
        'name',
        'grade_level',
        'learning_mode',
        'shift',
        'gender',
        'ms_team_id',
        'ms_team_url',
    ];

    public function subjects()
    {
        return $this->hasMany(SectionSubject::class);
    }

    public function students()
    {
        return $this->hasMany(StudentSection::class);
    }

    public function advisoryAssignments()
    {
        return $this->hasMany(ClassAdvisoryAssignment::class);
    }

    public function activeAdvisory()
    {
        return $this->hasOne(ClassAdvisoryAssignment::class)->where('status', 'active');
    }

    public function getOfficialNameAttribute(): ?string
    {
        $shift = $this->shift;
        $shiftLower = strtolower((string) $shift);
        if (empty($shift) || str_contains($shiftLower, 'f2f') || str_contains($shiftLower, 'full')) {
            $modeLower = strtolower((string) $this->learning_mode);
            $nameLower = strtolower((string) $this->name);
            if (str_contains($modeLower, '2nd') || str_contains($nameLower, '2nd') || str_contains($modeLower, 'second')) {
                $shift = '2nd Shift';
            } else {
                $shift = '1st Shift';
            }
        }

        $grade = $this->grade_level;
        // Normalize grade names (e.g., G1 -> Grade 1, K2 -> Kinder 2)
        $grade = str_ireplace(['Kindergarten 1', 'Kinder 1', 'K1'], 'Kinder 1', $grade);
        $grade = str_ireplace(['Kindergarten 2', 'Kinder 2', 'K2'], 'Kinder 2', $grade);
        $grade = str_ireplace(['Grade 10', 'G10'], 'Grade 10', $grade);
        $grade = str_ireplace(['Grade 11', 'G11'], 'Grade 11', $grade);
        $grade = str_ireplace(['Grade 12', 'G12'], 'Grade 12', $grade);
        $grade = preg_replace('/^G([1-9])$/i', 'Grade $1', $grade);
        $grade = preg_replace('/^Grade\s+([1-9])$/i', 'Grade $1', $grade);

        $gender = strtolower($this->gender ?? 'male');
        if (str_contains($gender, 'boy') || str_contains($gender, 'male')) {
            $gender = 'male';
        } elseif (str_contains($gender, 'girl') || str_contains($gender, 'female')) {
            $gender = 'female';
        }

        $key = implode('|', [
            $grade,
            $shift,
            $gender,
        ]);

        $names = [
            'Kinder 1|1st Shift|male' => 'HUSAYN IBN ALI',
            'Kinder 1|1st Shift|female' => 'HUSAYN IBN ALI',
            'Kinder 1|2nd Shift|male' => 'HUSAYN IBN ALI',
            'Kinder 1|2nd Shift|female' => 'HUSAYN IBN ALI',
            
            'Kinder 2|1st Shift|male' => 'ABU BAKR AS-SIDDEEQ',
            'Kinder 2|1st Shift|female' => 'UTHMAN IBN AFFAN',
            'Kinder 2|2nd Shift|male' => 'UMAR IBN AL-KHATTAB',
            'Kinder 2|2nd Shift|female' => "ABDULLAH IBN MAS'UD",
            
            'Grade 1|1st Shift|male' => 'HUDHAYFAH IBN AL-YAMAN',
            'Grade 1|1st Shift|female' => 'ALI IBN ABI TALIB',
            'Grade 1|2nd Shift|male' => 'SUHAYB AR-RUMI',
            'Grade 1|2nd Shift|female' => "SA'D IBN ABI WAQQAS",
            
            'Grade 2|1st Shift|male' => 'AMR IBN AL-JAMUH',
            'Grade 2|1st Shift|female' => 'TALHAH IBN UBAYDULLAH',
            'Grade 2|2nd Shift|male' => 'SAEED IBN ZAYD',
            'Grade 2|2nd Shift|female' => 'ASIM IBN THABIT',
            
            'Grade 3|1st Shift|male' => 'AMMAR IBN YASIR',
            'Grade 3|1st Shift|female' => 'HABIB IBN ZAYD AL-ANSARI',
            'Grade 3|2nd Shift|male' => 'THABIT IBN QAYS',
            'Grade 3|2nd Shift|female' => 'ZAYD IBN HARITHA',
            
            'Grade 4|1st Shift|male' => 'ABDUR RAHMAN IBN AWF',
            'Grade 4|1st Shift|female' => 'HAKIM IBN HIZAM',
            'Grade 4|2nd Shift|male' => 'IKRIMAH IBN ABI JAHL',
            'Grade 4|2nd Shift|female' => 'AZ-ZUBAIR IBN AL AWWAM',
            
            'Grade 5|1st Shift|male' => 'MUHAMMAD IBN MASLAMAH',
            'Grade 5|1st Shift|female' => 'HAMZA IBN ABDUL-MUTTALIB',
            'Grade 5|2nd Shift|male' => "MUS'AB IBN UMAIR",
            'Grade 5|2nd Shift|female' => "MUS'AB IBN UMAIR",
            
            'Grade 6|1st Shift|male' => 'ABBAS IBN ABD AL-MUTTALIB',
            'Grade 6|1st Shift|female' => 'ABDULLAH IBN SALAM',
            'Grade 6|2nd Shift|male' => 'KHALID IBN WALID',
            'Grade 6|2nd Shift|female' => 'KHALID IBN WALID',
            
            'Grade 7|1st Shift|male' => 'ABU SUFYAN IBN AL-HARITH',
            'Grade 7|1st Shift|female' => 'USAMA IBN ZAYD',
            'Grade 7|2nd Shift|male' => 'ANAS IBN MALIK',
            'Grade 7|2nd Shift|female' => 'ANAS IBN MALIK',
            
            'Grade 8|1st Shift|male' => "SA'AD IBN MU'ADH",
            'Grade 8|1st Shift|female' => "SA'AD IBN MU'ADH",
            'Grade 8|2nd Shift|male' => "MU'ADH IBN JABAL",
            'Grade 8|2nd Shift|female' => "NU'AYM IBN MAS'UD",
            
            'Grade 9|1st Shift|male' => 'ABU HURAYRAH',
            'Grade 9|1st Shift|female' => 'ABU HURAYRAH',
            'Grade 9|2nd Shift|male' => 'ABU DHARR AL-GHIFARI',
            'Grade 9|2nd Shift|female' => 'ABU DHARR AL-GHIFARI',
            
            'Grade 10|1st Shift|male' => 'UTBAH IBN GHAZWAN',
            'Grade 10|1st Shift|female' => 'UTBAH IBN GHAZWAN',
            'Grade 10|2nd Shift|male' => 'ABU AYYUB AL-ANSARI',
            'Grade 10|2nd Shift|female' => 'ABU AYYUB AL-ANSARI',
            
            'Grade 11|1st Shift|male' => 'ABU UBAYDAH IBN AL-JARRAH',
            'Grade 11|1st Shift|female' => 'ABU UBAYDAH IBN AL-JARRAH',
            'Grade 11|2nd Shift|male' => 'ABU UBAY IBN HATIM',
            'Grade 11|2nd Shift|female' => 'ABU UBAY IBN HATIM',
            
            'Grade 12|1st Shift|male' => "ABU MUSA AL-ASH'ARI",
            'Grade 12|1st Shift|female' => "ABU MUSA AL-ASH'ARI",
            'Grade 12|2nd Shift|male' => 'SUHAYB AR-RUMI',
            'Grade 12|2nd Shift|female' => 'SUHAYB AR-RUMI',
        ];

        if (isset($names[$key])) {
            return $names[$key];
        }

        // Try to find ANY name matching grade level as a fallback
        foreach ($names as $k => $nameVal) {
            if (str_starts_with($k, $grade . '|')) {
                return $nameVal;
            }
        }

        return null;
    }

    public function getSectionTitleAttribute(): string
    {
        $name = $this->official_name ?: ($this->name && $this->name !== 'A' ? $this->name : 'General');

        return "{$this->grade_level} - {$name}";
    }

    /** Human-readable label */
    public function getDisplayNameAttribute(): string
    {
        $grade  = $this->grade_level;
        $name   = $this->official_name ?: ($this->name && $this->name !== 'A' ? $this->name : 'General');
        $shift  = $this->shift ?? 'F2F';
        $gender = ucfirst($this->gender === 'male' ? 'Boys' : 'Girls');
        $year   = $this->school_year;
        return "{$grade} - {$name} {$shift} {$gender} {$year}";
    }
}
