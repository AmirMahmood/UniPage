<?php

namespace UniPage\Domain;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Processor;
use UniPage\utils\PublicationTypeEnum;
use UniPage\utils\ValidationException;

#[Entity, Table(name: 'publication')]
final class Publication
{
    #[Id, Column(type: 'integer'), GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[Column(type: 'string', nullable: false)]
    public string $url;

    #[Column(type: 'string', nullable: false)]
    public string $title;

    #[Column(type: 'string', nullable: false)]
    public string $published_in;

    #[Column(type: 'string', nullable: false)]
    public string $authors;

    #[Column(type: 'string', nullable: false)]
    public string $citation;

    #[Column(type: 'string', nullable: false, length: 32)]
    public string $type;

    #[Column(type: 'string', nullable: false, length: 4)]
    public string $year;

    #[Column(type: 'datetimetz_immutable', nullable: true)]
    public DateTimeImmutable $last_modification;

    private ?array $_cache_bibtex = null;

    public function __construct(
        string $title,
        string $type,
        string $year,
        string $url = '',
        string $published_in = '',
        string $authors = '',
        string $citation = '',
        ...$args
    ) {
        $this->url = $url;
        $this->title = $title;
        $this->type = $type;
        $this->published_in = $published_in;
        $this->authors = $authors;
        $this->citation = $citation;
        $this->year = $year;
        $this->last_modification = new DateTimeImmutable('now');
    }

    // don't use function name started with `get`. doctrine uses that function to access properties.
    public function gen_title()
    {
        $bibtex = $this->_get_parsed_bibtex();
        if ($bibtex !== null && array_key_exists('title', $bibtex)) {
            return $bibtex['title'];
        }
        return $this->title;
    }

    // don't use function name started with `get`. doctrine uses that function to access properties.
    public function gen_authors()
    {
        $bibtex = $this->_get_parsed_bibtex();
        if ($bibtex !== null && array_key_exists('author', $bibtex)) {
            return $bibtex['author'];
        }
        return $this->authors;
    }

    // don't use function name started with `get`. doctrine uses that function to access properties.
    public function gen_year()
    {
        $bibtex = $this->_get_parsed_bibtex();
        if ($bibtex !== null && array_key_exists('year', $bibtex)) {
            return $bibtex['year'];
        }
        return $this->year;
    }

    // don't use function name started with `get`. doctrine uses that function to access properties.
    public function gen_published_in()
    {
        $bibtex = $this->_get_parsed_bibtex();
        if ($bibtex !== null) {
            if (array_key_exists('booktitle', $bibtex)) {
                return $bibtex['booktitle'];
            }
            if (array_key_exists('journal', $bibtex)) {
                return $bibtex['journal'];
            }
            if (array_key_exists('school', $bibtex)) {
                return $bibtex['school'];
            }
        }
        return $this->published_in;
    }

    private function _get_parsed_bibtex()
    {
        if ($this->_cache_bibtex !== null) {
            return $this->_cache_bibtex;
        }

        if ($this->citation === '') {
            return null;
        }

        $listener = new Listener();
        $listener->addProcessor(new Processor\TagNameCaseProcessor(CASE_LOWER));
        $parser = new Parser();
        $parser->addListener($listener);
        $parser->parseString($this->citation);
        $this->_cache_bibtex = $listener->export()[0];
        return $this->_cache_bibtex;
    }

    static private function _get_empty_check_list()
    {
        return array('id', 'title', 'type', 'year');
    }

    public function update(...$args)
    {
        #TODO: check can_update with real columns
        $can_update = array('url', 'title', 'type', 'published_in', 'authors', 'citation', 'year');

        foreach ($can_update as $key) {
            if (array_key_exists($key, $args)) {
                $this->$key = $args[$key];
            }
        }

        $this->last_modification = new DateTimeImmutable('now');
    }

    public static function validate_new(array $data)
    {
        $exist_check = array('title', 'type', 'year');
        return Publication::_validate($data, $exist_check);
    }

    public static function validate_update(array $data)
    {
        $exist_check = array('id');
        return Publication::_validate($data, $exist_check);
    }

    private static function _validate(array $data, array $exist_check)
    {
        foreach ($exist_check as $value) {
            if (!array_key_exists($value, $data)) {
                throw new ValidationException("please enter '{$value}'");
            }
        }

        foreach (Publication::_get_empty_check_list() as $value) {
            if (array_key_exists($value, $data) && $data[$value] === '') {
                throw new ValidationException("'{$value}' field can't be empty");
            }
        }

        if (array_key_exists('year', $data) && $data['year'] !== '' && !preg_match("/^\d{4}$/", $data['year'])) {
            throw new ValidationException("'year' must be in 'yyyy' format");
        }

        if (array_key_exists('type', $data) && $data['type'] !== '' && !in_array($data['type'], array_column(PublicationTypeEnum::cases(), 'value'))) {
            throw new ValidationException("there is no type '{$data['type']}'");
        }

        return $data;
    }
}
