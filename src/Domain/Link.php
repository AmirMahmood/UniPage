<?php

namespace UniPage\Domain;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use UniPage\utils\ValidationException;
use UniPage\utils\LinkTypeEnum;

#[Entity, Table(name: 'link')]
final class Link
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[Column(type: 'string', nullable: false)]
    public string $url;

    #[Column(type: 'string', nullable: false)]
    public string $title;

    #[Column(type: 'string', nullable: false)]
    public string $description;

    #[Column(type: 'string', nullable: false, length: 16)]
    public string $type;

    #[Column(type: 'datetimetz_immutable', nullable: true)]
    public DateTimeImmutable $last_modification;

    public function __construct(
        string $url,
        string $title,
        string $type,
        string $description = '',
        ...$args
    ) {
        $this->url = $url;
        $this->title = $title;
        $this->type = $type;
        $this->description = $description;
        $this->last_modification = new DateTimeImmutable('now');
    }

    static private function _get_empty_check_list()
    {
        return array('id', 'url', 'title', 'type');
    }

    public function update(...$args)
    {
        #TODO: check can_update with real columns
        $can_update = array('url', 'title', 'type', 'description');

        foreach ($can_update as $key) {
            if (array_key_exists($key, $args)) {
                $this->$key = $args[$key];
            }
        }

        $this->last_modification = new DateTimeImmutable('now');
    }

    public static function validate_new(array $data)
    {
        $exist_check = array('url', 'title', 'type');
        return Link::_validate($data, $exist_check);
    }

    public static function validate_update(array $data)
    {
        $exist_check = array('id');
        return Link::_validate($data, $exist_check);
    }

    private static function _validate(array $data, array $exist_check)
    {
        foreach ($exist_check as $value) {
            if (!array_key_exists($value, $data)) {
                throw new ValidationException("please enter '{$value}'");
            }
        }

        foreach (Link::_get_empty_check_list() as $value) {
            if (array_key_exists($value, $data) && $data[$value] === '') {
                throw new ValidationException("'{$value}' field can't be empty");
            }
        }

        if (array_key_exists('type', $data) && $data['type'] !== '' && !in_array($data['type'], array_column(LinkTypeEnum::cases(), 'value'))) {
            throw new ValidationException("there is no type '{$data['type']}'");
        }

        return $data;
    }
}
