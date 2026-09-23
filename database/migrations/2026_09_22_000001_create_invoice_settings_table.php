<?php

declare(strict_types=1);

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
        Schema::create('invoice_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->unique();
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('seller_name', 255)->nullable();
            $table->string('seller_vatin', 255)->nullable();
            $table->string('seller_address_line_1', 255)->nullable();
            $table->string('seller_address_line_2', 255)->nullable();
            $table->string('seller_address_line_3', 255)->nullable();
            $table->string('seller_address_post_code', 255)->nullable();
            $table->string('seller_address_city', 255)->nullable();
            $table->string('seller_address_country', 255)->nullable();
            $table->string('seller_phone', 255)->nullable();
            $table->string('seller_email', 255)->nullable();
            $table->text('footer_default')->nullable();
            $table->text('notes_default')->nullable();
            $table->unsignedInteger('tax_rate_default')->nullable();
            $table->string('invoice_number_prefix', 20)->nullable();
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};
