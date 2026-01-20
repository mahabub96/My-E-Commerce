<?php

namespace App\Database;

use PDO;

/**
 * Base Migration Class
 * 
 * All migration classes extend this base class
 */
abstract class Migration
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Run the migration
     */
    abstract public function up(): void;

    /**
     * Reverse the migration
     */
    abstract public function down(): void;
}
