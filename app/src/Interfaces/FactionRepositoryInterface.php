<?php

namespace App\Interfaces;

interface FactionRepositoryInterface
{
    public function getAll(int $page, int $perPage): array;
    public function getById(int $id): ?array;
    public function create(array $data): array;
    public function update(int $id, array $data): ?array;
    public function delete(int $id): bool;
    public function searchFactions(string $searchTerm, int $page = 1, int $perPage = 10): array;
    public function isFactionNameUnique(string $factionName, ?int $excludeId = null): bool;
}
