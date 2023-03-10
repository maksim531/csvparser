<?php

namespace MyProject\Services;

use MyProject\Domain\BaseCarFactory;
use MyProject\Exceptions\InvalidArgumentException;
use Exception;

class CsvParser implements Parser
{
    public $source;

    public function __construct(CsvDataSource $dataSource)
    {
        $this->source = $dataSource;
    }

    const MAX_STRING_LENGTH = 1000;
    const SEPARATOR = ';';

    private $handle;
    private $result = [];

    private function init($fileName)
    {
        try {
            $handle = @fopen($fileName, 'r');
        } catch (Exception $e) {
            throw new InvalidArgumentException(self::class . ':Файл ' . $fileName . ' не найден');
        }

        if ($handle == false) {
            throw new InvalidArgumentException(self::class . ':Файл ' . $fileName . ' не найден');
        }

        $this->handle = $handle;
    }

    private function parse()
    {
        $row = 0;
        while (($data = fgetcsv($this->handle, self::MAX_STRING_LENGTH, self::SEPARATOR)) !== false) {
            $num = count($data);
            if ($num !== 7) {
                $row++;
                continue;
            }

            for ($c=0; $c < $num; $c++) {
                $this->result[$row][] = $data[$c];
            }

            $row++;
        }
    }

    private function close()
    {
        fclose($this->handle);
    }

    public function getResult($fileName)
    {
        $this->init($fileName);
        $this->parse();

        $this->close();

        return $this->result;
    }

    public function fillCars($data)
    {
        $cars = [];
        foreach ($data as $key => $row) {
            if ($key == 0 && $row[0] === 'car_type') {
                continue;
            } else {
                try {
                    $car = BaseCarFactory::factory($row);
                    $car->fill($this->source, $row);

                    $cars[] = $car;
                } catch (InvalidArgumentException $e) {
                    continue;
                }
            }
        }

        return $cars;
    }
}