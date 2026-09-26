<?php
namespace Tests\Validation;

use HexaGen\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_min_y_max_aceptan_decimales(): void
    {
        $validator = new Validator();

        $this->assertFalse($validator->validate(['ancho' => '0.3'], ['ancho' => 'numeric|min:0.5|max:12.5']));
        $this->assertArrayHasKey('ancho', $validator->getErrors());

        $this->assertTrue($validator->validate(['ancho' => '0.5'], ['ancho' => 'numeric|min:0.5|max:12.5']));
        $this->assertTrue($validator->validate(['ancho' => '12.5'], ['ancho' => 'numeric|min:0.5|max:12.5']));
        $this->assertFalse($validator->validate(['ancho' => '12.6'], ['ancho' => 'numeric|min:0.5|max:12.5']));
    }

    public function test_min_en_texto_sigue_contando_caracteres(): void
    {
        $validator = new Validator();

        $this->assertFalse($validator->validate(['nombre' => 'A'], ['nombre' => 'string|min:2']));
        $this->assertTrue($validator->validate(['nombre' => 'Ana'], ['nombre' => 'string|min:2']));
    }
}
