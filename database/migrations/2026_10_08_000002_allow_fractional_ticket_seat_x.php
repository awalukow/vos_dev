<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // SQLite's numeric affinity already preserves fractional coordinates.
        if (DB::getDriverName()==='mysql') {
            DB::statement('ALTER TABLE ticket_seats MODIFY x DECIMAL(6,2) NOT NULL');
        } elseif (DB::getDriverName()==='pgsql') {
            DB::statement('ALTER TABLE ticket_seats ALTER COLUMN x TYPE DECIMAL(6,2)');
        } elseif (DB::getDriverName()==='sqlsrv') {
            DB::statement('ALTER TABLE ticket_seats ALTER COLUMN x DECIMAL(6,2) NOT NULL');
        }
    }

    public function down(): void {
        // Keep the wider numeric type to avoid truncating saved seat positions.
    }
};
