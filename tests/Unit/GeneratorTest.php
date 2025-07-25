<?php

use Masyasmv\Adapter\AdapterGenerator;
use PHPUnit\Framework\TestCase;

interface ITest { public function getX(): int; public function setX(int $x); }

class GeneratorTest extends TestCase
{
    public function testGenerateCreatesFile()
    {
        $gen = new AdapterGenerator();
        $out = __DIR__.'/tmp';
        rmdir($out); 
        $gen->generate(ITest::class, $out);

        $this->assertFileExists($out.'/TestAdapter.php');
        $code = file_get_contents($out.'/TestAdapter.php');
        $this->assertStringContainsString('class TestAdapter', $code);
        $this->assertStringContainsString('getX', $code);
        $this->assertStringContainsString('setX', $code);
    }
}