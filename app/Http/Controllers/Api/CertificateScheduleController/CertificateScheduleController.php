<?php

namespace App\Http\Controllers\Api\CertificateScheduleController;

use App\Http\Controllers\Controller;
use App\Models\CertificateSchedule;
use App\Repositories\CertificateScheduleRepository;
use Illuminate\Http\Request;

class CertificateScheduleController extends Controller
{
    protected CertificateScheduleRepository $repo;

    public function __construct(CertificateScheduleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        return response()->json($this->repo->all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'submission_start_date' => 'nullable|date',
            'submission_end_date' => 'nullable|date',
            'submission_note' => 'nullable|string',
            'submission_end_date_only' => 'nullable|date',
            'submission_end_note' => 'nullable|string',
            'evaluation_start_date' => 'nullable|date',
            'evaluation_end_date' => 'nullable|date',
            'evaluation_note' => 'nullable|string',
            'announcement_date' => 'nullable|date',
            'announcement_note' => 'nullable|string',
            'awarding_start_date' => 'nullable|date',
            'awarding_end_date' => 'nullable|date',
            'awarding_note' => 'nullable|string',
        ]);

        $schedule = $this->repo->create($data);
        return response()->json($schedule, 201);
    }

    public function show(CertificateSchedule $certificateSchedule)
    {
        return response()->json($certificateSchedule);
    }

    public function update(Request $request, CertificateSchedule $certificateSchedule)
    {
        $data = $request->validate([
            'submission_start_date' => 'nullable|date',
            'submission_end_date' => 'nullable|date',
            'submission_note' => 'nullable|string',
            'submission_end_date_only' => 'nullable|date',
            'submission_end_note' => 'nullable|string',
            'evaluation_start_date' => 'nullable|date',
            'evaluation_end_date' => 'nullable|date',
            'evaluation_note' => 'nullable|string',
            'announcement_date' => 'nullable|date',
            'announcement_note' => 'nullable|string',
            'awarding_start_date' => 'nullable|date',
            'awarding_end_date' => 'nullable|date',
            'awarding_note' => 'nullable|string',
        ]);

        $updated = $this->repo->update($certificateSchedule, $data);
        return response()->json($updated);
    }

    public function destroy(CertificateSchedule $certificateSchedule)
    {
        $this->repo->delete($certificateSchedule);
        return response()->noContent();
    }
}