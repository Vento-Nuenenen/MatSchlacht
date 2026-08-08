<?php

namespace App\Mail\Transport;

use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\FileAttachment;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Graph\Generated\Models\Recipient;
use Microsoft\Graph\Generated\Users\Item\SendMail\SendMailPostRequestBody;
use Microsoft\Graph\GraphServiceClient;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message as MimeMessage;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{
    public function __construct(
        protected GraphServiceClient $graph,
        protected string $userId
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        try {
            $originalMessage = $message->getOriginalMessage();

            if (! $originalMessage instanceof MimeMessage) {
                throw new \Exception('Message is not an instance of Symfony\Component\Mime\Message');
            }

            /** @var Email $email */
            $email = MessageConverter::toEmail($originalMessage);

            $requestBody = new SendMailPostRequestBody;
            $graphMessage = new Message;

            $graphMessage->setSubject($email->getSubject() ?? '');

            $body = new ItemBody;
            $body->setContentType(new BodyType($email->getHtmlBody() ? BodyType::HTML : BodyType::TEXT));
            $body->setContent((string) ($email->getHtmlBody() ?: ($email->getTextBody() ?: '')));
            $graphMessage->setBody($body);

            $graphMessage->setToRecipients($this->mapContacts($email->getTo()));

            if ($cc = $email->getCc()) {
                $graphMessage->setCcRecipients($this->mapContacts($cc));
            }

            if ($bcc = $email->getBcc()) {
                $graphMessage->setBccRecipients($this->mapContacts($bcc));
            }

            if ($attachments = $email->getAttachments()) {
                $graphAttachments = [];
                foreach ($attachments as $attachment) {
                    $graphAttachment = new FileAttachment;
                    $graphAttachment->setName($attachment->getFilename());
                    $graphAttachment->setContentType($attachment->getHeaders()->get('Content-Type')?->getBodyAsString() ?? 'application/octet-stream');

                    /** @var string $attachmentBody */
                    $attachmentBody = $attachment->getBody();
                    $graphAttachment->setContentBytes(Utils::streamFor(base64_encode($attachmentBody)));
                    $graphAttachments[] = $graphAttachment;
                }
                $graphMessage->setAttachments($graphAttachments);
            }

            // If the message has a specific From address, and it is different from the configured userId,
            // we might want to try using it, but for Client Credentials flow with Mail.Send,
            // we must send via a specific user endpoint.
            $sender = $this->userId;
            $from = $email->getFrom();
            if (! empty($from)) {
                $sender = $from[0]->getAddress();
            }

            $requestBody->setMessage($graphMessage);
            $requestBody->setSaveToSentItems(true);

            $this->graph->users()->byUserId($sender)->sendMail()->post($requestBody)->wait();
        } catch (ODataError $e) {
            $error = $e->getError();
            $message = $error->getMessage() ?? 'Unknown Microsoft Graph error';
            $code = $error->getCode() ?? 'UnknownCode';

            throw new \Exception("Microsoft Graph Mail Error [{$code}]: {$message}", 0, $e);
        }
    }

    /**
     * @param  array<int, Address>  $contacts
     * @return array<int, Recipient>
     */
    protected function mapContacts(array $contacts): array
    {
        return array_map(function (Address $address) {
            $recipient = new Recipient;
            $emailAddress = new EmailAddress;
            $emailAddress->setAddress($address->getAddress());
            $emailAddress->setName($address->getName());
            $recipient->setEmailAddress($emailAddress);

            return $recipient;
        }, $contacts);
    }

    public function __toString(): string
    {
        return 'microsoftgraph';
    }
}
