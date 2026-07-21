<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZKTecoParser
{
    /**
     * Parse Attendance Log file (*_attlog.dat)
     * Plain text, tab/space-separated.
     */
    public function parseAttendance(string $path): array
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: " . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 6) {
                $logs[] = [
                    'employee_id' => (int)$parts[0],
                    'datetime' => $parts[1] . ' ' . $parts[2],
                    'verify_mode' => (int)$parts[3],
                    'in_out_mode' => (int)$parts[4],
                    'work_code' => (int)$parts[5],
                    'reserved' => (int)$parts[6],
                ];
            }
        }

        return $logs;
    }

    /**
     * Parse User file (user.dat)
     * Binary file.
     */
    public function parseUsers(string $path): array
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: " . $path);
        }

        $data = file_get_contents($path);
        $length = strlen($data);
        $offset = 0;
        $users = [];

        // Legacy ZKTeco user record size is typically 72 bytes.
        // Let's read in 72-byte blocks. If the file structure is different, we handle it gracefully.
        $recordSize = 72;

        while ($offset + $recordSize <= $length) {
            $chunk = substr($data, $offset, $recordSize);
            
            // Unpack according to structure:
            // v PIN (2 bytes)
            // C Privilege (1 byte)
            // a8 Password (8 bytes)
            // a24 Name (24 bytes)
            // C4 Card (4 bytes)
            // C Group (1 byte)
            // v4 TimeZones (8 bytes)
            // a24 PIN2 (User ID) (24 bytes)
            $unpacked = unpack('vPIN/CPrivilege/a8Password/a24Name/C4Card/CGroup/v4TimeZones/a24PIN2', $chunk);

            if ($unpacked) {
                $employeeIdStr = trim($unpacked['PIN2']);
                $employeeId = $employeeIdStr !== '' ? (int)$employeeIdStr : (int)$unpacked['PIN'];
                
                // Clean strings from null characters
                $name = trim(str_replace("\0", "", $unpacked['Name']));
                $password = trim(str_replace("\0", "", $unpacked['Password']));
                
                // Build card number from C4 card bytes
                $cardNum = ($unpacked['Card1'] | ($unpacked['Card2'] << 8) | ($unpacked['Card3'] << 16) | ($unpacked['Card4'] << 24));

                $users[] = [
                    'employee_id' => $employeeId,
                    'name' => $name ?: 'Employee ' . $employeeId,
                    'department_id' => (int)$unpacked['Group'], // Default department maps to group/department index
                    'card_number' => $cardNum ? (string)$cardNum : null,
                    'privilege' => (int)$unpacked['Privilege'],
                    'password' => $password ?: null,
                    'status' => 0, // default status active
                    'raw_bytes' => bin2hex($chunk),
                ];
            }

            $offset += $recordSize;
        }

        return $users;
    }

    /**
     * Parse Department file (department.dat)
     * Binary or plain text depending on firmware export format.
     */
    public function parseDepartments(string $path): array
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: " . $path);
        }

        $data = file_get_contents($path);
        
        // Simple heuristic: if it contains typical text patterns, parse as tab/space separated text
        if (!preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', substr($data, 0, 500))) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $departments = [];
            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', trim($line), 2);
                if (count($parts) >= 2) {
                    $departments[] = [
                        'id' => (int)$parts[0],
                        'name' => trim($parts[1]),
                    ];
                }
            }
            return $departments;
        }

        // Parse as binary: 2 bytes ID (uint16) + 30 bytes Name (char) = 32 bytes chunks
        $length = strlen($data);
        $offset = 0;
        $departments = [];
        $recordSize = 32;

        while ($offset + $recordSize <= $length) {
            $chunk = substr($data, $offset, $recordSize);
            $unpacked = unpack('vID/a30Name', $chunk);
            if ($unpacked) {
                $name = trim(str_replace("\0", "", $unpacked['Name']));
                if ($unpacked['ID'] > 0 || $name !== '') {
                    $departments[] = [
                        'id' => (int)$unpacked['ID'],
                        'name' => $name ?: 'Department ' . $unpacked['ID'],
                    ];
                }
            }
            $offset += $recordSize;
        }

        return $departments;
    }

    /**
     * Parse Face Templates file (ssrface.dat)
     */
    public function parseFaceTemplates(string $path): array
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: " . $path);
        }

        $data = file_get_contents($path);
        $length = strlen($data);
        $offset = 0;
        $results = [];

        if ($length >= 6) {
            // Check size of first template
            $size = unpack('v', substr($data, 0, 2))[1];
            $is8Byte = false;

            // If size matches the remainder of file or looks like 8-byte record header
            if ($length >= 8) {
                $is8Byte = true;
            }

            while ($offset < $length) {
                if ($length - $offset < 6) break;

                $size = unpack('v', substr($data, $offset, 2))[1];
                if ($size <= 0 || $size > 50000) break;

                if ($is8Byte && ($length - $offset >= 8)) {
                    $header = unpack('vSize/VPIN/CIndex/CValid', substr($data, $offset, 8));
                    $userId = $header['PIN'];
                    $faceIndex = $header['Index'];
                    $templateLength = $size;
                    $recordSize = 8 + $size;
                    
                    if ($offset + $recordSize > $length) {
                        $is8Byte = false;
                        continue;
                    }
                } else {
                    $header = unpack('vSize/vPIN/CIndex/CValid', substr($data, $offset, 6));
                    $userId = $header['PIN'];
                    $faceIndex = $header['Index'];
                    $templateLength = $size;
                    $recordSize = 6 + $size;
                }

                $results[] = [
                    'user_id' => $userId,
                    'face_index' => $faceIndex,
                    'template_length' => $templateLength
                ];

                $offset += $recordSize;
            }
        }

        return $results;
    }

    /**
     * Parse Fingerprint Templates file (template.fp10)
     */
    public function parseFingerprintTemplates(string $path): array
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: " . $path);
        }

        $data = file_get_contents($path);
        $length = strlen($data);
        $offset = 0;
        $results = [];

        if ($length >= 6) {
            while ($offset < $length) {
                if ($length - $offset < 6) break;

                $size = unpack('v', substr($data, $offset, 2))[1];
                if ($size <= 0 || $size > 50000) break;

                // Try 6-byte header
                $header = unpack('vSize/vPIN/CFingerID/CValid', substr($data, $offset, 6));
                $userId = $header['PIN'];
                $fingerIndex = $header['FingerID'];
                $templateSize = $size;
                $recordSize = 6 + $size;

                if ($offset + $recordSize > $length) {
                    if ($length - $offset >= 8) {
                        $header = unpack('vSize/VPIN/CFingerID/CValid', substr($data, $offset, 8));
                        $userId = $header['PIN'];
                        $fingerIndex = $header['FingerID'];
                        $templateSize = $size;
                        $recordSize = 8 + $size;
                    } else {
                        break;
                    }
                }

                $results[] = [
                    'user_id' => $userId,
                    'finger_index' => $fingerIndex,
                    'template_size' => $templateSize
                ];

                $offset += $recordSize;
            }
        }

        return $results;
    }

    /**
     * Generate Attendance Report
     */
    public function generateAttendanceReport(array $attLogs, array $users = [], array $departments = [], array $scheduleConfig = []): array
    {
        $timeInLimit = $scheduleConfig['time_in'] ?? '07:30:00';
        $timeOutLimit = $scheduleConfig['time_out'] ?? '16:00:00';

        // Map users and departments for fast lookup
        $userMap = [];
        foreach ($users as $u) {
            $userMap[$u['employee_id']] = $u;
        }

        $deptMap = [];
        foreach ($departments as $d) {
            $deptMap[$d['id']] = $d['name'];
        }

        // Group logs by employee and date
        $groupedLogs = [];
        foreach ($attLogs as $log) {
            $empId = $log['employee_id'];
            $dt = new \DateTime($log['datetime']);
            $dateStr = $dt->format('Y-m-d');
            $timeStr = $dt->format('H:i:s');

            $groupedLogs[$empId][$dateStr][] = $timeStr;
        }

        $report = [];

        foreach ($groupedLogs as $empId => $dates) {
            $user = $userMap[$empId] ?? null;
            $name = $user ? $user['name'] : 'Employee ' . $empId;
            $deptName = $user ? ($deptMap[$user['department_id']] ?? 'Main') : 'Main';

            foreach ($dates as $dateStr => $times) {
                // Sort times ascending
                sort($times);
                
                $timeInStr = $times[0];
                $timeOutStr = count($times) > 1 ? end($times) : null;

                $timeInDt = new \DateTime($dateStr . ' ' . $timeInStr);
                $timeOutDt = $timeOutStr ? new \DateTime($dateStr . ' ' . $timeOutStr) : null;

                $schedInDt = new \DateTime($dateStr . ' ' . $timeInLimit);
                $schedOutDt = new \DateTime($dateStr . ' ' . $timeOutLimit);

                // Calculate Late (minutes)
                $lateMinutes = 0;
                if ($timeInDt > $schedInDt) {
                    $diff = $timeInDt->diff($schedInDt);
                    $lateMinutes = ($diff->h * 60) + $diff->i;
                }

                $undertimeMinutes = 0;
                $overtimeMinutes = 0;
                $totalHours = 0.0;
                $totalHoursFormatted = '—';

                // Determine Status: PRESENT or LATE based on Time In only
                if ($timeInDt <= $schedInDt) {
                    $status = 'PRESENT';
                } else {
                    $status = 'LATE';
                }

                // Determine Remarks
                if (!$timeOutDt) {
                    $remarksStr = 'Missing Time Out';
                } else {
                    $timeOutTimeStr = $timeOutDt->format('H:i:s');
                    $timeOutFormatted = $timeOutDt->format('g:i A');

                    if ($timeOutTimeStr < '16:00:00') {
                        $remarksStr = 'Early Time Out';
                    } elseif ($timeOutTimeStr === '16:00:00') {
                        $remarksStr = '—';
                    } else {
                        // Time Out is after 4:00 PM
                        if ($timeInDt > $schedInDt) {
                            $remarksStr = "Late by {$lateMinutes} minutes; Overtime until {$timeOutFormatted}";
                        } else {
                            $remarksStr = "Overtime until {$timeOutFormatted}";
                        }
                    }

                    // Total Hours calculations
                    // Working hours start: 7:30 AM or Time In if late
                    $calcStartDt = $timeInDt > $schedInDt ? $timeInDt : $schedInDt;
                    // Working hours end: 4:00 PM or Time Out if early
                    $calcEndDt = $timeOutDt >= $schedOutDt ? $schedOutDt : $timeOutDt;

                    $diffHours = $calcEndDt->diff($calcStartDt);
                    $diffMins = ($diffHours->h * 60) + $diffHours->i;
                    if ($calcEndDt < $calcStartDt) {
                        $diffMins = 0;
                    }

                    $totalHours = round($diffMins / 60, 2);

                    $hoursVal = floor($diffMins / 60);
                    $minsVal = $diffMins % 60;
                    if ($hoursVal > 0) {
                        $totalHoursFormatted = $minsVal > 0 ? "{$hoursVal} hrs {$minsVal} mins" : "{$hoursVal} hrs";
                    } else {
                        $totalHoursFormatted = "{$minsVal} mins";
                    }
                }

                $report[] = [
                    'employee_id' => $empId,
                    'name' => $name,
                    'department' => $deptName,
                    'date' => $dateStr,
                    'time_in' => $timeInStr,
                    'time_out' => $timeOutStr ?: '—',
                    'late' => $lateMinutes > 0 ? $this->formatDuration($lateMinutes) : '0m',
                    'undertime' => $undertimeMinutes > 0 ? $this->formatDuration($undertimeMinutes) : '0m',
                    'overtime' => $overtimeMinutes > 0 ? $this->formatDuration($overtimeMinutes) : '0m',
                    'total_hours' => $totalHours,
                    'total_hours_formatted' => $totalHoursFormatted,
                    'status' => $status,
                    'remarks' => $remarksStr
                ];
            }
        }

        // Sort report by date desc, then name asc
        usort($report, function($a, $b) {
            $dateCmp = strcmp($b['date'], $a['date']);
            if ($dateCmp !== 0) return $dateCmp;
            return strcmp($a['name'], $b['name']);
        });

        return $report;
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . 'm';
        }
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . 'h ' . $mins . 'm';
    }

    /**
     * Compile users array back to binary user.dat format (72 bytes records)
     */
    public function compileUsers(array $users): string
    {
        $binary = '';
        foreach ($users as $u) {
            $empId = (int)$u['employee_id'];
            $privilege = (int)($u['privilege'] ?? 0);
            $password = (string)($u['password'] ?? '');
            $name = (string)($u['name'] ?? '');
            $cardInt = (int)($u['card_number'] ?? 0);
            $deptId = (int)($u['department_id'] ?? 0);

            $card1 = $cardInt & 0xFF;
            $card2 = ($cardInt >> 8) & 0xFF;
            $card3 = ($cardInt >> 16) & 0xFF;
            $card4 = ($cardInt >> 24) & 0xFF;

            // Pad password to 8 bytes and name to 24 bytes with null characters
            $paddedPassword = str_pad(substr($password, 0, 8), 8, "\0");
            $paddedName = str_pad(substr($name, 0, 24), 24, "\0");
            $paddedPin2 = str_pad((string)$empId, 24, "\0");

            $binary .= pack(
                'vCa8a24C4Cv4a24',
                $empId & 0xFFFF,
                $privilege,
                $paddedPassword,
                $paddedName,
                $card1, $card2, $card3, $card4,
                $deptId,
                0, 0, 0, 0, // Timezones
                $paddedPin2
            );
        }
        return $binary;
    }
}
