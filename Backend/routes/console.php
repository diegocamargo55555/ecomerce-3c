<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:make-admin {email}', function () {
    $user = User::where('email', $this->argument('email'))->first();
    if (! $user) {
        $this->error('Usuário não encontrado. Cadastre a conta primeiro.');
        return 1;
    }
    $user->is_admin = true;
    $user->save();
    $this->info('Permissão de administrador concedida.');
    return 0;
})->purpose('Promover uma conta existente a administrador');
