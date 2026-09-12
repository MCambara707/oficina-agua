<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_raiz_envia_al_visitante_al_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
