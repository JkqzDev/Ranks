<?php

declare(strict_types=1);

namespace juqn\ranks\rank;

final class Rank {

    public function __construct(
        private readonly int $id,
        private readonly string $enumName,
        private readonly string $name,
        private readonly string $color,
        private readonly array $permissions,
        private readonly bool $isPrimary
    ) {}

    public function getId(): int {
        return $this->id;
    }

    public function getEnumName(): string {
        return $this->enumName;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getColor(): string {
        return $this->color;
    }

    public function isPrimary(): bool {
        return $this->isPrimary;
    }

    public function getPermissions(): array {
        return $this->permissions;
    }
}