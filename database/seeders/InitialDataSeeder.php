<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::create([
            'name' => 'Tecnologias de Informacion'
        ]);

        $employee = Employee::create([
            'dni' => 74978113,
            'names' => 'Roberto Ruben',
            'paternal_surname' => 'Chavez',
            'maternal_surname' => 'Vargas',
            'gender' => 'Masculino',
            'phone_number' => 923103948,
            'department_id' => $department->id,
            'is_active' => true
        ]);

        User::create([
            'name' => 'rchavezv7',
            'email' => 'rchavezv7@mda.gob.pe',
            'password' => Hash::make('12345678'),
            'employee_id' => $employee->id,
            'is_active' => true
        ]);
    }
}
