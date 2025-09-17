<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing user profile columns that are expected by the frontend:
     * - location: User's location/address
     * - profile_photo_url: URL to profile photo (alias for avatar)
     * - date_of_birth: Alternative name for birth_date
     * - blood_group: Alternative name for blood_type
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add location column for user's address/location
            $table->string('location')->nullable()->after('bio');

            // Add profile_photo_url as an alias/alternative to avatar
            $table->string('profile_photo_url')->nullable()->after('avatar');

            // Add date_of_birth as an alias/alternative to birth_date
            $table->date('date_of_birth')->nullable()->after('birth_date');

            // Add blood_group as an alias/alternative to blood_type
            $table->string('blood_group')->nullable()->after('blood_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'location',
                'profile_photo_url',
                'date_of_birth',
                'blood_group'
            ]);
        });
    }
};