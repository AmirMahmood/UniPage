<?php

namespace UniPage\Domain;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use UniPage\utils\ValidationException;
use UniPage\utils\UserStatusEnum;
use UniPage\utils\UserPositionsEnum;

use function DI\value;

#[Entity, Table(name: 'user')]
final class User
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[Column(type: 'string', nullable: false, unique: true, length: 16)]
    public string $username;

    #[Column(type: 'string', nullable: false)]
    public string $password;

    #[Column(type: 'string', nullable: false, length: 32)]
    public string $firstName;

    #[Column(type: 'string', nullable: false, length: 32)]
    public string $lastName;

    #[Column(type: 'string', nullable: false, unique: true, length: 64)]
    public string $email;

    #[Column(type: 'string', nullable: false, length: 10)]
    public string $date;

    #[Column(type: 'string', nullable: false, length: 16)]
    public string $position;

    #[Column(type: 'string', nullable: false, length: 16)]
    public string $status;

    #[Column(type: 'boolean', nullable: false)]
    public bool $is_admin;

    #[Column(type: 'boolean', nullable: false)]
    public bool $deleted;

    public function __construct(
        string $username,
        string $password,
        string $firstName,
        string $lastName,
        string $email,
        string $date,
        string $position,
        string $status,
        bool $is_admin
    ) {
        $this->username = strtolower($username);
        $this->password = hash('sha256', $password);
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = strtolower($email);
        $this->date = $date;
        $this->position = $position;
        $this->status = $status;
        $this->is_admin = $is_admin;

        $this->deleted = false;
    }

    public static function validate_new(array $data)
    {
        $exist_check = array(
            'username', 'password', 'firstName', 'lastName', 'email', 'date', 'position', 'status', 'is_admin'
        );
        $empty_check = array(
            'username', 'password', 'firstName', 'lastName', 'email', 'date', 'position', 'status', 'is_admin'
        );

        User::_validate($data, $exist_check, $empty_check);
    }

    public static function validate_update(array $data)
    {
        $exist_check = array(
            'id', 'firstName', 'lastName', 'email', 'date', 'position', 'status', 'is_admin'
        );
        $empty_check = array(
            'id', 'firstName', 'lastName', 'email', 'date', 'position', 'status', 'is_admin'
        );

        User::_validate($data, $exist_check, $empty_check);
    }

    public static function validate_password(array $data)
    {
        $exist_check = array('id', 'password');
        $empty_check = array('id', 'password');

        User::_validate($data, $exist_check, $empty_check);
    }

    private static function _validate(array $data, array $exist_check, array $empty_check)
    {

        foreach ($exist_check as $value) {
            if (!array_key_exists($value, $data)) {
                throw new ValidationException("please enter '{$value}'");
            }
        }

        foreach ($empty_check as $value) {
            if (array_key_exists($value, $data) && $data[$value] === '') {
                throw new ValidationException("'{$value}' field can't be empty");
            }
        }

        if (array_key_exists('username', $data) && !preg_match("/^[0-9a-zA-Z]+$/", $data['username'])) {
            throw new ValidationException("'username' can only contain numbers and english letters");
        }
        if (array_key_exists('date', $data) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $data['date'])) {
            throw new ValidationException("'date' must be in 'yyyy-mm-dd' format");
        }
        if (array_key_exists('email', $data) && !preg_match("/^\S+@\S+\.\S+$/", $data['email'])) {
            throw new ValidationException("please enter correct 'email' address");
        }

        if (array_key_exists('status', $data) && !in_array($data['status'], array_column(UserStatusEnum::cases(), 'value'))) {
            throw new ValidationException("there is no status '{$data['status']}'");
        }
        if (array_key_exists('position', $data) && !in_array($data['position'], array_column(UserPositionsEnum::cases(), 'value'))) {
            throw new ValidationException("there is no position '{$data['position']}'");
        }
    }
}
