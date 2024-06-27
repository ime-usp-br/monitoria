<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use romanzipp\QueueMonitor\Models\Monitor;

class MonitorController extends Controller
{
    public function getImportSchoolClassesJob()
    {
        $max_id = Monitor::where(['name'=>'App\Jobs\ProcessGetSchoolClassesFromReplicado'])->max('id');
        $max_progress = Monitor::where(['id'=>$max_id])->max('progress');
        return response()->json(Monitor::where(['id'=>$max_id, 
                                                'progress'=>$max_progress])
                                        ->first());
    }

    public function getImportOldDBJob()
    {
        $max_id = Monitor::where(['name'=>'App\Jobs\ProcessImportOldDB'])->max('id');
        $max_progress = Monitor::where(['id'=>$max_id])->max('progress');
        return response()->json(Monitor::where(['id'=>$max_id, 
                                                'progress'=>$max_progress])
                                        ->first());
    }
}
