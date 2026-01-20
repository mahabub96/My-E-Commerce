<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

function resultRow($name, $ok, $msg = '') {
    $c = $ok ? '✅' : '❌';
    echo "<tr><td><strong>{$name}</strong></td><td>{$c}</td><td>" . htmlspecialchars($msg) . "</td></tr>";
}

?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Model & DB Verification</title>
    <style>body{font-family:Arial,Helvetica,sans-serif;padding:20px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}th{background:#f4f4f4}</style>
</head>
<body>
<h1>Model & Database Verification ✅</h1>
<p>This page runs quick checks against the models and database. All write operations are performed inside transactions and rolled back to avoid changing data.</p>
<table>
<thead><tr><th>check</th><th>ok</th><th>notes</th></tr></thead>
<tbody>
<?php
// DB connection & basic query test (via Product model)
try {
    if (!class_exists(Product::class)) {
        resultRow('Product model exists', false, 'Class App\\Models\\Product not found');
    } else {
        $product = new Product();
        // simple select
        try {
            $rows = $product->all();
            resultRow('DB connection & products->all()', true, count($rows) . ' rows');
        } catch (\Throwable $e) {
            resultRow('DB connection & products->all()', false, $e->getMessage());
        }

        // findBySlug test (uses seeded slug if present)
        try {
            $slug = 'asus-ux430';
            $p = $product->findBySlug($slug);
            resultRow("Product::findBySlug('{$slug}')", (bool)$p, $p ? 'Found: ' . ($p['name'] ?? 'N/A') : 'Not found');
        } catch (\Throwable $e) {
            resultRow("Product::findBySlug()", false, $e->getMessage());
        }

        // getFeatured
        try {
            $feat = $product->getFeatured(3);
            resultRow('Product::getFeatured(3)', is_array($feat), count($feat) . ' rows');
        } catch (\Throwable $e) {
            resultRow('Product::getFeatured(3)', false, $e->getMessage());
        }

        // transactional create/update/delete (rolled back)
        try {
            $product->query('START TRANSACTION');
            $testData = [
                'category_id' => 1,
                'name' => 'VERIFY TEST PRODUCT ' . uniqid(),
                'slug' => 'verify-test-' . uniqid(),
                'description' => 'Temporary test product',
                'price' => 1.00,
                'quantity' => 1,
                'status' => 'active'
            ];
            $newId = $product->create($testData);
            $created = $product->find($newId);
            $ok = (bool)$created;
            resultRow('Product create() inside TX', $ok, $ok ? 'Inserted id: ' . $newId : 'failed');

            // update
            $product->update($newId, ['price' => 2.00]);
            $upd = $product->find($newId);
            resultRow('Product update() inside TX', $upd && $upd['price'] == '2.00', $upd ? 'price=' . ($upd['price'] ?? 'N/A') : 'not found');

            // delete
            $product->delete($newId);
            $afterDel = $product->find($newId);
            // still in same transaction; delete executed but we will rollback at the end, so check that delete() didn't error
            resultRow('Product delete() inside TX', $afterDel === null, 'expected null after delete');

            $product->query('ROLLBACK');
            resultRow('Transaction rollback', true, 'Rolled back test changes');
        } catch (\Throwable $e) {
            try { $product->query('ROLLBACK'); } catch (\Throwable $_) {}
            resultRow('Transactional product tests', false, $e->getMessage());
        }
    }
} catch (\Throwable $e) {
    resultRow('Product model tests', false, $e->getMessage());
}

// Core Model generic tests (using a temporary subclass that points at `products` table)
try {
    // define a tiny test subclass in a temp namespace
    eval('namespace App\\Temp; class TestModel extends \\App\\Core\\Model { protected string $table = "products"; }');
    $tmClass = '\\App\\Temp\\TestModel';
    if (!class_exists($tmClass)) {
        resultRow('Core Model instantiation via TestModel', false, "Class {$tmClass} not available");
    } else {
        $tm = new $tmClass();
        resultRow('Core Model instantiation via TestModel', true);

        // select() basic count
        try {
            $cntRow = $tm->select('COUNT(*) as cnt');
            $cnt = $cntRow[0]['cnt'] ?? null;
            resultRow('Model::select() basic', $cnt !== null, 'count=' . ($cnt ?? 'N/A'));
        } catch (\Throwable $e) {
            resultRow('Model::select() basic', false, $e->getMessage());
        }

        // transactional create/update/delete (rolled back)
        try {
            $tm->query('START TRANSACTION');

            $testData = [
                'category_id' => 1,
                'name' => 'CORE VERIFY ' . uniqid(),
                'slug' => 'core-verify-' . uniqid(),
                'description' => 'Core model create test',
                'price' => 0.50,
                'quantity' => 1,
                'status' => 'active'
            ];

            $newId = $tm->create($testData);
            resultRow('Model::create()', (bool)$newId, $newId ? 'Inserted id: ' . $newId : 'failed');

            $found = $tm->find($newId);
            resultRow('Model::find()', (bool)$found, $found ? ('Found id=' . ($found['id'] ?? 'N/A')) : 'not found');

            $tm->update($newId, ['price' => 1.50]);
            $upd = $tm->find($newId);
            resultRow('Model::update()', $upd && (string)$upd['price'] === '1.50', $upd ? 'price=' . ($upd['price'] ?? 'N/A') : 'not found');

            $tm->delete($newId);
            $afterDel = $tm->find($newId);
            resultRow('Model::delete()', $afterDel === null, 'expected null after delete');

            // query error handling test (expecting exception)
            try {
                $tm->query('THIS IS INVALID SQL');
                resultRow('Model::query() error handling', false, 'expected exception for invalid SQL');
            } catch (\Throwable $qe) {
                resultRow('Model::query() error handling', true, 'caught: ' . $qe->getMessage());
            }

            $tm->query('ROLLBACK');
            resultRow('Core Model transaction rollback', true, 'Rolled back test changes');
        } catch (\Throwable $e) {
            try { $tm->query('ROLLBACK'); } catch (\Throwable $_) {}
            resultRow('Core Model transactional tests', false, $e->getMessage());
        }
    }
} catch (\Throwable $e) {
    resultRow('Core Model tests', false, $e->getMessage());
}

// Check other models: Category, User, Order, OrderItem
$models = [
    'Category' => Category::class,
    'User' => User::class,
    'Order' => Order::class,
    'OrderItem' => OrderItem::class,
];

foreach ($models as $label => $class) {
    if (!class_exists($class)) {
        resultRow("{$label} model exists", false, "Class {$class} not found");
        continue;
    }

    try {
        $m = new $class();
        try {
            $all = $m->all();
            resultRow("{$label}::all()", true, count($all) . ' rows');
        } catch (\Throwable $e) {
            resultRow("{$label}::all()", false, $e->getMessage());
        }
    } catch (\Throwable $e) {
        resultRow("{$label} instantiate", false, $e->getMessage());
    }
}

// Final note
?>
</tbody>
</table>
<p><strong>Notes:</strong> If any model classes are missing or tests fail, I can add minimal model skeletons (safe, non-destructive) and re-run these checks for you. Want me to implement the missing model files now?</p>
</body>
</html>