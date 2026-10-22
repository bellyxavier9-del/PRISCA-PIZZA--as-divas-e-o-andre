<?php
/**
 * Prisca Pizza - API de Cardápio e Produtos
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../bd.php';

$action = $_GET['action'] ?? 'all';

switch ($action) {
    case 'all':
    default:
        // Busca todas as categorias ativas
        $categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY order_index ASC");
        $categories = $categoriesStmt->fetchAll();

        // Busca todos os produtos disponíveis
        $productsStmt = $pdo->query("SELECT p.*, c.slug as category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_available = 1 ORDER BY p.id ASC");
        $allProducts = $productsStmt->fetchAll();

        // Busca opções de customização
        $optionsStmt = $pdo->query("SELECT * FROM product_options WHERE is_active = 1 ORDER BY price_modifier ASC");
        $allOptions = $optionsStmt->fetchAll();

        $sizes = [];
        $crusts = [];
        $extras = [];

        foreach ($allOptions as $opt) {
            if ($opt['type'] === 'tamanho') {
                $sizes[] = $opt;
            } elseif ($opt['type'] === 'borda') {
                $crusts[] = $opt;
            } elseif ($opt['type'] === 'adicional') {
                $extras[] = $opt;
            }
        }

        // Agrupa produtos por categoria
        $result = [];
        foreach ($categories as $cat) {
            $catProducts = array_values(array_filter($allProducts, function($p) use ($cat) {
                return $p['category_id'] == $cat['id'];
            }));

            $result[] = [
                'id' => $cat['id'],
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'description' => $cat['description'],
                'icon' => $cat['icon'],
                'products' => $catProducts
            ];
        }

        echo json_encode([
            'success' => true,
            'categories' => $result,
            'options' => [
                'sizes' => $sizes,
                'crusts' => $crusts,
                'extras' => $extras
            ],
            'store_info' => [
                'opening_hour' => getSetting($pdo, 'opening_hour', '18:00'),
                'closing_hour' => getSetting($pdo, 'closing_hour', '23:30'),
                'delivery_fee_default' => (float)getSetting($pdo, 'delivery_fee_default', 7.00),
                'store_phone' => getSetting($pdo, 'store_phone', '(11) 3456-7890'),
                'store_address' => getSetting($pdo, 'store_address', 'Rua das Pizzas, 100 - Bela Vista, SP')
            ]
        ]);
        break;

    case 'product':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT p.*, c.slug as category_slug, c.name as category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Produto não encontrado.']);
            exit;
        }

        // Se for pizza, busca também as opções de outros sabores para meio a meio
        $otherFlavors = [];
        if ($product['category_slug'] === 'pizzas') {
            $stmtFlavors = $pdo->prepare("SELECT id, name, base_price FROM products WHERE category_id = ? AND id != ? AND is_available = 1");
            $stmtFlavors->execute([$product['category_id'], $id]);
            $otherFlavors = $stmtFlavors->fetchAll();
        }

        echo json_encode([
            'success' => true,
            'product' => $product,
            'other_flavors' => $otherFlavors
        ]);
        break;
}
