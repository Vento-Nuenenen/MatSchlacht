<?php

namespace Tests\Feature;

use App\Mail\Transport\MicrosoftGraphTransport;
use Illuminate\Mail\Transport\LogTransport;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MicrosoftGraphMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        Config::set('services.microsoft.tenant', 'fake-tenant-id');
        Config::set('services.microsoft.client_id', 'fake-client-id');
        Config::set('services.microsoft.client_secret', 'fake-client-secret');
    }

    public function test_microsoftgraph_mailer_is_defined()
    {
        $mailer = Mail::mailer('microsoftgraph');
        $this->assertNotNull($mailer);
    }

    public function test_microsoftgraph_mailer_uses_microsoft_graph_transport()
    {
        $transport = Mail::mailer('microsoftgraph')->getSymfonyTransport();
        $this->assertInstanceOf(MicrosoftGraphTransport::class, $transport);
    }

    public function test_microsoftgraph_mailer_falls_back_to_the_log_transport_without_credentials()
    {
        Config::set('services.microsoft.client_secret', null);

        $transport = Mail::mailer('microsoftgraph')->getSymfonyTransport();
        $this->assertInstanceOf(LogTransport::class, $transport);
    }
}
