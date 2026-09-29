<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Modelo operativo mínimo, solo para probar el trait. */
final class ScopedRecord extends Model
{
    use HasVisibilityScope;

    protected $table = 'scoped_records';

    protected $fillable = ['owner_id', 'branch_id'];
}

beforeEach(function (): void {
    Schema::create('scoped_records', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('owner_id');
        $table->unsignedBigInteger('branch_id');
        $table->timestamps();
    });

    $this->branch = Branch::factory()->create();
    $this->otherBranch = Branch::factory()->create();
    $this->owner = User::factory()->for($this->branch)->create();
    $this->colleague = User::factory()->for($this->branch)->create();
    $this->outsider = User::factory()->for($this->otherBranch)->create();

    $this->mine = ScopedRecord::query()->create(['owner_id' => $this->owner->id, 'branch_id' => $this->branch->id]);
    $this->colleagues = ScopedRecord::query()->create(['owner_id' => $this->colleague->id, 'branch_id' => $this->branch->id]);
    $this->foreign = ScopedRecord::query()->create(['owner_id' => $this->outsider->id, 'branch_id' => $this->otherBranch->id]);
});

afterEach(function (): void {
    Schema::dropIfExists('scoped_records');
});

function viewer(User $user, VisibilityScope $scope): User
{
    $user->visibility_scope = $scope;

    return $user;
}

it('lists only the records inside the viewer scope', function (VisibilityScope $scope, array $expected): void {
    $ids = ScopedRecord::query()->visibleTo(viewer($this->owner, $scope))->orderBy('id')->pluck('id')->all();

    expect($ids)->toBe(array_map(fn(string $name): int => $this->{$name}->id, $expected));
})->with([
    'own' => [VisibilityScope::Own, ['mine']],
    'branch' => [VisibilityScope::Branch, ['mine', 'colleagues']],
    'all' => [VisibilityScope::All, ['mine', 'colleagues', 'foreign']],
]);

it('checks a single record against the viewer scope', function (VisibilityScope $scope, string $record, bool $visible): void {
    expect($this->{$record}->isVisibleTo(viewer($this->owner, $scope)))->toBe($visible);
})->with([
    'own sees own' => [VisibilityScope::Own, 'mine', true],
    'own hides colleague' => [VisibilityScope::Own, 'colleagues', false],
    'branch sees colleague' => [VisibilityScope::Branch, 'colleagues', true],
    'branch hides other branch' => [VisibilityScope::Branch, 'foreign', false],
    'all sees other branch' => [VisibilityScope::All, 'foreign', true],
]);

it('hides everything from a branch scoped viewer without branch', function (): void {
    $viewer = viewer(User::factory()->create(['branch_id' => null]), VisibilityScope::Branch);

    expect($this->mine->isVisibleTo($viewer))->toBeFalse()
        ->and(ScopedRecord::query()->visibleTo($viewer)->count())->toBe(0);
});
