<?php
namespace Tests\Live;

use HexaGen\Core\Live\LiveComponent;
use PHPUnit\Framework\TestCase;

final class Counter extends LiveComponent
{
    public string $label = '';
    public bool $locked = false;

    protected array $guarded = ['locked'];

    public function render(): string
    {
        return '';
    }
}

final class LiveComponentTest extends TestCase
{
    public function test_el_navegador_no_puede_vaciar_guarded_ni_cambiar_el_id(): void
    {
        $component = new Counter();
        $readId    = \Closure::bind(fn () => $this->id, $component, LiveComponent::class);
        $idBefore  = $readId();

        $component->hydrateFromInput([
            'guarded' => [],          // intento de vaciar la lista protegida
            'locked'  => true,        // propiedad protegida
            'id'      => '"><script>', // id interno
            'label'   => 'hola',      // propiedad permitida
        ]);

        $this->assertFalse($component->locked);
        $this->assertSame('hola', $component->label);
        $this->assertSame($idBefore, $readId());
    }

    public function test_el_estado_cifrado_es_verificable_y_detecta_alteraciones(): void
    {
        $component = new Counter();
        $component->label = 'x';
        $token = $component->getSignedState();

        $this->assertSame('x', LiveComponent::decryptState($token)['label']);

        $tampered = substr($token, 0, 20) . (($token[20] === 'A') ? 'B' : 'A') . substr($token, 21);
        $this->assertNull(LiveComponent::decryptState($tampered));
    }
}
