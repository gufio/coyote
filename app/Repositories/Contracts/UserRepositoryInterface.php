<?php

namespace Coyote\Repositories\Contracts;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * @param $name
     * @param array $userIds
     * @return mixed
     */
    public function lookupName($name, $userIds = []);

    /**
     * Find by user name (case insensitive)
     *
     * @param $name
     * @return mixed
     */
    public function findByName($name);

    /**
     * Find by user email (case insensitive)
     *
     * @param $email
     * @return mixed
     */
    public function findByEmail($email);

    /**
     * @param array $data
     * @return \Coyote\User
     */
    public function newUser(array $data);

    /**
     * Pobiera reputacje usera w procentach (jak i rowniez pozycje usera w rankingu)
     *
     * @param $userId
     * @return null|array
     */
    public function rank($userId);

    /**
     * Podaje liczbe userow ktorzy maja jakakolwiek reputacje w systemie
     *
     * @return int
     */
    public function countUsersWithReputation();
}
