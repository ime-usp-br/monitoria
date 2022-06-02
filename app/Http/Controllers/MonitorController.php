<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use romanzipp\QueueMonitor\Models\Monitor;

class MonitorController extends Controller
{
    public function getImportSchoolClassesJob()
    {
        $max_job_id = Monitor::where(['name'=>'App\Jobs\ProcessGetSchoolClassesFromReplicado'])->max('job_id');
        $max_progress = Monitor::where(['job_id'=>$max_job_id])->max('progress');
        return response()->json(Monitor::where(['job_id'=>$max_job_id, 
                                                'progress'=>$max_progress])
                                        ->first());
    }
}
