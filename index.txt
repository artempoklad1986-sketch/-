<?php
require_once 'includes/db.php';

// Получаем популярные товары (случайные активные)
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 1 
    ORDER BY RAND() 
    LIMIT 8
");
$featured_products = $stmt->fetchAll();

// Получаем категории с количеством товаров
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as products_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 1 
    GROUP BY c.id, c.name 
    ORDER BY products_count DESC, c.name
");
$categories = $stmt->fetchAll();

// Получаем общую статистику
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 1");
$total_products = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$total_categories = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌱 Аквариумные растения - Лучший интернет-магазин водных растений</title>
    <meta name="description" content="Большой выбор аквариумных растений ✅ <?= $total_products ?> видов ✅ 9 категорий ✅ Доставка по всей России">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* HEADER */
        .header {
            background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="plant" patternUnits="userSpaceOnUse" width="20" height="20"><circle cx="10" cy="10" r="2" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23plant)"/></svg>');
            opacity: 0.3;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header h1 {
            font-size: 3.5em;
            margin-bottom: 15px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header .subtitle {
            font-size: 1.4em;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .header .stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 30px;
        }

        .header .stat {
            text-align: center;
        }

        .header .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            display: block;
        }

        .header .stat-label {
            opacity: 0.8;
            font-size: 0.9em;
        }

        /* NAVIGATION */
        .nav {
            background: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .nav a {
            color: #2c5530;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 24px;
            border: 2px solid #2c5530;
            border-radius: 30px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav a:hover {
            background: #2c5530;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(44, 85, 48, 0.3);
        }

        /* CATEGORIES SECTION */
        .categories {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5em;
            color: #2c5530;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 50px;
            font-size: 1.1em;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #27ae60, #2c5530);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .category-card:hover::before {
            transform: scaleX(1);
        }

        .category-card .icon {
            font-size: 3em;
            margin-bottom: 15px;
            display: block;
        }

        .category-card a {
            color: #2c5530;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
        }

        .category-card .count {
            color: #27ae60;
            font-weight: bold;
            margin-top: 10px;
            font-size: 0.9em;
        }

        /* FEATURED PRODUCTS */
        .featured {
            padding: 80px 0;
            background: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            padding: 25px;
        }

        .product-name {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c5530;
            line-height: 1.3;
        }

        .product-latin {
            font-style: italic;
            color: #777;
            margin-bottom: 12px;
            font-size: 0.95em;
        }

        .product-category {
            background: linear-gradient(45deg, #e8f5e8, #d4f1d4);
            color: #2c5530;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 15px;
        }

        .product-price {
            font-size: 1.6em;
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 15px;
        }

        .product-difficulty {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
            color: white;
            display: inline-block;
            margin-bottom: 15px;
        }

        .difficulty-easy { background: #27ae60; }
        .difficulty-medium { background: #f39c12; }
        .difficulty-hard { background: #e74c3c; }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(45deg, #27ae60, #2c5530);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }

        /* STATS SECTION */
        .stats-section {
            background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="plants" patternUnits="userSpaceOnUse" width="40" height="40"><circle cx="20" cy="20" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="30" r="2" fill="rgba(255,255,255,0.05)"/><circle cx="30" cy="10" r="2" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23plants)"/></svg>');
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            position: relative;
            z-index: 2;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item .number {
            font-size: 3.5em;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .stat-item .label {
            font-size: 1.1em;
            opacity: 0.9;
        }

        /* CTA SECTION */
        .cta-section {
            padding: 80px 0;
            text-align: center;
            background: #f8f9fa;
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 2.5em;
            color: #2c5530;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .cta-text {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 30px;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-large {
            padding: 15px 35px;
            font-size: 1.1em;
            border-radius: 30px;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #2c5530;
            color: #2c5530;
        }

        .btn-outline:hover {
            background: #2c5530;
            color: white;
        }

        /* FOOTER */
        .footer {
            background: #2c5530;
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }

        .footer-section h3 {
            margin-bottom: 15px;
            color: #a8d5aa;
        }

        .footer-section a {
            color: #ccc;
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #4a7c59;
            padding-top: 20px;
            opacity: 0.8;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2.5em;
            }

            .header .stats {
                flex-direction: column;
                gap: 20px;
            }

            .nav-container {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .section-title {
                font-size: 2em;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .categories-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1>🌱 Аквариумные растения</h1>
                <p class="subtitle">Превратите свой аквариум в подводный сад мечты</p>
                <div class="stats">
                    <div class="stat">
                        <span class="stat-number"><?= $total_products ?>+</span>
                        <span class="stat-label">растений</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?= $total_categories ?></span>
                        <span class="stat-label">категорий</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">⭐⭐⭐⭐⭐</span>
                        <span class="stat-label">качество</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- NAVIGATION -->
    <nav class="nav">
        <div class="container">
            <div class="nav-container">
                <a href="index.php">🏠 Главная</a>
                <a href="products.php">🌿 Все растения (<?= $total_products ?>)</a>
                <a href="#categories">📂 Категории</a>
                <a href="#featured">✨ Рекомендуемые</a>
                <a href="admin/">⚙️ Админка</a>
            </div>
        </div>
    </nav>

    <!-- CATEGORIES SECTION -->
    <section class="categories" id="categories">
        <div class="container">
            <h2 class="section-title">🗂️ Категории растений</h2>
            <p class="section-subtitle">Выберите подходящую категорию для вашего аквариума</p>

            <div class="categories-grid">
                <?php 
                $categoryIcons = [
                    'Плавающие растения' => '🌊',
                    'Эпифиты' => '🪨',
                    'Мхи' => '🍃',
                    'Длинностебельные' => '🌿',
                    'Розеточные' => '🌹',
                    'Почвопокровные' => '🌱',
                    'Клубневые' => '🥔',
                    'Печеночники' => '🍀',
                    'Луковичные' => '🧅'
                ];
                ?>

                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <span class="icon"><?= $categoryIcons[$category['name']] ?? '🌱' ?></span>
                        <a href="category.php?id=<?= $category['id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </a>
                        <div class="count"><?= $category['products_count'] ?> растений</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FEATURED PRODUCTS -->
    <section class="featured" id="featured">
        <div class="container">
            <h2 class="section-title">✨ Рекомендуемые растения</h2>
            <p class="section-subtitle">Лучшие растения для начинающих и опытных аквариумистов</p>

            <?php if ($featured_products): ?>
                <div class="products-grid">
                    <?php foreach ($featured_products as $product): ?>
                        <div class="product-card">
                            <img src="<?= htmlspecialchars($product['main_image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="product-image"
                                 loading="lazy">
                            <div class="product-info">
                                <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="product-latin"><?= htmlspecialchars($product['latin_name']) ?></p>
                                <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                                <div class="product-price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</div>

                                <?php
                                $difficultyClass = 'easy';
                                if (strtolower($product['difficulty']) == 'средне') $difficultyClass = 'medium';
                                elseif (strtolower($product['difficulty']) == 'сложно') $difficultyClass = 'hard';
                                ?>
                                <span class="product-difficulty difficulty-<?= $difficultyClass ?>">
                                    <?= htmlspecialchars($product['difficulty']) ?>
                                </span>

                                <br><br>
                                <a href="product.php?slug=<?= $product['slug'] ?>" class="btn">
                                    📖 Подробнее
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <h3>Товары временно недоступны</h3>
                    <p>Мы работаем над пополнением каталога</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- STATS SECTION -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="number"><?= $total_products ?>+</div>
                    <div class="label">Видов растений в каталоге</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?= $total_categories ?></div>
                    <div class="label">Категорий для выбора</div>
                </div>
                <div class="stat-item">
                    <div class="number">100%</div>
                    <div class="label">Гарантия качества</div>
                </div>
                <div class="stat-item">
                    <div class="number">24/7</div>
                    <div class="label">Поддержка клиентов</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Готовы создать аквариум мечты?</h2>
                <p class="cta-text">Откройте для себя мир подводного садоводства с нашими качественными растениями</p>
                <div class="cta-buttons">
                    <a href="products.php" class="btn btn-large">🌿 Смотреть все растения</a>
                    <a href="#categories" class="btn btn-outline btn-large">📂 Выбрать категорию</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>🌱 Аквариумные растения</h3>
                    <p>Лучший выбор водных растений для вашего аквариума. Качество, красота, здоровье ваших рыбок.</p>
                </div>

                <div class="footer-section">
                    <h3>📂 Категории</h3>
                    <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                        <a href="category.php?id=<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="footer-section">
                    <h3>🔗 Полезные ссылки</h3>
                    <a href="products.php">Все растения</a>
                    <a href="admin/">Админ панель</a>
                    <a href="#categories">Категории</a>
                    <a href="#featured">Рекомендуемые</a>
                </div>

                <div class="footer-section">
                    <h3>📞 Контакты</h3>
                    <p>📧 info@aquaplants.ru</p>
                    <p>📱 +7 (999) 123-45-67</p>
                    <p>🕒 Пн-Вс: 9:00-21:00</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Аквариумные растения. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
        // Плавная прокрутка к якорям
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Анимация счетчиков при скролле
        const animateCounters = () => {
            const counters = document.querySelectorAll('.stat-item .number');
            const options = {
                threshold: 0.7
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counter = entry.target;
                        const finalValue = counter.textContent;

                        if (finalValue.includes('+') || finalValue.includes('%')) {
                            // Для чисел с символами
                            counter.style.transform = 'scale(1.1)';
                            setTimeout(() => {
                                counter.style.transform = 'scale(1)';
                            }, 300);
                        }
                    }
                });
            }, options);

            counters.forEach(counter => observer.observe(counter));
        };

        // Запуск анимации после загрузки страницы
        window.addEventListener('load', animateCounters);
    </script>
</body>
</html>
