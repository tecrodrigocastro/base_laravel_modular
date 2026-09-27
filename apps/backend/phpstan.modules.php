<?php

declare(strict_types=1);

// Auto-includes every packages/{module}/phpstan.neon, so a new module is
// picked up by `make check` without editing this file — see
// .claude/rules/architecture.md in the repo root.
$includes = [];

foreach (glob(__DIR__.'/../../packages/*/phpstan.neon') as $file) {
    if (is_file($file)) {
        $includes[] = $file;
    }
}

return ['includes' => $includes];
