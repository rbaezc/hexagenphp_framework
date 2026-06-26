# Validation

## FormRequest

The cleanest approach — validation runs before the controller method is even called:

```php
// src/Slices/Flights/Http/CreateFlightRequest.php
use HexaGen\Core\Http\FormRequest;

class CreateFlightRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'origin'      => 'required|string|min:3|max:3',
            'destination' => 'required|string|min:3|max:3|different:origin',
            'price'       => 'required|numeric|min:0',
            'class'       => 'required|in:economy,business,first',
            'date'        => 'required|date|after:today',
            'email'       => 'required|email|unique:flights,email',
        ];
    }

    public function messages(): array
    {
        return [
            'origin.required'     => 'The origin airport code is required.',
            'destination.different' => 'Origin and destination must be different.',
        ];
    }

    public function attributes(): array
    {
        return [
            'origin'      => 'origin airport',
            'destination' => 'destination airport',
        ];
    }
}
```

```php
// In controller — inject the FormRequest, validation is automatic
public function store(CreateFlightRequest $request): Response
{
    $flight = Flight::create($request->validated());
    return $this->json($flight->toArray(), 201);
}
```

If validation fails, a `422 Unprocessable Entity` JSON response is returned automatically.

## Available rules

| Rule | Description |
|---|---|
| `required` | Field must be present and non-empty |
| `nullable` | Field may be null |
| `sometimes` | Only validate if present |
| `string` | Must be a string |
| `numeric` | Must be numeric |
| `integer` | Must be an integer |
| `boolean` | Must be true/false/1/0 |
| `array` | Must be an array |
| `email` | Valid email address |
| `url` | Valid URL |
| `ip` / `ipv4` / `ipv6` | Valid IP address |
| `uuid` | Valid UUID v4 |
| `json` | Valid JSON string |
| `date` | Valid date |
| `accepted` | Must be yes/on/1/true |
| `min:n` | Min length/value |
| `max:n` | Max length/value |
| `between:min,max` | Between min and max |
| `size:n` | Exact length/value |
| `digits:n` | Exactly N digits |
| `digits_between:min,max` | Between N and M digits |
| `in:a,b,c` | Must be one of the values |
| `not_in:a,b` | Must not be one of the values |
| `same:field` | Must match another field |
| `different:field` | Must differ from another field |
| `confirmed` | Must match `field_confirmation` |
| `starts_with:a,b` | Must start with one of the values |
| `ends_with:a,b` | Must end with one of the values |
| `after:date` | Date must be after |
| `before:date` | Date must be before |
| `required_if:field,value` | Required when another field equals value |
| `unique:table,col` | Must be unique in DB |
| `unique:table,col,ignoreId` | Unique except for this ID (for updates) |
| `exists:table,col` | Must exist in DB |
