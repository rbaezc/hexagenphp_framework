<?php
namespace Tests\Database;

use HexaGen\Core\Database\DatabaseConnection;
use HexaGen\Core\Database\Model;
use HexaGen\Core\Database\Schema\Blueprint;
use HexaGen\Core\Database\Schema\Schema;
use HexaGen\Core\Database\Traits\HasTimestamps;
use PHPUnit\Framework\TestCase;

final class Note extends Model
{
    use HasTimestamps;

    protected static string $table = 'notes';
    protected static array $fillable = ['title', 'done'];
    protected array $hidden = ['secret'];

    public ?int $id = null;
    public ?string $title = null;
    public ?bool $done = false;
}

/**
 * Model::save() solo debe persistir columnas (propiedades públicas declaradas),
 * nunca configuración protegida como $casts, $hidden o $timestamps.
 */
final class ModelSaveTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = (new DatabaseConnection())->getPdo();
        Model::setConnection(new class($pdo) extends DatabaseConnection {
            public function __construct(private \PDO $injected) {}
            public function getPdo(): \PDO { return $this->injected; }
        });

        (new Schema($pdo))->create('notes', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->boolean('done')->default(false);
            $t->timestamps();
        });
    }

    public function test_create_y_update_guardan_solo_columnas(): void
    {
        $note = Note::create(['title' => 'Primera', 'done' => true]);

        $this->assertNotNull($note->id);
        $this->assertNotNull($note->created_at);

        $note->title = 'Editada';
        $this->assertTrue($note->save());

        $fresh = Note::find($note->id);
        $this->assertSame('Editada', $fresh->title);
    }

    public function test_las_relaciones_cargadas_no_se_intentan_guardar(): void
    {
        $note = Note::create(['title' => 'Con relación']);
        $note->comments = [['id' => 1]]; // propiedad dinámica, como una relación cargada

        $this->assertTrue($note->save());
    }
}
