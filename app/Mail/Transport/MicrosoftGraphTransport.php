<?php

namespace App\Mail\Transport;

use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{
    protected string $tenantId;
    protected string $clientId;
    protected string $clientSecret;
    protected string $userId;

    public function __construct(string $tenantId, string $clientId, string $clientSecret, string $userId)
    {
        parent::__construct();
        $this->tenantId = $tenantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->userId = $userId;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $accessToken = $this->getAccessToken();

        $graphMessage = [
            'subject' => $email->getSubject(),
            'body' => [
                'contentType' => $email->getHtmlBody() ? 'HTML' : 'Text',
                'content' => $email->getHtmlBody() ?: $email->getTextBody(),
            ],
            'toRecipients' => $this->mapContacts($email->getTo()),
            'ccRecipients' => $this->mapContacts($email->getCc()),
            'bccRecipients' => $this->mapContacts($email->getBcc()),
        ];

        if ($email->getReplyTo()) {
            $graphMessage['replyTo'] = $this->mapContacts($email->getReplyTo());
        }

        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename') ?: 'attachment',
                'contentType' => $attachment->getMediaType() . '/' . $attachment->getMediaSubtype(),
                'contentBytes' => base64_encode($attachment->getBody()),
            ];
        }

        if (!empty($attachments)) {
            $graphMessage['attachments'] = $attachments;
        }

        $response = Http::withToken($accessToken)
            ->post("https://graph.microsoft.com/v1.0/users/{$this->userId}/sendMail", [
                'message' => $graphMessage,
                'saveToSentItems' => 'true',
            ]);

        if ($response->failed()) {
            throw new Exception('Failed to send email via Microsoft Graph: ' . $response->body());
        }
    }

    protected function mapContacts(array $contacts): array
    {
        return array_map(function (Address $address) {
            return [
                'emailAddress' => [
                    'name' => $address->getName(),
                    'address' => $address->getAddress(),
                ],
            ];
        }, $contacts);
    }

    protected function getAccessToken(): string
    {
        $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

        $response = Http::asForm()->post($url, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            throw new Exception('Failed to get access token for Microsoft Graph: ' . $response->body());
        }

        return $response->json('access_token');
    }

    public function __toString(): string
    {
        return 'microsoftgraph';
    }
}
