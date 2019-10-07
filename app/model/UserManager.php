<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 8. 5. 2018
 * Time: 22:16
 */
namespace App\Model;
use Nette;
use Nette\Security as NS;

class UserManager implements NS\IAuthenticator
{
    public $database;

    function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

    function authenticate(array $credentials)
    {
        list($username, $password) = $credentials;
        $row = $this->database->table('users')
            ->where('login', $username)->fetch();

        if (!$row) {
            throw new NS\AuthenticationException('User not found.');
        }

        if (md5($password)!= $row->heslo) {
            throw new NS\AuthenticationException('Invalid password.');
        }

        return new NS\Identity($row->id, $row->rola, [
            'meno' => $row->meno,
            'priezvisko' => $row->priezvisko,
            'email' => $row->email,
            'rola' => $row->rola
        ]);
    }
}