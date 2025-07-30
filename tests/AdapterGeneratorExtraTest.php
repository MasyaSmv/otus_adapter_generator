<?php

namespace Tests;

use Masyasmv\Adapter\AdapterGenerator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AdapterGeneratorExtraTest extends TestCase
{
    private AdapterGenerator $gen;
    private string $out;

    protected function setUp(): void
    {
        $this->gen = new AdapterGenerator();
        $this->out = sys_get_temp_dir() . '/adapter_test_' . uniqid('', true);
        // на всякий случай удалим старую папку
        if (is_dir($this->out)) {
            $this->rrmdir($this->out);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->out)) {
            $this->rrmdir($this->out);
        }
    }

    private function rrmdir(string $dir): void
    {
        foreach (array_diff(scandir($dir), ['.','..']) as $f) {
            $path = "$dir/$f";
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testThrowsWhenInterfaceNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->gen->generate('Non\\Existing\\Iface', $this->out);
    }

    public function testGeneratesGeneralMethod(): void
    {
        // динамически объявляем интерфейс
        eval('namespace MyApp; interface IGeneral { public function doAction(string $msg); }');

        $this->gen->generate('MyApp\\IGeneral', $this->out);

        $file = $this->out . '/GeneralAdapter.php';
        $this->assertFileExists($file, "Ожидаем, что файл адаптера GeneralAdapter.php погенерится");

        $code = file_get_contents($file);
        // есть сигнатура метода
        $this->assertStringContainsString('public function doAction(string $msg): void', $code);
        // проверяем, что используется IoC::resolve в ветке else
        $this->assertStringContainsString('IoC::resolve(', $code);
    }

    public function testGeneratesClassReturnType(): void
    {
        // интерфейс с возвращаемым классом
        eval('namespace MyApp; interface IDateProvider { public function getNow(): \DateTime; }');

        $this->gen->generate('MyApp\\IDateProvider', $this->out);

        $file = $this->out . '/DateProviderAdapter.php';
        $this->assertFileExists($file);

        $code = file_get_contents($file);
        // проверяем, что в генерации используется DateTime::class, а не 'int' или 'string'
        $this->assertStringContainsString('DateTime::class', $code);
    }

    public function testOutputDirectoryIsCreatedAutomatically(): void
    {
        // объявляем минимальный интерфейс
        eval('namespace Foo; interface ITest { public function getX(): int; }');

        // папка не существует на старте
        $this->assertDirectoryDoesNotExist($this->out);

        $this->gen->generate('Foo\\ITest', $this->out);

        // после вызова папка появилась
        $this->assertDirectoryExists($this->out);
        // а внутри — файл TestAdapter.php
        $this->assertFileExists($this->out . '/TestAdapter.php');
    }
}
