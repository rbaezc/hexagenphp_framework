<?php
namespace HexaGen\Core\Live;

use HexaGen\Core\Template\TemplateEngine;

abstract class LiveComponent
{
    private static string $appKey = 'HexaGenSecretKeySaltForLiveSlicesValidation'; // Can be loaded from env
    private string $id;

    public function __construct()
    {
        $this->id = 'live-' . uniqid();
    }

    /**
     * Retrieve all public properties (state) of the component.
     */
    public function getState(): array
    {
        $properties = get_object_vars($this);
        // Exclude internal component framework fields
        unset($properties['id']);
        return $properties;
    }

    /**
     * Hydrate component properties with client state.
     */
    public function hydrate(array $state): void
    {
        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Encrypt and sign (HMAC-SHA256) the current component state.
     */
    public function getSignedState(): string
    {
        $serialized = json_encode($this->getState());
        $signature = hash_hmac('sha256', $serialized, self::$appKey);
        return base64_encode($serialized) . '.' . $signature;
    }

    /**
     * Verify signature and decrypt state.
     */
    public static function decryptState(string $payload): ?array
    {
        $parts = explode('.', $payload);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedState, $signature] = $parts;
        $serialized = base64_decode($encodedState);
        
        $expectedSignature = hash_hmac('sha256', $serialized, self::$appKey);
        if (!hash_equals($expectedSignature, $signature)) {
            return null; // Tampering detected
        }

        return json_decode($serialized, true);
    }

    /**
     * Render the template and wrap it in the LiveComponent container with state payload.
     */
    protected function renderView(string $template, array $extraData = []): string
    {
        $engine = new TemplateEngine();
        
        // Combine model state and custom variables
        $data = array_merge($this->getState(), $extraData);
        $html = $engine->render($template, $data);

        $parts = explode('\\', static::class);
        $componentName = end($parts);

        $stateToken = $this->getSignedState();

        // Wrap the HTML with HTMX endpoints and target variables
        return sprintf(
            '<div id="%s" data-live-component="%s" data-live-state="%s" hx-target="this" hx-swap="outerHTML">%s</div>',
            $this->id,
            $componentName,
            $stateToken,
            $html
        );
    }

    abstract public function render(): string;
}
