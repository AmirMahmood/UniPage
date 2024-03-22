<?php

namespace UniPage\Domain;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use UniPage\utils\ValidationException;
use UniPage\utils\UserStatusEnum;
use UniPage\utils\UserPositionsEnum;

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
    public string $firstname;

    #[Column(type: 'string', nullable: false, length: 32)]
    public string $lastname;

    #[Column(type: 'string', nullable: false, length: 64)]
    public string $email;

    #[Column(type: 'string', nullable: false, length: 10)]
    public string $end_date;

    #[Column(type: 'string', nullable: false, length: 10)]
    public string $start_date;

    #[Column(type: 'string', nullable: false, length: 16)]
    public string $position;

    #[Column(type: 'string', nullable: false, length: 16)]
    public string $status;

    #[Column(type: 'boolean', nullable: false)]
    public bool $is_admin;

    #[Column(type: 'boolean', nullable: false)]
    public bool $deleted;

    #[Column(type: 'datetimetz_immutable', nullable: true)]
    public DateTimeImmutable $last_login;

    #[Column(type: 'datetimetz_immutable', nullable: true)]
    public DateTimeImmutable $last_modification;

    #[Column(type: 'string', nullable: false)]
    public string $linkedin;

    #[Column(type: 'string', nullable: false)]
    public string $google_scholar;

    #[Column(type: 'string', nullable: false)]
    public string $researchgate;

    #[Column(type: 'string', nullable: false)]
    public string $orcid;

    #[Column(type: 'string', nullable: false)]
    public string $dblp;

    #[Column(type: 'string', nullable: false)]
    public string $website;

    public function __construct(
        string $username,
        string $password,
        string $firstname,
        string $lastname,
        string $start_date,
        string $position,
        string $status,
        int $is_admin = 0,
        string $end_date = '',
        string $email = '',
        string $linkedin = '',
        string $google_scholar = '',
        string $researchgate = '',
        string $orcid = '',
        string $dblp = '',
        string $website = '',
        ...$args
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->position = $position;
        $this->status = $status;
        $this->is_admin = $is_admin;
        $this->last_modification = new DateTimeImmutable('now');
        $this->deleted = 0;
        $this->linkedin = $linkedin;
        $this->google_scholar = $google_scholar;
        $this->researchgate = $researchgate;
        $this->orcid = $orcid;
        $this->dblp = $dblp;
        $this->website = $website;
    }

    static private function _get_empty_check_list()
    {
        return array('id', 'username', 'password', 'firstname', 'lastname', 'start_date', 'position', 'status', 'is_admin');
    }

    public function update(...$args)
    {
        #TODO: check can_update with real columns
        $can_update = array(
            'firstname', 'lastname', 'email', 'end_date', 'start_date', 'position', 'status',
            'is_admin', 'linkedin', 'google_scholar', 'researchgate', 'orcid', 'dblp', 'website'
        );

        foreach ($can_update as $key) {
            if (array_key_exists($key, $args)) {
                $this->$key = $args[$key];
            }
        }

        $this->last_modification = new DateTimeImmutable('now');
    }

    public function update_login_time()
    {
        $this->last_login = new DateTimeImmutable('now');
    }

    public static function validate_new(array $data)
    {
        $exist_check = array(
            'username', 'password', 'firstname', 'lastname', 'start_date', 'position', 'status'
        );
        return User::_validate($data, $exist_check);
    }

    public static function validate_update(array $data)
    {
        $exist_check = array('id');
        return User::_validate($data, $exist_check);
    }

    public static function validate_password(array $data)
    {
        $exist_check = array('id', 'password');
        return User::_validate($data, $exist_check);
    }

    private static function _validate(array $data, array $exist_check)
    {
        foreach ($exist_check as $value) {
            if (!array_key_exists($value, $data)) {
                throw new ValidationException("please enter '{$value}'");
            }
        }

        foreach (User::_get_empty_check_list() as $value) {
            if (array_key_exists($value, $data) && $data[$value] === '') {
                throw new ValidationException("'{$value}' field can't be empty");
            }
        }

        if (array_key_exists('username', $data) && $data['username'] !== '') {
            if (!preg_match("/^[^_.][0-9a-zA-Z_.]+[^_.]$/", $data['username'])) {
                throw new ValidationException("'username' can only contain numbers, english letters, underline and dot");
            }
            $data['username'] = strtolower($data['username']);
        }
        if (array_key_exists('start_date', $data) && $data['start_date'] !== '' && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $data['start_date'])) {
            throw new ValidationException("'start_date' must be in 'yyyy-mm-dd' format");
        }
        if (array_key_exists('end_date', $data) && $data['end_date'] !== '' && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $data['end_date'])) {
            throw new ValidationException("'end_date' must be in 'yyyy-mm-dd' format");
        }
        if (array_key_exists('email', $data) && $data['email'] !== '') {
            if (!preg_match("/^\S+@\S+\.\S+$/", $data['email'])) {
                throw new ValidationException("please enter correct 'email' address");
            }
            $data['email'] = strtolower($data['email']);
        }

        if (array_key_exists('status', $data) && $data['status'] !== '' && !in_array($data['status'], array_column(UserStatusEnum::cases(), 'value'))) {
            throw new ValidationException("there is no status '{$data['status']}'");
        }
        if (array_key_exists('position', $data) && $data['position'] !== '' && !in_array($data['position'], array_column(UserPositionsEnum::cases(), 'value'))) {
            throw new ValidationException("there is no position '{$data['position']}'");
        }

        if (array_key_exists('is_admin', $data)) {
            $data['is_admin'] = (int)$data['is_admin'];
        }
        if (array_key_exists('password', $data)) {
            $data['password'] = hash('sha256', $data['password']);
        }

        return $data;
    }
}
