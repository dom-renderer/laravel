<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request) {

        if ($request->ajax()) {
            $attendances = Attendance::myAttendanceForToday()->orderBy('io_time', 'DESC');

            return dataTables($attendances)
            ->addColumn('time', function ($row) {
                return date('d-m-Y H:i', strtotime($row->io_time));
            })
            ->editColumn('type', function ($row) {
                if ($row->type != 0) {
                    if ($row->type == 1) {
                        return '<span class="badge-success"> CHECKED IN </span>';
                    } else {
                        return '<span class="badge-danger"> CHECKED OUT </span>';
                    }
                } else {
                    if ($row->break_type == 1) {
                        return '<span class="badge-success"> CHECKED IN FROM BREAK </span>';
                    } else if ($row->break_type == 2) {
                        return '<span class="badge-danger"> CHECKED OUT FOR BREAK </span>';
                    }
                }
            })
            ->rawColumns(['type'])
            ->addIndexColumn()
            ->toJson();
        }

        $moduleName = 'Attendance';
        $date = date('d-m-Y');
        $entries = Attendance::myAttendanceForToday()->get()->toArray();
        $time = '00:00:00';

        $workedHours = 0;

        if (count($entries) > 0) {
            if (count($entries) % 2 == 0) {
                $times = array_column($entries, 'io_time');
                $times = array_chunk($times, 2);

                foreach ($times as $timestamp) {
                    $workedHours += Carbon::parse($timestamp[0])->diffInSeconds($timestamp[1]);
                }
            } else {
                $times = array_column($entries, 'io_time');
                array_push($times, date('Y-m-d H:i:s'));

                if (count($times) % 2 == 0) {
                    $times = array_chunk($times, 2);

                    foreach ($times as $timestamp) {
                        $workedHours += Carbon::parse($timestamp[0])->diffInSeconds($timestamp[1]);
                    }
                }
            }
        }

        if ($workedHours != 0) {
            $time = gmdate('H:i:s', $workedHours);
        }

        $showCheckOutForBreakBtn = $showCheckInFromBreakBtn = $showCheckOutBtn = $showCheckInBtn = false;

        $i = Attendance::myAttendanceForToday()->checkIn()->count();
        $o = Attendance::myAttendanceForToday()->checkOut()->count();
        $bI = Attendance::myAttendanceForToday()->breakIn()->count();
        $bO = Attendance::myAttendanceForToday()->breakOut()->count();

        if ($i > 0) {
            if ($i == $o) {
                $showCheckInBtn = true;
            }
        } else {
            $showCheckInBtn = true;
        }

        if ($i > 0) {
            if ($i == $o + 1) {
                if ($bO == $bI) { 
                    $showCheckOutBtn = true;
                }
            }
        }

        if ($i > 0) {
            if ($i == $o + 1) {
                if ($bO > 0 && $bO == $bI  + 1) {
                    $showCheckInFromBreakBtn = true;
                }
            }
        }

        if ($i > 0) {
            if ($i == $o + 1) {
                if ($bO == $bI) {
                    $showCheckOutForBreakBtn = true;
                }
            }
        }

        return view('master.attendance.index', compact('moduleName', 'date', 'entries', 'time', 'showCheckOutForBreakBtn', 'showCheckInFromBreakBtn', 'showCheckOutBtn', 'showCheckInBtn'));
    }

    public function in() {
        $in = Attendance::myAttendanceForToday()->checkIn()->count();
        $out = Attendance::myAttendanceForToday()->checkOut()->count();

        $shouldCreateRecord = false;

        if ($in > 0) {
            if ($in == $out) {
                $shouldCreateRecord = true;
            }
        } else {
            $shouldCreateRecord = true;
        }

        if ($shouldCreateRecord) {
            Attendance::create([
                'user_id' => auth()->user()->id,
                'date' => date('Y-m-d'),
                'io_time' => now(),
                'type' => 1,
                'break_type' => 0
            ]);
        }

        return redirect()->route('attendance')->with('success', 'Checked in successfully');
    }

    public function out() {
        $in = Attendance::myAttendanceForToday()->checkIn()->count();
        $out = Attendance::myAttendanceForToday()->checkOut()->count();

        $breakIn = Attendance::myAttendanceForToday()->breakIn()->count();
        $breakOut = Attendance::myAttendanceForToday()->breakOut()->count();

        if ($in > 0) {
            if ($in == $out + 1) {
                if ($breakOut == $breakIn + 1) {
                    Attendance::create([
                        'user_id' => auth()->user()->id,
                        'date' => date('Y-m-d'),
                        'io_time' => now(),
                        'type' => 0,
                        'break_type' => 1
                    ]);
                }

                Attendance::create([
                    'user_id' => auth()->user()->id,
                    'date' => date('Y-m-d'),
                    'io_time' => now(),
                    'type' => 2,
                    'break_type' => 0
                ]);
            }
        }

        return redirect()->route('attendance')->with('success', 'Checked out successfully');
    }

    public function breakIn() {
        $in = Attendance::myAttendanceForToday()->checkIn()->count();
        $out = Attendance::myAttendanceForToday()->checkOut()->count();

        $breakIn = Attendance::myAttendanceForToday()->breakIn()->count();
        $breakOut = Attendance::myAttendanceForToday()->breakOut()->count();

        $shouldCreateRecord = false;

        if ($in > 0) {
            if ($in == $out + 1) {
                if ($breakOut > 0 && $breakOut == $breakIn  + 1) {
                    $shouldCreateRecord = true;
                }
            }
        }

        if ($shouldCreateRecord) {
            Attendance::create([
                'user_id' => auth()->user()->id,
                'date' => date('Y-m-d'),
                'io_time' => now(),
                'type' => 0,
                'break_type' => 1
            ]);
        }

        return redirect()->route('attendance')->with('success', 'Break checked in successfully');
    }

    public function breakOut() {
        $in = Attendance::myAttendanceForToday()->checkIn()->count();
        $out = Attendance::myAttendanceForToday()->checkOut()->count();

        $breakIn = Attendance::myAttendanceForToday()->breakIn()->count();
        $breakOut = Attendance::myAttendanceForToday()->breakOut()->count();

        $shouldCreateRecord = false;

        if ($in > 0) {
            if ($in == $out + 1) {
                if ($breakOut == $breakIn) {
                    $shouldCreateRecord = true;
                }
            }
        }

        if ($shouldCreateRecord) {
            Attendance::create([
                'user_id' => auth()->user()->id,
                'date' => date('Y-m-d'),
                'io_time' => now(),
                'type' => 0,
                'break_type' => 2
            ]);
        }

        return redirect()->route('attendance')->with('success', 'Break checked out successfully');        
    }
}
