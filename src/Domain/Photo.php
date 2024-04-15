<?php

namespace UniPage\Domain;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use UniPage\utils\ValidationException;

#[Entity, Table(name: 'photo')]
final class Photo
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[Column(type: 'string', nullable: false, length: 32)]
    public string $filename;

    #[Column(type: 'string', nullable: false)]
    public string $description;

    #[Column(type: 'boolean', nullable: false)]
    public bool $slideshow;

    #[Column(type: 'string', nullable: false, length: 10)]
    public string $date;

    #[Column(type: 'datetimetz_immutable', nullable: true)]
    public DateTimeImmutable $last_modification;

    #[Column(type: 'datetimetz_immutable', nullable: true)]
    public DateTimeImmutable $created;

    public function __construct(
        string $filename,
        int $slideshow,
        string $date = '',
        string $description = '',
        ...$args
    ) {
        $this->filename = $filename;
        $this->slideshow = $slideshow;
        $this->date = $date;
        $this->description = $description;

        $this->last_modification = new DateTimeImmutable('now');
        $this->created = new DateTimeImmutable('now');
    }

    static private function _get_empty_check_list()
    {
        return array('id', 'filename', 'slideshow');
    }

    public function update(...$args)
    {
        #TODO: check can_update with real columns
        $can_update = array('slideshow', 'description', 'date');

        foreach ($can_update as $key) {
            if (array_key_exists($key, $args)) {
                $this->$key = $args[$key];
            }
        }

        $this->last_modification = new DateTimeImmutable('now');
    }

    public static function validate_new(array $data)
    {
        $exist_check = array('filename', 'slideshow');
        return Photo::_validate($data, $exist_check);
    }

    public static function validate_update(array $data)
    {
        $exist_check = array('id');
        return Photo::_validate($data, $exist_check);
    }

    private static function _validate(array $data, array $exist_check)
    {
        foreach ($exist_check as $value) {
            if (!array_key_exists($value, $data)) {
                throw new ValidationException("please enter '{$value}'");
            }
        }

        foreach (Photo::_get_empty_check_list() as $value) {
            if (array_key_exists($value, $data) && $data[$value] === '') {
                throw new ValidationException("'{$value}' field can't be empty");
            }
        }

        if (array_key_exists('date', $data) && $data['date'] !== '' && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $data['date'])) {
            throw new ValidationException("'date' must be in 'yyyy-mm-dd' format");
        }

        return $data;
    }
}
