<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;

            // Extract "from" address
            $fromAddresses = $message->getFrom();
            $from = '';
            foreach ($fromAddresses as $address) {
                $from = $address->getAddress();
                break;
            }

            // Extract "to" addresses
            $toAddresses = $message->getTo();
            $toList = [];
            foreach ($toAddresses as $address) {
                $toList[] = $address->getAddress();
            }

            // Extract subject
            $subject = $message->getSubject() ?? '';

            // Extract message ID
            $messageId = $event->sent->getMessageId() ?? null;

            // Determine mailer name from event data
            $mailer = $event->data['mailer'] ?? config('mail.default', 'smtp');

            // Determine transport from config
            $transport = config("mail.mailers.{$mailer}.transport", $mailer);

            EmailLog::create([
                'mailer' => $mailer,
                'transport' => $transport,
                'from_address' => $from,
                'to_addresses' => implode(', ', $toList),
                'subject' => Str::limit($subject, 497, '...'),
                'status' => 'sent',
                'error_message' => null,
                'message_id' => $messageId ? Str::limit($messageId, 497, '...') : null,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let logging break email delivery
            Log::warning('Failed to log sent email: '.$e->getMessage());
        }
    }
}
