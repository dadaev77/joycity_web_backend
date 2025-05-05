<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Symfony\Component\Finder\Finder;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class PathCollector extends NodeVisitorAbstract
{
    public $paths = [];

    public function enterNode($node)
    {
        if ($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            $docComment = $node->getDocComment();
            if ($docComment) {
                $docText = $docComment->getText();
                if (preg_match('/@OA\\\\(Get|Post|Put|Delete|Patch)\s*\(\s*path\s*=\s*"([^"]+)"/', $docText, $matches)) {
                    $method = strtolower($matches[1]);
                    $path = $matches[2];
                    $this->paths[$path][$method] = [
                        'class' => $node->getAttribute('parent')->name->toString(),
                        'method' => $node->name->toString(),
                        'doc' => $docText
                    ];
                }
            }
        }
    }
}

$finder = new Finder();
$finder->files()->in(__DIR__ . '/../../../controllers')->name('*.php');

$parserFactory = new ParserFactory();
$parser = $parserFactory->createForNewestSupportedVersion();
$traverser = new NodeTraverser();
$collector = new PathCollector();
$traverser->addVisitor($collector);

foreach ($finder as $file) {
    $code = file_get_contents($file->getRealPath());
    $stmts = $parser->parse($code);
    $traverser->traverse($stmts);
}

$duplicates = [];
foreach ($collector->paths as $path => $methods) {
    if (count($methods) > 1) {
        $duplicates[$path] = $methods;
    }
}

echo "Найдены следующие дублирующиеся пути:\n\n";
foreach ($duplicates as $path => $methods) {
    echo "Путь: $path\n";
    foreach ($methods as $method => $info) {
        echo "  Метод: $method\n";
        echo "  Класс: {$info['class']}\n";
        echo "  Функция: {$info['method']}\n";
        echo "  Документация:\n{$info['doc']}\n";
    }
    echo "\n";
}

// Генерация скрипта для добавления operationId
echo "\nСкрипт для добавления operationId:\n\n";
foreach ($duplicates as $path => $methods) {
    foreach ($methods as $method => $info) {
        $operationId = strtolower($method) . '_' . 
                      str_replace(['/', '{', '}'], '_', trim($path, '/'));
        echo "// Добавить в {$info['class']}::{$info['method']}\n";
        echo "operationId=\"$operationId\",\n";
    }
} 