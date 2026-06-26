# Storage

## Basic usage

```php
use HexaGen\Core\Storage\Storage;

// Store a file
Storage::put('invoices/inv-001.pdf', $pdfContent);

// Read a file
$content = Storage::get('invoices/inv-001.pdf');

// Check existence
Storage::exists('invoices/inv-001.pdf');

// Delete
Storage::delete('invoices/inv-001.pdf');

// Public URL
$url = Storage::url('invoices/inv-001.pdf');
// → http://my-app.com/storage/invoices/inv-001.pdf
```

## Uploading from a request

```php
public function upload(Request $request): Response
{
    $file = $request->files->get('photo');

    $path = Storage::put(
        'photos/' . uniqid() . '.' . $file->getClientOriginalExtension(),
        file_get_contents($file->getPathname())
    );

    return $this->json(['path' => $path, 'url' => Storage::url($path)]);
}
```

## Named disks

```php
// Local disk (default)
Storage::disk('local')->put('file.txt', 'content');

// S3
Storage::disk('s3')->put('backups/db.sql.gz', $content);
$url = Storage::disk('s3')->url('backups/db.sql.gz');
```

## Drivers

```ini
# Local
FILESYSTEM_DRIVER=local
STORAGE_PATH=storage/app

# S3
FILESYSTEM_DRIVER=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-bucket
```

## Configuration

```php
// config/filesystems.php
return [
    'default' => env('FILESYSTEM_DRIVER', 'local'),
    'disks'   => [
        'local' => [
            'driver' => 'local',
            'root'   => base_path('storage/app'),
        ],
        's3' => [
            'driver' => 's3',
            'key'    => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
        ],
    ],
];
```
