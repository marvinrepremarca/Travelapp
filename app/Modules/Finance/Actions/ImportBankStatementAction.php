<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\StatementLineStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\AgencyBankAccount;
use App\Modules\Finance\Models\BankStatement;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Finance\Services\StatementCsvParser;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Carga un extracto CSV: el mismo archivo no se carga dos veces y las líneas que ya existen
 * (extractos que se solapan) se omiten. Todo o nada.
 */
final readonly class ImportBankStatementAction
{
    private const HASH = 'sha256';

    public function __construct(private StatementCsvParser $parser) {}

    public function execute(User $actor, AgencyBankAccount $account, string $fileName, string $contents, CarbonImmutable $now): BankStatement
    {
        if (! $account->is_active) {
            throw FinanceRuleViolation::bankAccountInactive();
        }

        $fileHash = hash(self::HASH, $contents);
        if (BankStatement::query()->where('agency_bank_account_id', $account->id)->where('file_hash', $fileHash)->exists()) {
            throw FinanceRuleViolation::statementAlreadyImported();
        }

        $parsed = $this->parser->parse($contents, $account->format(), $account->currency);

        try {
            return DB::transaction(function () use ($actor, $account, $fileName, $fileHash, $parsed, $now): BankStatement {
                $hashes = [];
                $occurrences = [];
                foreach ($parsed as $index => $line) {
                    $fingerprint = $line->fingerprint();
                    $occurrences[$fingerprint] = ($occurrences[$fingerprint] ?? 0) + 1;
                    $hashes[$index] = hash(self::HASH, $fingerprint . '|' . $occurrences[$fingerprint]);
                }

                $existing = BankStatementLine::query()->where('agency_bank_account_id', $account->id)->whereIn('line_hash', $hashes)->pluck('line_hash')->flip();
                $dates = array_map(static fn($line): string => $line->postedOn->toDateString(), $parsed);

                $statement = BankStatement::query()->create([
                    'agency_bank_account_id' => $account->id,
                    'file_name' => $fileName,
                    'file_hash' => $fileHash,
                    'lines_imported' => count($parsed) - $existing->count(),
                    'lines_skipped' => $existing->count(),
                    'first_posted_on' => min($dates),
                    'last_posted_on' => max($dates),
                    'imported_by' => $actor->id,
                    'imported_at' => $now,
                ]);

                foreach ($parsed as $index => $line) {
                    if ($existing->has($hashes[$index])) {
                        continue;
                    }

                    BankStatementLine::query()->create([
                        'bank_statement_id' => $statement->id,
                        'agency_bank_account_id' => $account->id,
                        'posted_on' => $line->postedOn->toDateString(),
                        'description' => $line->description,
                        'reference' => $line->reference,
                        'amount_minor' => $line->amount->getMinorAmount()->toInt(),
                        'currency' => $account->currency,
                        'line_hash' => $hashes[$index],
                        'status' => StatementLineStatus::Pending,
                    ]);
                }

                return $statement;
            });
        } catch (UniqueConstraintViolationException) {
            // Otra carga simultánea del mismo archivo ganó la carrera.
            throw FinanceRuleViolation::statementAlreadyImported();
        }
    }
}
