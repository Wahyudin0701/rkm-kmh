<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'photo_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('photo_path')->nullable()->after('role');
            });
        }

        // Sinkronkan foto yang sudah ada dari master Employee ke akun User terkait
        try {
            $employees = Employee::whereNotNull('photo_path')->get();
            foreach ($employees as $emp) {
                if ($emp->user_id) {
                    User::where('id', $emp->user_id)->update(['photo_path' => $emp->photo_path]);
                } elseif (!empty($emp->nik)) {
                    $user = User::where('username', $emp->nik)->first();
                    if ($user) {
                        $user->update(['photo_path' => $emp->photo_path]);
                        $emp->update(['user_id' => $user->id]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore if tables are being set up in test environment
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'photo_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('photo_path');
            });
        }
    }
};
