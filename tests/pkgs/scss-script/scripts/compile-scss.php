<?php
Civi\CompilePlugin\Util\Script::assertTask();

$scssCompiler = new \ScssPhp\ScssPhp\Compiler();
$scss = 'div { .foo { hyphens: auto; } }';
$css = $scssCompiler->compile($scss);
$autoprefixer = new \Padaliyajay\PHPAutoprefixer\Autoprefixer($css);
file_put_contents("build.css", $autoprefixer->compile());
