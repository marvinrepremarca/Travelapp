<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Actions\AddDepartureAction;
use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Crm\Enums\ConsentChannel;
use App\Modules\Crm\Enums\ConsentPurpose;
use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\Traveler;
use App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\AgencyProfile;
use App\Modules\Organization\Models\Branch;
use App\Modules\Organization\Services\NitCheckDigit;
use App\Modules\Pricing\Database\Seeders\TaxReferenceSeeder;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use App\Modules\Workflow\Enums\TaskPriority;
use App\Modules\Workflow\Enums\TaskStatus;
use App\Modules\Workflow\Models\ApprovalRequest;
use App\Modules\Workflow\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Datos de demostración para pruebas manuales. Nunca se ejecuta fuera de local/testing.
 * Todos los usuarios usan la contraseña de config('travel.demo.password').
 */
final class DemoSeeder extends Seeder
{
    private const DEMO_NIT = '900123456';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoSeeder solo se ejecuta en local o testing.');
        }

        $this->call([RolesAndPermissionsSeeder::class, TaxReferenceSeeder::class]);

        AgencyProfile::query()->firstOrCreate([], [
            'legal_name' => 'Viajes Demo S.A.S.',
            'trade_name' => 'Viajes Demo',
            'nit' => self::DEMO_NIT,
            'nit_check_digit' => NitCheckDigit::for(self::DEMO_NIT),
            'rnt_number' => '12345',
            'rnt_expires_on' => now()->addDays(30)->toDateString(),
            'address' => 'Calle 10 # 5-20',
            'city' => 'Bogotá',
            'phone' => '6016000000',
            'email' => 'contacto@viajesdemo.test',
        ]);

        $bogota = $this->branch('BOG-01', 'Bogotá Centro', 'Bogotá');
        $medellin = $this->branch('MDE-01', 'Medellín Poblado', 'Medellín');

        $owner = $this->user('gerente@viajesdemo.test', 'Gabriela Gerente', Role::AgencyOwner, $bogota);
        $admin = $this->user('admin@viajesdemo.test', 'Samuel Sistemas', Role::SystemAdmin, $bogota);
        $finance = $this->user('finanzas@viajesdemo.test', 'Fernanda Finanzas', Role::Finance, $bogota);
        $managerBog = $this->user('director.bogota@viajesdemo.test', 'Diego Director', Role::BranchManager, $bogota);
        $this->user('director.medellin@viajesdemo.test', 'Daniela Directora', Role::BranchManager, $medellin);
        $agentBog = $this->user('asesor.bogota@viajesdemo.test', 'Andrés Asesor', Role::TravelAgent, $bogota);
        $agentMde = $this->user('asesor.medellin@viajesdemo.test', 'Ana Asesora', Role::TravelAgent, $medellin);
        $this->user('operaciones@viajesdemo.test', 'Óscar Operaciones', Role::Operations, $bogota);

        $bogota->manager_id = $managerBog->id;
        $bogota->save();

        $this->task($agentBog, $managerBog, 'Confirmar hotel del grupo Pérez', TaskPriority::High, now()->addDay());
        $this->task($agentBog, $agentBog, 'Llamar a cliente por cotización a Cartagena', TaskPriority::Normal, now()->subDay());
        $this->task($agentMde, $owner, 'Renovar convenio con operador de tours', TaskPriority::Urgent, now()->addDays(3));

        $this->approval($agentBog, ApprovalType::Discount, $bogota, 'Descuento del 12 % en paquete a San Andrés', 'Cliente frecuente');
        $this->approval($agentMde, ApprovalType::Discount, $medellin, 'Descuento del 8 % en tour a Guatapé', 'Grupo de 10 personas');
        $this->approval($agentBog, ApprovalType::Refund, $bogota, 'Reembolso por cancelación de tour', 'Cancelado por el proveedor');

        $this->crm($agentBog);
        $this->suppliers();
        $this->catalog();

        unset($admin, $finance);
    }

    private function suppliers(): void
    {
        if (Supplier::query()->exists()) {
            return;
        }

        Supplier::factory()->create(['trade_name' => 'Hotel Caribe Real', 'legal_name' => 'Hotel Caribe Real S.A.S.']);
        Supplier::factory()->rntExpiringIn(10)->create(['trade_name' => 'Tours Ciudad Amurallada', 'legal_name' => 'Tours CA S.A.S.']);
        Supplier::factory()->rntExpiringIn(-5)->create(['trade_name' => 'Transportes Sabana', 'legal_name' => 'Transportes Sabana Ltda.']);
        Supplier::factory()->foreign()->create(['trade_name' => 'Global Hotels', 'legal_name' => 'Global Hotels Inc.']);
    }

    /** Producto propio con temporadas y salidas para probar cupos y tarifas por edad. */
    private function catalog(): void
    {
        if (CatalogProduct::query()->exists()) {
            return;
        }

        $operator = Supplier::query()->where('trade_name', 'Tours Ciudad Amurallada')->first();
        $today = CarbonImmutable::today();
        $seasons = app(AddSeasonAction::class);
        $departures = app(AddDepartureAction::class);

        $rosario = CatalogProduct::factory()->create(['code' => 'CTG-ROSARIO', 'name' => 'Pasadía Islas del Rosario', 'product_type' => ProductType::DayTrip, 'supplier_id' => $operator?->id]);
        $seasons->execute($rosario, 'Temporada media', $today->startOfYear(), $today->addMonths(2)->endOfMonth(), ['adult' => 18000000, 'child' => 12000000, 'infant' => 0]);
        $seasons->execute($rosario, 'Temporada alta', $today->addMonths(3)->startOfMonth(), $today->addMonths(4)->endOfMonth(), ['adult' => 24000000, 'child' => 16000000, 'infant' => 0]);
        $departures->execute($rosario, $today->addDays(3), '08:00', 30);
        $departures->execute($rosario, $today->addDays(4), '08:00', 2);

        $city = CatalogProduct::factory()->create(['code' => 'CTG-CITY', 'name' => 'City tour Cartagena', 'product_type' => ProductType::Tour]);
        $seasons->execute($city, 'Todo el año', $today->startOfYear(), $today->endOfYear()->addYear(), ['adult' => 9000000, 'child' => 6000000]);
        $departures->execute($city, $today->addDays(2), '15:00', 20);

        CatalogProduct::factory()->create(['code' => 'CTG-AIRPORT', 'name' => 'Traslado aeropuerto - hotel', 'product_type' => ProductType::Transfer, 'duration_minutes' => 30]);
    }

    private function crm(User $agent): void
    {
        if (Customer::query()->exists()) {
            return;
        }

        $customer = Customer::factory()->ownedBy($agent)->create([
            'first_name' => 'Laura', 'last_name' => 'Pérez', 'display_name' => 'Laura Pérez',
            'document_number' => '52123456', 'email' => 'laura.perez@correo.test',
        ]);
        $customer->consents()->create([
            'purpose' => ConsentPurpose::DataProcessing, 'granted' => true, 'channel' => ConsentChannel::InPerson,
            'policy_version' => config()->string('travel.privacy.policy_version'), 'recorded_by' => $agent->id, 'recorded_at' => now(),
        ]);
        Traveler::factory()->for($customer)->create(['first_name' => 'Laura', 'last_name' => 'Pérez']);
        Traveler::factory()->for($customer)->child(8)->create(['first_name' => 'Tomás', 'last_name' => 'Pérez', 'passport_expires_on' => now()->addMonths(3)]);

        Lead::factory()->ownedBy($agent)->create(['contact_name' => 'Familia Gómez', 'destination' => 'Cartagena']);
        Lead::factory()->ownedBy($agent)->inStatus(LeadStatus::Quoted)->create(['contact_name' => 'Carlos Ruiz', 'destination' => 'Madrid']);
    }

    private function branch(string $code, string $name, string $city): Branch
    {
        return Branch::query()->firstOrCreate(['code' => $code], [
            'name' => $name,
            'city' => $city,
            'timezone' => config('travel.agency.timezone'),
            'is_active' => true,
        ]);
    }

    private function user(string $email, string $name, Role $role, Branch $branch): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User(['email' => $email]);
        $user->fill(['name' => $name, 'password' => config()->string('travel.demo.password')]);
        $user->visibility_scope = $role->defaultScope();
        $user->branch_id = $branch->id;
        $user->is_active = true;
        $user->email_verified_at = now()->toImmutable();
        $user->save();
        $user->syncRoles([$role->value]);

        return $user;
    }

    private function task(User $assignee, User $creator, string $title, TaskPriority $priority, \DateTimeInterface $dueAt): void
    {
        if (Task::query()->where('title', $title)->exists()) {
            return;
        }

        $task = new Task(['title' => $title, 'priority' => $priority, 'due_at' => $dueAt]);
        $task->status = TaskStatus::Open;
        $task->owner_id = $assignee->id;
        $task->branch_id = $assignee->branch_id;
        $task->created_by = $creator->id;
        $task->save();
    }

    private function approval(User $requester, ApprovalType $type, Branch $subject, string $summary, string $justification): void
    {
        if (ApprovalRequest::query()->where('summary', $summary)->exists()) {
            return;
        }

        $approval = new ApprovalRequest([
            'type' => $type,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => (string) $subject->id,
            'summary' => $summary,
            'justification' => $justification,
        ]);
        $approval->status = ApprovalStatus::Pending;
        $approval->owner_id = $requester->id;
        $approval->branch_id = $requester->branch_id;
        $approval->save();
    }
}
