<?php

namespace App\Repositories;

use App\Models\Character;

class CharacterRepository implements CharacterRepositoryInterface
{
    private $character;

    public function __construct(Character $character)
    {
        $this->character = $character;
    }

    public function getAllWithRelations(int $page, int $perPage): array
    {
        return $this->character->getAllCharactersWithRelations($page, $perPage);
    }

    public function getByIdWithRelations(int $id): ?array
    {
        return $this->character->getCharacterWithRelations($id);
    }

    public function create(array $data): array
    {
        return $this->character->createCharacter($data);
    }

    public function update(int $id, array $data): ?array
    {
        return $this->character->updateCharacter($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->character->deleteCharacter($id);
    }
}
