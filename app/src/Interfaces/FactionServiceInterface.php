<?php

namespace App\Interfaces;

interface FactionServiceInterface
{
    public function getAllFactions(string $token, int $page = 1, int $perPage = 10, ?string $searchTerm = null): array;
    public function getFactionById(string $token, int $factionId): array;
    public function createFaction(string $token, array $data): array;
    public function updateFaction(string $token, int $factionId, array $data): array;
    public function deleteFaction(string $token, int $factionId): void;
}
