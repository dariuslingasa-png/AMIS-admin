<?php

namespace Tests\Unit;

use App\Services\System\MicrosoftGraphMailService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MicrosoftGraphMailServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('services.microsoft_graph_mail', [
            'enabled' => true,
            'tenant_id' => 'tenant-id',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'senders' => ['sender-one@amis.edu.ph', 'sender-two@amis.edu.ph'],
            'per_minute_limit' => 1,
            'daily_limit' => 10,
        ]);
    }

    public function test_it_rotates_graph_senders_when_the_minute_guard_is_reached(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'login.microsoftonline.com')) {
                return Http::response(['access_token' => 'test-access-token'], 200);
            }

            return Http::response([], 202);
        });

        $service = app(MicrosoftGraphMailService::class);
        $message = [
            'subject' => 'Test advisory',
            'body' => ['contentType' => 'HTML', 'content' => '<p>Test</p>'],
        ];

        $first = $service->send('parent-one@example.com', $message);
        $second = $service->send('parent-two@example.com', $message);

        $this->assertSame('microsoft_graph:sender-one@amis.edu.ph', $first['mailer_used']);
        $this->assertSame('microsoft_graph:sender-two@amis.edu.ph', $second['mailer_used']);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/users/sender-one%40amis.edu.ph/sendMail')
            && $request['message']['toRecipients'][0]['emailAddress']['address'] === 'parent-one@example.com');
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/users/sender-two%40amis.edu.ph/sendMail')
            && $request['message']['toRecipients'][0]['emailAddress']['address'] === 'parent-two@example.com');
    }

    public function test_it_balances_successive_messages_across_both_graph_senders(): void
    {
        config()->set('services.microsoft_graph_mail.per_minute_limit', 25);

        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'login.microsoftonline.com')) {
                return Http::response(['access_token' => 'test-access-token'], 200);
            }

            return Http::response([], 202);
        });

        $service = app(MicrosoftGraphMailService::class);
        $message = [
            'subject' => 'Test advisory',
            'body' => ['contentType' => 'HTML', 'content' => '<p>Test</p>'],
        ];

        $first = $service->send('parent-one@example.com', $message);
        $second = $service->send('parent-two@example.com', $message);

        $this->assertSame('microsoft_graph:sender-one@amis.edu.ph', $first['mailer_used']);
        $this->assertSame('microsoft_graph:sender-two@amis.edu.ph', $second['mailer_used']);
    }
}
