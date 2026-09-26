<?php
namespace Tests\Bootstrap;

use HexaGen\Core\Bootstrap\EnvLoader;
use PHPUnit\Framework\TestCase;

final class EnvLoaderTest extends TestCase
{
    private string $file;
    private array $keys = ['HG_T_PLAIN', 'HG_T_DQ', 'HG_T_SQ', 'HG_T_COMMENT', 'HG_T_EXPORT', 'HG_T_EMPTY', 'HG_T_KEEP'];

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($this->file, implode("\n", [
            '# comentario',
            'HG_T_PLAIN=hola',
            'HG_T_DQ="valor con espacios # no es comentario"',
            "HG_T_SQ='literal \$HOME'",
            'HG_T_COMMENT=abc # comentario al final',
            'export HG_T_EXPORT=exportado',
            'HG_T_EMPTY=',
            'HG_T_KEEP=desde-archivo',
            'no es una linea valida',
        ]));
        foreach ($this->keys as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        foreach ($this->keys as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    public function test_carga_valores_con_y_sin_comillas(): void
    {
        EnvLoader::load($this->file);

        $this->assertSame('hola', getenv('HG_T_PLAIN'));
        $this->assertSame('valor con espacios # no es comentario', getenv('HG_T_DQ'));
        $this->assertSame('literal $HOME', getenv('HG_T_SQ'));
        $this->assertSame('abc', getenv('HG_T_COMMENT'));
        $this->assertSame('exportado', getenv('HG_T_EXPORT'));
        $this->assertSame('', getenv('HG_T_EMPTY'));
    }

    public function test_no_sobrescribe_variables_del_sistema(): void
    {
        putenv('HG_T_KEEP=del-sistema');

        EnvLoader::load($this->file);

        $this->assertSame('del-sistema', getenv('HG_T_KEEP'));
    }

    public function test_ignora_archivos_inexistentes(): void
    {
        EnvLoader::load($this->file . '.no-existe');
        $this->assertFalse(getenv('HG_T_PLAIN'));
    }

    public function test_helper_env_convierte_tipos(): void
    {
        putenv('HG_T_PLAIN=true');
        $this->assertTrue(env('HG_T_PLAIN'));
        putenv('HG_T_PLAIN=null');
        $this->assertNull(env('HG_T_PLAIN'));
        $this->assertSame('defecto', env('HG_T_NO_EXISTE', 'defecto'));
    }
}
