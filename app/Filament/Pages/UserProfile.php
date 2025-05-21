<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;

class UserProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationLabel = 'Mi Perfil';
    
    protected static ?string $title = 'Perfil de Usuario';
    
    protected static ?string $slug = 'perfil';
    
    protected static ?string $navigationGroup = 'Usuario';
    
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.user-profile';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->fillForm();
    }
    
    protected function fillForm(): void
    {
        $user = Auth::user();
        
        $this->data = [
            'name' => $user->name,
            'email' => $user->email,
            'employee_name' => $user->employee ? 
                "{$user->employee->names} {$user->employee->paternal_surname} {$user->employee->maternal_surname}" : 
                'Sin empleado asignado',
            'department' => $user->employee && $user->employee->department ? 
                $user->employee->department->name : 
                'Sin departamento asignado',
        ];
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de perfil')
                    ->description('Información básica de tu cuenta')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de usuario')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->disabled(),
                        Forms\Components\TextInput::make('employee_name')
                            ->label('Nombre completo')
                            ->disabled(),
                        Forms\Components\TextInput::make('department')
                            ->label('Departamento')
                            ->disabled(),
                    ]),
                
                Forms\Components\Section::make('Cambiar contraseña')
                    ->description('Actualiza tu contraseña para mantener tu cuenta segura')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Contraseña actual')
                            ->password()
                            ->required()
                            ->rule('current_password')
                            ->autocomplete('off')
                            ->validationMessages([
                                'current_password' => 'La contraseña actual es incorrecta.',
                                'required' => 'Debes ingresar tu contraseña actual.'
                            ]),
                        Forms\Components\TextInput::make('new_password')
                            ->label('Nueva contraseña')
                            ->password()
                            ->required()
                            ->autocomplete('new-password')
                            ->minLength(8)
                            ->rule(Password::defaults())
                            ->regex('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/')
                            ->validationMessages([
                                'required' => 'Debes ingresar una nueva contraseña.',
                                'min' => 'La contraseña debe tener al menos 8 caracteres.',
                                'regex' => 'La contraseña debe contener al menos una letra, un número y un carácter especial.'
                            ])
                            ->helperText('La contraseña debe tener al menos 8 caracteres, una letra, un número y un carácter especial (@$!%*#?&).'),
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Confirmar nueva contraseña')
                            ->password()
                            ->required()
                            ->same('new_password')
                            ->validationMessages([
                                'required' => 'Debes confirmar tu nueva contraseña.',
                                'same' => 'La confirmación de contraseña no coincide.'
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
    
    public function updatePassword()
    {
        $data = $this->form->getState();
        
        $validated = $this->validate([
            'data.current_password' => ['required', 'current_password'],
            'data.new_password' => [
                'required', 
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/'
            ],
            'data.new_password_confirmation' => ['required', 'same:data.new_password'],
        ], [], [
            'data.current_password' => 'contraseña actual',
            'data.new_password' => 'nueva contraseña',
            'data.new_password_confirmation' => 'confirmación de nueva contraseña',
        ]);
        
        $user = Auth::user();
        $user->password = Hash::make($data['new_password']);
        $user->save();
        
        // Corregir el método reset para limpiar los campos correctamente
        $this->data['current_password'] = '';
        $this->data['new_password'] = '';
        $this->data['new_password_confirmation'] = '';
        
        Notification::make()
            ->title('Contraseña actualizada')
            ->success()
            ->body('Tu contraseña ha sido actualizada exitosamente.')
            ->send();
    }
    
    public function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('updatePassword')
                ->label('Actualizar contraseña')
                ->submit('updatePassword')
                ->color('primary')
        ];
    }
}
