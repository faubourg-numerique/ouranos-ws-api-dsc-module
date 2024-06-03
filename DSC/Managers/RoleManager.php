<?php

namespace API\Modules\DSC\Managers;

use API\Managers\EntityManager;
use API\Modules\DSC\Models\Role;

class RoleManager
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(Role $role): void
    {
        $entity = $role->toEntity();
        $this->entityManager->create($entity);
    }

    public function readOne(string $id): Role
    {
        $entity = $this->entityManager->readOne($id);
        $role = new Role();
        $role->fromEntity($entity);
        return $role;
    }

    public function readMultiple(?string $query = null, bool $idAsKey = false): array
    {
        $entities = $this->entityManager->readMultiple(null, Role::TYPE, $query);

        $roles = [];
        foreach ($entities as $entity) {
            $role = new Role();
            $role->fromEntity($entity);
            if ($idAsKey) $roles[$role->id] = $role;
            else $roles[] = $role;
        }

        return $roles;
    }

    public function update(Role $role): void
    {
        $entity = $role->toEntity();
        $this->entityManager->update($entity);
    }

    public function delete(Role $role): void
    {
        $entity = $role->toEntity();
        $this->entityManager->delete($entity);
    }
}
