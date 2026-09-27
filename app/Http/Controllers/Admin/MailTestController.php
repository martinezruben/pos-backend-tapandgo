<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MailConfigurationService;
use Illuminate\Http\Request;

class MailTestController extends Controller
{
    protected MailConfigurationService $mailService;

    public function __construct(MailConfigurationService $mailService)
    {
        $this->mailService = $mailService;
        $this->middleware('auth:admin');
        $this->middleware('gate:system_parameters.edit');
    }

    public function validateSmtp()
    {
        return response()->json($this->mailService->validateSmtpConnection());
    }

    public function validateOffice365()
    {
        return response()->json($this->mailService->validateOffice365Connection());
    }

    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'to_address' => 'required|email',
        ]);

        return response()->json(
            $this->mailService->testEmail($validated['to_address'])
        );
    }
}
