<?php

declare(strict_types=1);

namespace hcf\command;

abstract class Argument implements ParentCommand {

    /**
     * @param string      $name
     * @param string|null $permission
     * @param array       $aliases
     */
    public function __construct(
        private string $name,
        private ?string $permission = null,
        private array $aliases = []
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getPermission(): ?string {
        return $this->permission;
    }

    /**
     * @return array
     */
    public function getAliases(): array {
        return $this->aliases;
    }
}