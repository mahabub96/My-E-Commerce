<?php

use App\Database\Migration;

/**
 * Migration: add_foreign_keys
 */
class AddForeignKeys extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Add foreign keys for referential integrity
        
        // Products -> Categories
        $this->db->exec("
            ALTER TABLE products 
            ADD CONSTRAINT fk_products_category 
            FOREIGN KEY (category_id) REFERENCES categories(id) 
            ON DELETE SET NULL
        ");

        // Order Items -> Orders
        $this->db->exec("
            ALTER TABLE order_items 
            ADD CONSTRAINT fk_order_items_order 
            FOREIGN KEY (order_id) REFERENCES orders(id) 
            ON DELETE CASCADE
        ");

        // Order Items -> Products
        $this->db->exec("
            ALTER TABLE order_items 
            ADD CONSTRAINT fk_order_items_product 
            FOREIGN KEY (product_id) REFERENCES products(id) 
            ON DELETE RESTRICT
        ");

        // Orders -> Users
        $this->db->exec("
            ALTER TABLE orders 
            ADD CONSTRAINT fk_orders_user 
            FOREIGN KEY (user_id) REFERENCES users(id) 
            ON DELETE CASCADE
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->exec("ALTER TABLE products DROP FOREIGN KEY fk_products_category");
        $this->db->exec("ALTER TABLE order_items DROP FOREIGN KEY fk_order_items_order");
        $this->db->exec("ALTER TABLE order_items DROP FOREIGN KEY fk_order_items_product");
        $this->db->exec("ALTER TABLE orders DROP FOREIGN KEY fk_orders_user");
    }
}
