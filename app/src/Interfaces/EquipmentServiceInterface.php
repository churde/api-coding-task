<?php

namespace App\Interfaces;

interface EquipmentServiceInterface
{
    public function getAllEquipment(string $token, int $page = 1, int $perPage = 10, ?string $searchTerm = null): array;
    public function getEquipmentById(string $token, int $equipmentId): array;
    public function createEquipment(string $token, array $data): array;
    public function updateEquipment(string $token, int $equipmentId, array $data): array;
    public function deleteEquipment(string $token, int $equipmentId): void;
}
