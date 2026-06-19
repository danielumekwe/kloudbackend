<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServerAccessControlTest extends TestCase
{
    private function fakeWhmcs(array $ownedServiceIds): void
    {
        Http::fake(function (ClientRequest $request) use ($ownedServiceIds) {
            $action = $request->data()['action'] ?? null;

            return match ($action) {
                'GetClientsProducts' => isset($request->data()['serviceid'])
                    ? Http::response(['result' => 'success', 'products' => ['product' => ['id' => 99, 'name' => 'Test VPS']]])
                    : Http::response([
                        'result' => 'success',
                        'products' => ['product' => array_map(fn ($id) => ['id' => $id, 'name' => "Service {$id}"], $ownedServiceIds)],
                    ]),
                'ModuleCommand' => Http::response(['result' => 'success']),
                'GetCurrencies' => Http::response(['result' => 'error']),
                default => Http::response(['result' => 'error'], 404),
            };
        });
    }

    public function test_client_cannot_view_another_clients_server(): void
    {
        $this->fakeWhmcs(ownedServiceIds: [10, 20]);

        $response = $this->withSession(['clientId' => 7])->get('/servers/99');

        $response->assertStatus(404);
    }

    public function test_client_can_view_their_own_server(): void
    {
        $this->fakeWhmcs(ownedServiceIds: [99]);

        $response = $this->withSession(['clientId' => 7])->get('/servers/99');

        $response->assertOk();
    }

    public function test_client_cannot_run_an_action_on_another_clients_server(): void
    {
        $this->fakeWhmcs(ownedServiceIds: [10, 20]);

        $response = $this->withSession(['clientId' => 7])->post('/servers/99/action', ['command' => 'reboot']);

        $response->assertStatus(404);
    }

    public function test_client_can_run_an_action_on_their_own_server(): void
    {
        $this->fakeWhmcs(ownedServiceIds: [99]);

        $response = $this->withSession(['clientId' => 7])->post('/servers/99/action', ['command' => 'reboot']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
