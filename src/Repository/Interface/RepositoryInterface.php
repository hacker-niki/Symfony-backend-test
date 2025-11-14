<?php

namespace App\Repository\Interface;

use Doctrine\DBAL\LockMode;

interface RepositoryInterface
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, int|null $lockVersion = null): object|null;

    public function findBy(array $criteria, array|null $orderBy = null, int|null $limit = null, int|null $offset = null): array;

    public function findOneBy(array $criteria, array|null $orderBy = null): object|null;
}