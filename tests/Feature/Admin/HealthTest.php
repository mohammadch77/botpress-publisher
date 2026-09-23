<?php

it('returns ok from the health endpoint', function () {
    $response = $this->getJson('/api/admin/health');

    $response->assertOk()->assertJson(['status' => 'ok']);
});
