<?php

namespace App\Repositories;

interface CharacterRepositoryInterface
{
    public function getAllWithRelations(int $page, int $perPage): array;
    public function getByIdWithRelations(int $id): ?array;
    public function create(array $data): array;
    public function update(int $id, array $data): ?array;
    public function delete(int $id): bool;
}
