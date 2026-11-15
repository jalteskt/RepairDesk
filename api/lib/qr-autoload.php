<?php
declare(strict_types=1);

// Load the bundled QR encoder and its enum dependency.
spl_autoload_register(static function (string $class): void {
    $prefixes = ['BaconQrCode\\' => 'bacon-qr-code/', 'DASPRiD\\Enum\\' => 'enum/'];
    foreach ($prefixes as $prefix => $directory) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $path = __DIR__.'/'.$directory.'src/'.$relative.'.php';
            if (is_file($path)) require $path;
            return;
        }
    }
});
