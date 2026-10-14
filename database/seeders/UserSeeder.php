<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $wakaRole = Role::where('name', 'Waka Kurikulum')->first();
        $guruRole = Role::where('name', 'Guru')->first();
        $waliRole = Role::where('name', 'Wali Kelas')->first();
        $bkRole = Role::where('name', 'Guru BK')->first();
        $siswaRole = Role::where('name', 'Siswa')->first();

        $defaultClassroom = Classroom::first();

        // 0. Super Admin
        $superAdmin = User::firstOrCreate(
            ['username' => 'Muzan'],
            [
                'full_name' => 'Kibutsuji Muzan',
                'identity_number' => '197001011990011001',
                'email' => 'admin@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole?->id,
            ]
        );
        $superAdmin->syncRoles(['Super Admin']);

        // 1. Waka Kurikulum
        $waka = User::firstOrCreate(
            ['username' => 'waka'],
            [
                'full_name' => 'Waka Kurikulum Admin',
                'identity_number' => '197501012000031001',
                'email' => 'waka@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $wakaRole?->id,
            ]
        );
        $waka->syncRoles(['Waka Kurikulum']);

        // 2. Guru
        $guru = User::firstOrCreate(
            ['username' => 'guru1'],
            [
                'full_name' => 'Budi Santoso, S.Kom',
                'identity_number' => '198002022005011002',
                'email' => 'guru1@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $guruRole?->id,
            ]
        );
        $guru->syncRoles(['Guru']);

        // 3. Wali Kelas
        $wali = User::firstOrCreate(
            ['username' => 'walikelas1'],
            [
                'full_name' => 'Siti Aminah, M.Pd',
                'identity_number' => '198503032010012003',
                'email' => 'walikelas1@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $waliRole?->id,
            ]
        );
        $wali->syncRoles(['Wali Kelas']);
        if ($defaultClassroom && ! $wali->classrooms()->where('classrooms.id', $defaultClassroom->id)->exists()) {
            $wali->classrooms()->attach($defaultClassroom->id, ['status' => 'Aktif']);
        }

        // 4. Guru BK
        $gurubk = User::firstOrCreate(
            ['username' => 'gurubk'],
            [
                'full_name' => 'Drs. Hendra Gunawan',
                'identity_number' => '197804042003121004',
                'email' => 'gurubk@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $bkRole?->id,
            ]
        );
        $gurubk->syncRoles(['Guru BK']);

        // 5. Siswa (Portal Siswa & Orang Tua)
        $siswa = User::firstOrCreate(
            ['username' => 'siswa1'],
            [
                'full_name' => 'Ahmad Fauzi',
                'identity_number' => '0061234567',
                'email' => 'siswa1@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $siswaRole?->id,
            ]
        );
        $siswa->syncRoles(['Siswa']);
        if ($defaultClassroom && ! $siswa->classrooms()->where('classrooms.id', $defaultClassroom->id)->exists()) {
            $siswa->classrooms()->attach($defaultClassroom->id, ['status' => 'Aktif']);
        }
    }
}
