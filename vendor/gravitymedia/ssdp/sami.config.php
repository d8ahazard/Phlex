<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

use Sami\Sami;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/src');

return new Sami($iterator, array(
    'theme' => 'default',
    'title' => 'Ssdp API',
    'build_dir' => __DIR__ . '/docs',
    'cache_dir' => __DIR__ . '/cache',
    'default_opened_level' => 2,
));
