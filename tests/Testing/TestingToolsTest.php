<?php
namespace Tests\Testing;

use HexaGen\Core\Testing\TestResponse;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las aserciones de TestResponse deben fallar de verdad aunque
 * zend.assertions esté desactivado (valor de producción).
 */
final class TestingToolsTest extends TestCase
{
    public function test_assert_status_falla_con_un_codigo_distinto(): void
    {
        $this->expectException(AssertionFailedError::class);
        (new TestResponse(new Response('', 500)))->assertOk();
    }

    public function test_assert_json_path_compara_valores(): void
    {
        $response = new TestResponse(new Response('{"data":{"total":3}}', 200));
        $response->assertOk()->assertJsonPath('data.total', 3);

        $this->expectException(AssertionFailedError::class);
        $response->assertJsonPath('data.total', 4);
    }
}
