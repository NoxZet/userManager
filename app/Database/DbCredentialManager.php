<?php

declare(strict_types=1);

namespace App\Database;

use Nette;

class DbCredentialManager implements Nette\Security\IAuthenticator
{
    private $database;

    private $passwords;

    public function __construct(Nette\Database\Context $database, Nette\Security\Passwords $passwords) {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    public function authenticate(array $credentials): Nette\Security\IIdentity {
        [$name, $pass] = $credentials;

        $row = $this->database->table('users')->where('name', $name)->fetch();

        if (!$row) {
            throw new Nette\Security\AuthenticationException('User not found');
        }
        if (!$this->passwords->verify($pass, $row->pass)) {
            throw new Nette\Security\AuthenticationException('Invalid password');
        }

        return new Nette\Security\Identity($row->id, [], ['username' => $row->name]);
    }
    
    /**
     * Returns number of users in the 'users' table
     * @return int Number of users in the 'users' table
     */
    public function userCount(): int {
        return $this->database->table('users')->count();
    }
    
    /**
     * Returns iterable of users in the 'users' table
     * @return \Nette\Database\IRow[] Iterable of users in the 'users' table
     */
    public function getUsers(): array {
        return $this->database->table('users')->fetchAll();
    }
    
    /**
     * Returns name of the user with the given id
     * @param int $id Id to retrieve a username for
     * @throws App\Database\IdInvalidException If there is no user with the given id
     * @return Name of the user with the given id
     */
    public function getUserName(int $id): string {
        $row = $this->database->table('users')->get($id);
        
        if (!$row) {
            throw new IdInvalidException('User with id '.$id.' not found');
        }
        
        return $row->name;
    }
    
    /**
     * Returns true if table 'users' is empty, false otherwise
     * @return bool True if table 'users' is empty, false otherwise
     */
    public function isEmpty(): bool {
        return $this->userCount() <= 0;
    }
    
    /**
     * Returns true if user with the given id exists, false otherwise
     * @return bool True if user with the given id exists, false otherwise
     */
    public function userExists(string $searchName): bool {
        return $this->database->table('users')->where('name', $searchName)->fetch() ? true : false;
    }
    
    /**
     * Changes credentials of user with given id
     * @param int $id Id of user to change credentials for
     * @param string $name New name, if length is 0, no change occurs
     * @param string $newPass New password, if length is 0, change occurs
     * @throws App\Database\IdInvalidException If there is no user with the given id
     */
    public function changeCredentials(int $id, string $name, string $newPass): void {
        $row = $this->database->table('users')->get($id);
        
        if (!$row) {
            throw new IdInvalidException('User with id '.$id.' not found');
        }
        
        $values = [];
        // Only modify if length of either is non-zero
        // if the name is same as before, don't modify (save unnecessary db access if there is no change)
        if ($name !== $row->name && strlen($name) > 0) {
            $values["name"] = $name;
        }
        if (strlen($newPass) > 0) {
            $values["pass"] = $this->passwords->hash($newPass);
        }
        if (count($values) < 1) {
            return;
        }
        
        $row->update($values);
    }
    
    /**
     * Deletes user with given id
     * @param int $id Id of user to delete
     * @throws App\Database\IdInvalidException If there is no user with the given id
     */
    public function deleteUser(int $id): void {
        $row = $this->database->table('users')->get($id);
        
        if (!$row) {
            throw new IdInvalidException('User with id '.$id.' not found');
        }
        $row->delete();
    }
    
    /**
     * Creates user with given credentials
     * @param string $name Name of the created user
     * @param string $pass Password of the created user
     * @throws App\Database\UserCreateException If the name is already taken
     */
    public function userCreate(string $name, string $pass): void {
        if ($this->userExists($name)) {
            throw new UserCreateException('User with name '.$name.' already exists');
        }
        
        $this->database->table('users')->insert(['name' => $name, 'pass' => $this->passwords->hash($pass)]);
    }
    
}