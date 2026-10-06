<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Data\ParsedStatementLine;
use App\Modules\Finance\Data\StatementFormat;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Str;

/**
 * Lee el CSV del extracto según el formato de la cuenta. Falla cerrado: una fila ilegible detiene la carga
 * indicando el número de fila, para que no quede un extracto a medias.
 */
final class StatementCsvParser
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    private const NEGATIVE_PARENTHESES = '/^\((.*)\)$/';

    private const FALLBACK_DECIMAL_SEPARATOR = '.';

    private const NON_NUMERIC = '/[^0-9\-]/';

    /** @return non-empty-list<ParsedStatementLine> */
    public function parse(string $contents, StatementFormat $format, string $currency): array
    {
        $rows = $this->rows($contents, $format->delimiter);
        $headerIndex = $format->headerRow - 1;
        $columns = $this->columns($rows[$headerIndex] ?? [], $format);

        $lines = [];
        foreach (array_slice($rows, $headerIndex + 1, preserve_keys: true) as $index => $row) {
            if ($this->isBlank($row)) {
                continue;
            }

            $lines[] = $this->line($row, $columns, $format, $currency, $index + 1);
        }

        if ($lines === []) {
            throw FinanceRuleViolation::statementEmpty();
        }

        return $lines;
    }

    /** @return list<list<string>> */
    private function rows(string $contents, string $delimiter): array
    {
        $contents = str_starts_with($contents, self::UTF8_BOM) ? substr($contents, strlen(self::UTF8_BOM)) : $contents;
        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $rows = [];
        foreach (preg_split('/\r\n|\n|\r/', $contents) ?: [] as $line) {
            $rows[] = array_map(static fn(?string $cell): string => trim((string) $cell), str_getcsv($line, $delimiter, '"', ''));
        }

        return $rows;
    }

    /**
     * Posición de cada columna configurada dentro del encabezado.
     *
     * @param  list<string>  $header
     * @return array<string, int|null>
     */
    private function columns(array $header, StatementFormat $format): array
    {
        $normalized = array_map(static fn(string $title): string => Str::of($title)->ascii()->lower()->squish()->value(), $header);
        $find = static function (?string $title) use ($normalized): ?int {
            if ($title === null) {
                return null;
            }

            $position = array_search(Str::of($title)->ascii()->lower()->squish()->value(), $normalized, true);

            return is_int($position) ? $position : null;
        };

        $columns = [
            'date' => $find($format->dateColumn),
            'description' => $find($format->descriptionColumn),
            'reference' => $find($format->referenceColumn),
            'amount' => $find($format->amountColumn),
            'debit' => $find($format->debitColumn),
            'credit' => $find($format->creditColumn),
        ];

        $missing = array_filter([
            $format->dateColumn => $columns['date'],
            $format->descriptionColumn => $columns['description'],
        ], static fn(?int $position): bool => $position === null);
        if ($format->referenceColumn !== null && $columns['reference'] === null) {
            $missing[$format->referenceColumn] = null;
        }

        $hasAmount = $columns['amount'] !== null || ($columns['debit'] !== null && $columns['credit'] !== null);
        if ($missing !== [] || ! $hasAmount) {
            throw FinanceRuleViolation::statementColumnsMissing(implode(', ', array_filter([...array_keys($missing), $hasAmount ? null : (string) ($format->amountColumn ?? $format->creditColumn)])));
        }

        return $columns;
    }

    /**
     * @param  list<string>  $row
     * @param  array<string, int|null>  $columns
     */
    private function line(array $row, array $columns, StatementFormat $format, string $currency, int $rowNumber): ParsedStatementLine
    {
        try {
            $date = CarbonImmutable::createFromFormat('!' . $format->dateFormat, $row[(int) $columns['date']] ?? '');
        } catch (InvalidFormatException) {
            $date = null;
        }

        if (! $date instanceof CarbonImmutable) {
            throw FinanceRuleViolation::statementRowInvalid($rowNumber);
        }

        try {
            $amount = $columns['amount'] !== null
                ? $this->money($row[$columns['amount']] ?? '', $format, $currency)
                : $this->money($row[(int) $columns['credit']] ?? '', $format, $currency)->minus($this->money($row[(int) $columns['debit']] ?? '', $format, $currency)->abs());
        } catch (MathException) {
            throw FinanceRuleViolation::statementRowInvalid($rowNumber);
        }

        if ($amount->isZero()) {
            throw FinanceRuleViolation::statementRowInvalid($rowNumber);
        }

        $reference = $columns['reference'] === null ? null : ($row[$columns['reference']] ?? '');

        return new ParsedStatementLine(
            postedOn: $date,
            description: Str::limit($row[(int) $columns['description']] ?? '', ParsedStatementLine::DESCRIPTION_LENGTH, ''),
            reference: $reference === null || $reference === '' ? null : Str::limit($reference, ParsedStatementLine::REFERENCE_LENGTH, ''),
            amount: $amount,
        );
    }

    /** "1.234.567,89", "-1,234.50", "(500)", "$ 300" → Money; vacío = cero. */
    private function money(string $value, StatementFormat $format, string $currency): Money
    {
        $value = trim($value);
        if ($value === '') {
            return Money::zero($currency);
        }

        $negative = preg_match(self::NEGATIVE_PARENTHESES, $value, $match) === 1;
        $value = $negative ? $match[1] : $value;
        $separator = $format->decimalSeparator === '' ? self::FALLBACK_DECIMAL_SEPARATOR : $format->decimalSeparator;
        [$integer, $decimals] = array_pad(explode($separator, $value, 2), 2, '');
        $number = preg_replace(self::NON_NUMERIC, '', $integer) . '.' . preg_replace(self::NON_NUMERIC, '', $decimals);
        $decimal = BigDecimal::of(rtrim($number, '.') === '' || rtrim($number, '.') === '-' ? '0' : rtrim($number, '.'));

        return Money::of($negative ? $decimal->negated() : $decimal, $currency, roundingMode: RoundingMode::HALF_UP);
    }

    /** @param  list<string>  $row */
    private function isBlank(array $row): bool
    {
        return implode('', $row) === '';
    }
}
