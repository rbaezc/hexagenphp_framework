# OpenAPI

HexaGen can generate an **OpenAPI 3.0.3** specification directly from your route definitions.

## Generate the spec

```bash
php hexaphp openapi:generate
```

Outputs `openapi.json` in the project root.

## Annotating routes

Add PHPDoc annotations to your controller methods:

```php
class FlightsController extends AbstractController
{
    /**
     * @openapi
     * /flights:
     *   get:
     *     summary: List available flights
     *     tags: [Flights]
     *     parameters:
     *       - name: origin
     *         in: query
     *         schema:
     *           type: string
     *           example: MEX
     *       - name: destination
     *         in: query
     *         schema:
     *           type: string
     *           example: MAD
     *     responses:
     *       200:
     *         description: List of flights
     *         content:
     *           application/json:
     *             schema:
     *               type: object
     *               properties:
     *                 data:
     *                   type: array
     *                   items:
     *                     $ref: '#/components/schemas/Flight'
     */
    public function index(Request $request): Response { ... }

    /**
     * @openapi
     * /flights:
     *   post:
     *     summary: Create a flight
     *     tags: [Flights]
     *     security:
     *       - bearerAuth: []
     *     requestBody:
     *       required: true
     *       content:
     *         application/json:
     *           schema:
     *             $ref: '#/components/schemas/CreateFlightRequest'
     *     responses:
     *       201:
     *         description: Flight created
     *       422:
     *         description: Validation error
     */
    public function store(CreateFlightRequest $request): Response { ... }
}
```

## Serving the spec

You can expose the generated spec via a route:

```php
Route::get('/api/openapi.json', function () {
    return response()->file(base_path('openapi.json'), [
        'Content-Type' => 'application/json',
    ]);
});
```

Then use it with **Swagger UI**, **Redoc**, or **Scalar**:

```html
<!-- Scalar UI — drop this in a blade/twig view -->
<script id="api-reference"
        data-url="/api/openapi.json"
        src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
```
