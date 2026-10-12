<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('icon_class')->nullable()->after('guard_name');
            $table->string('dashboard_route')->nullable()->after('icon_class');
            $table->foreignId('parent_id')->nullable()->after('dashboard_route');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('parent_id');
           
         
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['icon_class', 'dashboard_route', 'parent_id', 'status']);
            $table->id()->startingValue(1);
        });
    }
};
