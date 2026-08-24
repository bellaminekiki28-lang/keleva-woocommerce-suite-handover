<?php
declare(strict_types=1);

if ($argc !== 2 || '' === trim($argv[1])) {
    fwrite(STDERR, "Usage : php generate-password-hash.php <mot-de-passe>\n");
    exit(1);
}

echo password_hash($argv[1], PASSWORD_DEFAULT) . PHP_EOL;
