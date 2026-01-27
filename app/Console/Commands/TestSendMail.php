<?php

namespace App\Console\Commands;

use App\Mail\TestMail;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestSendMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email via Microsoft Graph API';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $email = $this->argument('email');
        $this->info("Sending test email to {$email}...");

        try {
            Mail::to($email)->send(new TestMail());
            $this->info('Email sent successfully!');
        } catch (Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
        }
    }
}
