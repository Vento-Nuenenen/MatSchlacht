<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailTestController extends Controller
{
    public function sendTestMail(Request $request)
    {
        $recipient = $request->query('email', config('mail.from.address'));

        if (! $recipient) {
            return response()->json(['error' => 'No recipient email specified and mail.from.address is empty.'], 400);
        }

        try {
            Mail::to($recipient)->send(new TestMail);

            return response()->json([
                'status' => 'success',
                'message' => "Test email sent successfully to {$recipient}.",
                'mailer' => config('mail.default'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send test email: '.$e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}
