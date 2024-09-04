<?php

use App\VerificationResults;
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
        Schema::create('verification_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('CASCADE');
            $table->string('file_type')->default('json');
            $table->enum('result', [VerificationResults::INVALID_ISSUER, VerificationResults::INVALID_RECIPIENT, VerificationResults::INVALID_SIGNATURE, VerificationResults::VERIFIED]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_results');
    }
};
