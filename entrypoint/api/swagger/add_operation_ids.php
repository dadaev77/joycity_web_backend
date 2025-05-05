<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Symfony\Component\Finder\Finder;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;

class OperationIdAdder extends NodeVisitorAbstract
{
    private $printer;
    private $modified = false;

    public function __construct()
    {
        $this->printer = new Standard();
    }

    public function enterNode($node)
    {
        if ($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            $docComment = $node->getDocComment();
            if ($docComment) {
                $docText = $docComment->getText();
                
                if (preg_match('/@OA\\\\(Get|Post|Put|Delete|Patch)\s*\(\s*path\s*=\s*"([^"]+)"/', $docText, $matches)) {
                    $method = strtolower($matches[1]);
                    $path = $matches[2];
                    
                    // Проверяем, есть ли уже operationId
                    if (!preg_match('/operationId\s*=\s*"([^"]+)"/', $docText)) {
                        $operationId = $method . '_' . str_replace(['/', '{', '}'], '_', trim($path, '/'));
                        
                        // Сохраняем отступы и форматирование
                        $newDocText = preg_replace(
                            '/(@OA\\\\' . $matches[1] . '\s*\(\s*path\s*=\s*"[^"]+")/',
                            '$1, operationId="' . $operationId . '"',
                            $docText
                        );
                        
                        $node->setDocComment(new \PhpParser\Comment\Doc($newDocText));
                        $this->modified = true;
                    }
                }
            }
        }
    }

    public function hasModified()
    {
        return $this->modified;
    }
}

$controllersPath = __DIR__ . '/../../../controllers';
echo "Поиск файлов в директории: $controllersPath\n";

if (!is_dir($controllersPath)) {
    die("Ошибка: Директория controllers не найдена!\n");
}

$finder = new Finder();
$finder->files()->in($controllersPath)->name('*.php');

$parserFactory = new ParserFactory();
$parser = $parserFactory->createForNewestSupportedVersion();
$traverser = new NodeTraverser();
$printer = new Standard();

$totalFiles = iterator_count($finder);
echo "Найдено PHP файлов: $totalFiles\n\n";

foreach ($finder as $file) {
    echo "Обработка файла: " . $file->getRelativePathname() . "\n";
    
    try {
        $code = file_get_contents($file->getRealPath());
        $stmts = $parser->parse($code);
        if ($stmts === null) {
            echo "Ошибка парсинга файла: " . $file->getRelativePathname() . "\n";
            continue;
        }

        $adder = new OperationIdAdder();
        $traverser->addVisitor($adder);
        $stmts = $traverser->traverse($stmts);
        $traverser->removeVisitor($adder);

        if ($adder->hasModified()) {
            $newCode = $printer->prettyPrintFile($stmts);
            file_put_contents($file->getRealPath(), $newCode);
            echo "Файл обновлен: " . $file->getRelativePathname() . "\n";
        }
    } catch (\Exception $e) {
        echo "Ошибка при обработке файла " . $file->getRelativePathname() . ": " . $e->getMessage() . "\n";
    }
}

echo "\nОбработка завершена.\n"; 