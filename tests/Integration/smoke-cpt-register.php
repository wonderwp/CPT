<?php
/**
 * Integration smoke: register a throwaway CPT via CustomPostTypeService.
 *
 * Run from ANY WordPress project where wonderwp/customposttype is installed:
 *
 *   wp eval-file vendor/wonderwp/customposttype/tests/Integration/smoke-cpt-register.php
 *   ddev wp eval-file vendor/wonderwp/customposttype/tests/Integration/smoke-cpt-register.php
 */

use WonderWp\Component\CPT\Definition\AbstractCustomPostType;
use WonderWp\Component\CPT\Service\CustomPostTypeService;

if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress is not loaded. Run via WP-CLI from a project that requires wonderwp/customposttype.\n");
    exit(1);
}

$autoloadCandidates = [
    dirname(__DIR__, 4) . '/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php',
];
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

if (!class_exists(CustomPostTypeService::class)) {
    fwrite(STDERR, "wonderwp/customposttype is not autoloadable from this WordPress project.\n");
    exit(1);
}

final class WwpSmokeCustomPostType extends AbstractCustomPostType
{
}

$cptKey = 'wwp_smoke_cpt';
$service = new CustomPostTypeService();
$service->addCustomPostType(new WwpSmokeCustomPostType($cptKey, [
    'public' => false,
    'show_ui' => false,
    'label' => 'WWP Smoke CPT',
]));

$response = $service->registerCustomPostType($service->getCustomPostType($cptKey));

if ($response->getMsgKey() !== 'cpt.registration.success') {
    fwrite(STDERR, "CPT registration failed: " . ($response->getError()?->getMessage() ?? 'unknown') . "\n");
    exit(1);
}

if (!post_type_exists($cptKey)) {
    fwrite(STDERR, "post_type_exists('$cptKey') is false after registration.\n");
    exit(1);
}

fwrite(STDOUT, "OK wonderwp/customposttype Integration smoke-cpt-register ($cptKey)\n");
