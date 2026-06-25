<?php
namespace HexaGen\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeRequestCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('make:request')
             ->setDescription('Generate a Form Request class.')
             ->addArgument('name', InputArgument::REQUIRED, 'Request name (e.g. StoreProductRequest)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $dir  = dirname(__DIR__, 4) . '/src/Requests';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = "{$dir}/{$name}.php";
        if (file_exists($file)) {
            $io->error("{$name} already exists.");
            return Command::FAILURE;
        }

        file_put_contents($file, <<<PHP
<?php
namespace HexaGen\Requests;

use HexaGen\Core\Http\FormRequest;

class {$name} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
PHP);

        $io->success("Form Request created: src/Requests/{$name}.php");
        return Command::SUCCESS;
    }
}
