<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

/**
 * Cómo leer el CSV del extracto de un banco. Las columnas se identifican por el texto del encabezado
 * (sin importar mayúsculas ni tildes). El valor viene en una columna con signo, o en débito y crédito.
 */
final readonly class StatementFormat
{
    public function __construct(
        public string $delimiter,
        public string $dateFormat,
        public string $decimalSeparator,
        public int $headerRow,
        public string $dateColumn,
        public string $descriptionColumn,
        public ?string $referenceColumn,
        public ?string $amountColumn,
        public ?string $debitColumn,
        public ?string $creditColumn,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        /** @var array<string, string|int> $defaults */
        $defaults = config()->array('travel.finance.statement_defaults');
        $optional = static fn(string $key): ?string => isset($data[$key]) && is_string($data[$key]) && trim($data[$key]) !== '' ? trim($data[$key]) : null;

        return new self(
            delimiter: (string) ($data['delimiter'] ?? $defaults['delimiter']),
            dateFormat: (string) ($data['date_format'] ?? $defaults['date_format']),
            decimalSeparator: (string) ($data['decimal_separator'] ?? $defaults['decimal_separator']),
            headerRow: (int) ($data['header_row'] ?? $defaults['header_row']),
            dateColumn: (string) ($data['date_column'] ?? ''),
            descriptionColumn: (string) ($data['description_column'] ?? ''),
            referenceColumn: $optional('reference_column'),
            amountColumn: $optional('amount_column'),
            debitColumn: $optional('debit_column'),
            creditColumn: $optional('credit_column'),
        );
    }

    /** @return array<string, string|int|null> */
    public function toArray(): array
    {
        return [
            'delimiter' => $this->delimiter,
            'date_format' => $this->dateFormat,
            'decimal_separator' => $this->decimalSeparator,
            'header_row' => $this->headerRow,
            'date_column' => $this->dateColumn,
            'description_column' => $this->descriptionColumn,
            'reference_column' => $this->referenceColumn,
            'amount_column' => $this->amountColumn,
            'debit_column' => $this->debitColumn,
            'credit_column' => $this->creditColumn,
        ];
    }
}
