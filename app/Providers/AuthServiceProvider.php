<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;
use App\Models\Document;
use App\Models\Employee;
use App\Models\ChargeBook;
use App\Models\Department;
use App\Models\Derivation;
use App\Policies\UserPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\ChargeBookPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\DerivationPolicy;
use App\Policies\RolePolicy;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Document::class => DocumentPolicy::class,
        Employee::class => EmployeePolicy::class,
        ChargeBook::class => ChargeBookPolicy::class,
        Department::class => DepartmentPolicy::class,
        Derivation::class => DerivationPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
