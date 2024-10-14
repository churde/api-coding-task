<?php

namespace App\Repositories;

interface CharacterRepositoryInterface
{
    public function getAllWithRelations(int $page, int $perPage): array;
    public function getByIdWithRelations(int $id): ?array;
    public function create(array $data): array;
    public function update(int $id, array $data): ?array;
    public function delete(int $id): bool;
    public function equipmentExists(int $equipmentId): bool;
    public function factionExists(int $factionId): bool;
    public function searchCharacters(string $searchTerm, int $page = 1, int $perPage = 10): array;
}
