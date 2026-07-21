<?php

namespace Tests\Feature;

use App\Mail\Transport\MicrosoftGraphTransport;
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

        // Mock environment variables for Microsoft Graph
        putenv('AZURE_TENANT_ID=fake-tenant-id');
        putenv('AZURE_CLIENT_ID=fake-client-id');
        putenv('AZURE_CLIENT_SECRET=fake-client-secret');
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
}
