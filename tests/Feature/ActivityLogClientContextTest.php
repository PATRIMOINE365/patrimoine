<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\UserAgentContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Activity Log client context: raw User-Agent capture plus the frozen
 * browser / platform / device derived from it at record time.
 */
class ActivityLogClientContextTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_WINDOWS =
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
        . '(KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    private const SAFARI_IPHONE =
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) '
        . 'AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 '
        . 'Mobile/15E148 Safari/604.1';

    public function test_record_freezes_ip_user_agent_browser_platform_and_device(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Administrator,
        ]);

        $request = Request::create(
            '/api/example',
            'POST',
            server: [
                'REMOTE_ADDR' => '192.0.2.77',
                'HTTP_USER_AGENT' => self::CHROME_WINDOWS,
            ]
        );

        $request->setUserResolver(
            fn (): User => $user
        );

        $event = app(ActivityLogService::class)->record(
            action: 'test.client_context',
            request: $request,
        );

        $this->assertSame('192.0.2.77', $event->ip_address);
        $this->assertSame(self::CHROME_WINDOWS, $event->user_agent);
        $this->assertSame('Chrome 128', $event->browser);
        $this->assertSame('Windows', $event->platform);
        $this->assertSame('Desktop', $event->device);
    }

    public function test_record_without_request_leaves_client_context_null(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Administrator,
        ]);

        $event = app(ActivityLogService::class)->record(
            action: 'test.no_request',
            actor: $user,
        );

        $this->assertNull($event->user_agent);
        $this->assertNull($event->browser);
        $this->assertNull($event->platform);
        $this->assertNull($event->device);
    }

    public function test_list_and_detail_endpoints_expose_client_context(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Administrator,
        ]);

        $request = Request::create(
            '/api/example',
            'POST',
            server: [
                'REMOTE_ADDR' => '198.51.100.9',
                'HTTP_USER_AGENT' => self::SAFARI_IPHONE,
            ]
        );

        $request->setUserResolver(
            fn (): User => $user
        );

        $event = app(ActivityLogService::class)->record(
            action: 'test.endpoint_exposure',
            request: $request,
        );

        $this->actingAs($user)
            ->getJson('/api/activity-log')
            ->assertOk()
            ->assertJsonPath('data.0.browser', 'Safari 17')
            ->assertJsonPath('data.0.platform', 'iOS')
            ->assertJsonPath('data.0.device', 'Mobile');

        $this->actingAs($user)
            ->getJson("/api/activity-log/{$event->id}")
            ->assertOk()
            ->assertJsonPath('browser', 'Safari 17')
            ->assertJsonPath('platform', 'iOS')
            ->assertJsonPath('device', 'Mobile')
            ->assertJsonPath('user_agent', self::SAFARI_IPHONE);
    }

    public function test_user_agent_parser_classifies_common_clients(): void
    {
        $parser = new UserAgentContextService();

        $edge = $parser->parse(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36 Edg/127.0.2651.74'
        );
        $this->assertSame('Microsoft Edge 127', $edge['browser']);
        $this->assertSame('Windows', $edge['platform']);
        $this->assertSame('Desktop', $edge['device']);

        $firefoxLinux = $parser->parse(
            'Mozilla/5.0 (X11; Linux x86_64; rv:129.0) Gecko/20100101 Firefox/129.0'
        );
        $this->assertSame('Firefox 129', $firefoxLinux['browser']);
        $this->assertSame('Linux', $firefoxLinux['platform']);
        $this->assertSame('Desktop', $firefoxLinux['device']);

        $androidTablet = $parser->parse(
            'Mozilla/5.0 (Linux; Android 14; SM-X910) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36'
        );
        $this->assertSame('Chrome 126', $androidTablet['browser']);
        $this->assertSame('Android', $androidTablet['platform']);
        $this->assertSame('Tablet', $androidTablet['device']);

        $androidPhone = $parser->parse(
            'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36'
        );
        $this->assertSame('Mobile', $androidPhone['device']);

        $empty = $parser->parse(null);
        $this->assertNull($empty['browser']);
        $this->assertNull($empty['platform']);
        $this->assertNull($empty['device']);
    }
}
