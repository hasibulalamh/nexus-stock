<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FinishUpazilaSchema extends Migration
{
    public function up(): void
    {
        // Drop unions table if nothing has a FK to it
        if (Schema::hasTable('unions')) {
            if (!Schema::hasColumn('locations', 'union_id')) {
                Schema::dropIfExists('unions');
            }
        }

        // Drop union_id from locations if it exists
        if (Schema::hasColumn('locations', 'union_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('union_id');
            });
        }

        // Rename thanas to upazilas if thanas exists and upazilas does not
        if (Schema::hasTable('thanas') && !Schema::hasTable('upazilas')) {
            Schema::rename('thanas', 'upazilas');
        }

        // Rename thana_id to upazila_id in locations if present
        if (Schema::hasColumn('locations', 'thana_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->renameColumn('thana_id', 'upazila_id');
            });
        }

        // Add nullable string postcode (indexed) to upazilas if missing
        if (Schema::hasTable('upazilas') && !Schema::hasColumn('upazilas', 'postcode')) {
            Schema::table('upazilas', function (Blueprint $table) {
                $table->string('postcode')->nullable()->after('code')->index();
            });
        }
    }

    public function down(): void
    {
        // Remove postcode column from upazilas
        if (Schema::hasTable('upazilas') && Schema::hasColumn('upazilas', 'postcode')) {
            Schema::table('upazilas', function (Blueprint $table) {
                $table->dropColumn('postcode');
            });
        }

        // Rename upazilas back to thanas if upazilas exists and thanas does not
        if (Schema::hasTable('upazilas') && !Schema::hasTable('thanas')) {
            Schema::rename('upazilas', 'thanas');
        }

        // Rename upazila_id back to thana_id in locations if present
        if (Schema::hasColumn('locations', 'upazila_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->renameColumn('upazila_id', 'thana_id');
            });
        }

        // Re-add union_id to locations if it was previously there
        if (!Schema::hasColumn('locations', 'union_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->foreignId('union_id')->after('thana_id')->nullable();
            });
        }
    }
}