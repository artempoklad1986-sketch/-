<?php
/**
 * АкваСбор - Расширенный редактор товаров v2.0
 * Мощная модалка с загрузкой фото и всеми возможностями
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'data.php';

// Только для авторизованных админов
if (!isset($_SESSION['admin_logged_in'])) {
    die('Access denied');
}

// Обработка AJAX запросов
if ($_POST['action'] ?? false) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'upload_image':
            $result = uploadProductImageAdvanced($_FILES['image'] ?? null);
            echo json_encode($result);
            exit;

        case 'delete_image':
            $result = deleteProductImageAdvanced($_POST['image_path'] ?? '');
            echo json_encode($result);
            exit;

        case 'save_product':
            $result = saveProductAdvanced($_POST, $_FILES ?? []);
            echo json_encode($result);
            exit;

        case 'get_product':
            $productId = (int)($_POST['product_id'] ?? 0);
            $product = $productId ? getProductById($productId) : null;
            echo json_encode(['success' => true, 'product' => $product]);
            exit;
    }
}

// Получаем данные
$productId = (int)($_GET['id'] ?? 0);
$product = $productId ? getProductById($productId) : null;
$categories = getCategories();
$mode = $product ? 'edit' : 'create';

?><!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title><?= $mode === 'edit' ? 'Редактировать' : 'Создать' ?> товар - АкваСбор</title>

    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>

    <style>
        :root {
            --primary: #667eea;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --border: #dee2e6;
            --radius: 8px;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        .editor-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .editor-header {
            background: white;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .editor-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .editor-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-outline { background: transparent; color: var(--primary); border: 2px solid var(--primary); }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .editor-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 24px;
        }

        .main-panel, .side-panel {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .panel-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, #f8f9ff, #fff);
        }

        .panel-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 14px;
            transition: var(--transition);
            font-family: inherit;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* === ЗАГРУЗКА ИЗОБРАЖЕНИЙ === */
        .images-section {
            margin-bottom: 30px;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .image-item {
            position: relative;
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            aspect-ratio: 1;
            overflow: hidden;
            transition: var(--transition);
        }

        .image-item:hover {
            border-color: var(--primary);
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-actions {
            position: absolute;
            top: 8px;
            right: 8px;
            display: flex;
            gap: 4px;
        }

        .image-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .image-btn-delete {
            background: var(--danger);
        }

        .image-btn-main {
            background: var(--success);
        }

        .upload-zone {
            border: 2px dashed var(--primary);
            border-radius: var(--radius);
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: rgba(102, 126, 234, 0.05);
        }

        .upload-zone:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: var(--success);
        }

        .upload-zone.dragover {
            background: rgba(102, 126, 234, 0.15);
            border-color: var(--success);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 16px;
        }

        /* === ТЕГИ И АТРИБУТЫ === */
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--primary);
            color: white;
            border-radius: 20px;
            font-size: 12px;
        }

        .tag-remove {
            cursor: pointer;
            opacity: 0.7;
        }

        .tag-remove:hover {
            opacity: 1;
        }

        /* === SEO СЕКЦИЯ === */
        .seo-section {
            background: #f8f9ff;
            padding: 20px;
            border-radius: var(--radius);
            margin-top: 20px;
        }

        .seo-preview {
            background: white;
            padding: 16px;
            border-radius: var(--radius);
            border-left: 4px solid var(--info);
            margin-top: 16px;
        }

        .seo-title {
            color: #1a0dab;
            font-size: 18px;
            margin-bottom: 4px;
            cursor: pointer;
        }

        .seo-url {
            color: #006621;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .seo-description {
            color: #4d5156;
            font-size: 13px;
            line-height: 1.4;
        }

        /* === СТАТУСЫ И ПЕРЕКЛЮЧАТЕЛИ === */
        .status-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .status-label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        /* === ПРЕДВАРИТЕЛЬНЫЙ ПРОСМОТР === */
        .preview-card {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            max-width: 280px;
        }

        .preview-image {
            width: 100%;
            height: 160px;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--border);
            font-size: 48px;
        }

        .preview-content {
            padding: 16px;
        }

        .preview-name {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .preview-price {
            color: var(--success);
            font-weight: 700;
            font-size: 18px;
        }

        /* === УВЕДОМЛЕНИЯ === */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 20px;
            border-radius: var(--radius);
            color: white;
            z-index: 9999;
            animation: slideInRight 0.3s ease;
        }

        .notification-success { background: var(--success); }
        .notification-error { background: var(--danger); }
        .notification-info { background: var(--info); }

        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        /* === АДАПТИВНОСТЬ === */
        @media (max-width: 768px) {
            .editor-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .editor-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class='editor-container'>
        <!-- Заголовок -->
        <div class='editor-header'>
            <h1 class='editor-title'>
                <i class='fas fa-<?= $mode === 'edit' ? 'edit' : 'plus' ?>'></i>
                <?= $mode === 'edit' ? 'Редактировать товар' : 'Создать новый товар' ?>
                <?php if ($product): ?>
                    <span style='color: var(--primary); font-size: 18px;'>#<?= $product['id'] ?></span>
                <?php endif; ?>
            </h1>

            <div class='editor-actions'>
                <a href='admin.php?section=products' class='btn btn-outline'>
                    <i class='fas fa-arrow-left'></i>
                    Назад к товарам
                </a>
                <button class='btn btn-success' onclick='saveProduct()'>
                    <i class='fas fa-save'></i>
                    <?= $mode === 'edit' ? 'Сохранить изменения' : 'Создать товар' ?>
                </button>
                <?php if ($mode === 'edit'): ?>
                <button class='btn btn-primary' onclick='previewProduct()'>
                    <i class='fas fa-eye'></i>
                    Предварительный просмотр
                </button>
                <?php endif; ?>
            </div>
        </div>

        <form id='productForm'>
            <input type='hidden' name='product_id' value='<?= $product['id'] ?? '' ?>'>

            <div class='editor-grid'>
                <!-- Основная панель -->
                <div class='main-panel'>
                    <!-- Основная информация -->
                    <div class='panel-header'>
                        <h2 class='panel-title'>
                            <i class='fas fa-info-circle'></i>
                            Основная информация
                        </h2>
                    </div>
                    <div class='panel-body'>
                        <div class='form-group'>
                            <label class='form-label'>
                                Название товара *
                                <span style='color: var(--danger);'>обязательно</span>
                            </label>
                            <input type='text' name='name' class='form-input' 
                                   value='<?= htmlspecialchars($product['name'] ?? '') ?>' 
                                   placeholder='Введите название товара...' 
                                   onkeyup='updatePreview()' required>
                        </div>

                        <div class='form-row'>
                            <div class='form-group'>
                                <label class='form-label'>Категория *</label>
                                <select name='category_id' class='form-select' required onchange='updatePreview()'>
                                    <option value=''>Выберите категорию</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value='<?= $category['id'] ?>' 
                                                <?= ($product['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                            <?= $category['icon'] ?> <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class='form-group'>
                                <label class='form-label'>Артикул (SKU)</label>
                                <input type='text' name='sku' class='form-input' 
                                       value='<?= htmlspecialchars($product['sku'] ?? '') ?>' 
                                       placeholder='Автогенерация при сохранении'>
                            </div>
                        </div>

                        <div class='form-group'>
                            <label class='form-label'>Краткое описание</label>
                            <textarea name='short_description' class='form-textarea' rows='3' 
                                      placeholder='Краткое описание товара...'><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                        </div>

                        <div class='form-group'>
                            <label class='form-label'>Полное описание</label>
                            <textarea name='description' class='form-textarea' rows='8' 
                                      placeholder='Подробное описание товара...'><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Изображения -->
                    <div class='panel-header'>
                        <h2 class='panel-title'>
                            <i class='fas fa-images'></i>
                            Изображения товара
                        </h2>
                    </div>
                    <div class='panel-body'>
                        <div class='images-section'>
                            <div class='images-grid' id='imagesGrid'>
                                <?php if (!empty($product['images'])): ?>
                                    <?php foreach ($product['images'] as $index => $image): ?>
                                        <div class='image-item' data-image='<?= htmlspecialchars($image) ?>'>
                                            <img src='<?= htmlspecialchars($image) ?>' alt='Изображение товара'>
                                            <div class='image-actions'>
                                                <?php if ($index === 0): ?>
                                                    <button type='button' class='image-btn image-btn-main' title='Главное фото'>
                                                        <i class='fas fa-star'></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type='button' class='image-btn image-btn-main' title='Сделать главным' onclick='makeMainImage("<?= htmlspecialchars($image) ?>")'>
                                                        <i class='far fa-star'></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type='button' class='image-btn image-btn-delete' title='Удалить' onclick='deleteImage("<?= htmlspecialchars($image) ?>")'>
                                                    <i class='fas fa-trash'></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class='upload-zone' onclick='document.getElementById("imageUpload").click()'>
                                <div class='upload-icon'>
                                    <i class='fas fa-cloud-upload-alt'></i>
                                </div>
                                <h3>Загрузить изображения</h3>
                                <p>Перетащите файлы сюда или нажмите для выбора</p>
                                <p style='font-size: 12px; color: var(--primary); margin-top: 8px;'>
                                    Поддерживаемые форматы: JPG, PNG, WebP, GIF (до 10MB)
                                </p>
                            </div>

                            <input type='file' id='imageUpload' multiple accept='image/*' style='display: none;' onchange='uploadImages(this.files)'>
                        </div>
                    </div>

                    <!-- SEO оптимизация -->
                    <div class='panel-header'>
                        <h2 class='panel-title'>
                            <i class='fas fa-search'></i>
                            SEO оптимизация
                        </h2>
                    </div>
                    <div class='panel-body'>
                        <div class='seo-section'>
                            <div class='form-group'>
                                <label class='form-label'>SEO заголовок</label>
                                <input type='text' name='meta_title' class='form-input' 
                                       value='<?= htmlspecialchars($product['meta_title'] ?? '') ?>' 
                                       placeholder='Оставьте пустым для автогенерации' 
                                       onkeyup='updateSeoPreview()'>
                            </div>

                            <div class='form-group'>
                                <label class='form-label'>META описание</label>
                                <textarea name='meta_description' class='form-textarea' rows='3' 
                                          placeholder='Краткое описание для поисковых систем' 
                                          onkeyup='updateSeoPreview()'><?= htmlspecialchars($product['meta_description'] ?? '') ?></textarea>
                            </div>

                            <div class='form-group'>
                                <label class='form-label'>Ключевые слова</label>
                                <input type='text' name='meta_keywords' class='form-input' 
                                       value='<?= htmlspecialchars($product['meta_keywords'] ?? '') ?>' 
                                       placeholder='аквариум, рыбки, растения'>
                            </div>

                            <div class='seo-preview' id='seoPreview'>
                                <div class='seo-title'><?= htmlspecialchars($product['meta_title'] ?? $product['name'] ?? 'Название товара') ?></div>
                                <div class='seo-url'>https://example.com/product/<?= $product['slug'] ?? 'product-url' ?></div>
                                <div class='seo-description'><?= htmlspecialchars($product['meta_description'] ?? 'Описание товара для поисковых систем...') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Боковая панель -->
                <div class='side-panel'>
                    <!-- Ценообразование -->
                    <div class='panel-header'>
                        <h2 class='panel-title'>
                            <i class='fas fa-ruble-sign'></i>
                            Цены и остатки
                        </h2>
                    </div>
                    <div class='panel-body'>
                        <div class='form-group'>
                            <label class='form-label'>Цена товара (₽) *</label>
                            <input type='number' name='price' class='form-input' 
                                   value='<?= $product['price'] ?? '' ?>' 
                                   placeholder='0.00' min='0' step='0.01' 
                                   onkeyup='updatePreview()' required>
                        </div>

                        <div class='form-group'>
                            <label class='form-label'>Старая цена (₽)</label>
                            <input type='number' name='old_price' class='form-input' 
                                   value='<?= $product['old_price'] ?? '' ?>' 
                                   placeholder='0.00' min='0' step='0.01'>
                        </div>

                        <div class='form-group'>
                            <label class='form-label'>Количество в наличии *</label>
                            <input type='number' name='stock' class='form-input' 
                                   value='<?= $product['stock'] ?? '0' ?>' 
                                   placeholder='0' min='0' required>
                        </div>

                        <div class='form-group'>
                            <label class='form-label'>Вес (граммы)</label>
                            <input type='number' name='weight' class='form-input' 
                                   value='<?= $product['weight'] ?? '100' ?>' 
                                   placeholder='100' min='1'>
                        </div>
                    </div>

                    <!-- Статус и настройки -->
                    <div class='panel-header'>
                        <h2 class='panel-title'>
                            <i class='fas fa-toggle-on'></i>
                            Статус товара
                        </h2>
                    </div>
                    <div class='panel-body'>
                        <div class='status-row'>
                            <label class='status-label'>
                                <div class='toggle-switch'>
                                    <input type='checkbox' name='is_active' <?= ($product['is_active'] ?? true) ? 'checked' : '' ?>>
                                    <span class='slider'></span>
                                </div>
                                Активен (виден на сайте)
                            </label>
                        </div>

                        <div class='status-row'>
                            <label class='status-label'>
                                <div class='toggle-switch'>
                                    <input type='checkbox' name='is_featured' <?= ($product['is_featured'] ?? false) ? 'checked' : '' ?>>
                                    <span class='slider'></span>
                                </div>
                                🔥 Популярный товар
                            </label>
                        </div>

                        <div class='status-row'>
                            <label class='status-label'>
                                <div class='toggle-switch'>
                                    <input type='checkbox' name='is_new' <?= ($product['is_new'] ?? false) ? 'checked' : '' ?>>
                                    <span class='slider'></span>
                                </div>
                                ✨ Новинка
                            </label>
                        </div>
                    </div>

                    <!-- Предварительный просмотр -->
                    <div class='panel-header'>
                        <h2 class='panel-title'>
                            <i class='fas fa-eye'></i>
                            Предварительный просмотр
                        </h2>
                    </div>
                    <div class='panel-body'>
                        <div class='preview-card' id='previewCard'>
                            <div class='preview-image' id='previewImage'>
                                <?php if (!empty($product['images'])): ?>
                                    <img src='<?= htmlspecialchars($product['images'][0]) ?>' alt='Preview' style='width: 100%; height: 100%; object-fit: cover;'>
                                <?php else: ?>
                                    <i class='fas fa-image'></i>
                                <?php endif; ?>
                            </div>
                            <div class='preview-content'>
                                <div class='preview-name' id='previewName'><?= htmlspecialchars($product['name'] ?? 'Название товара') ?></div>
                                <div class='preview-price' id='previewPrice'><?= number_format($product['price'] ?? 0, 0, '', ' ') ?> ₽</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // === ПЕРЕМЕННЫЕ ===
        let uploadedImages = <?= json_encode($product['images'] ?? []) ?>;

        // === ЗАГРУЗКА ИЗОБРАЖЕНИЙ ===

        // Drag & Drop
        const uploadZone = document.querySelector('.upload-zone');

        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            uploadImages(e.dataTransfer.files);
        });

        async function uploadImages(files) {
            if (!files || files.length === 0) return;

            const formData = new FormData();

            for (let file of files) {
                if (!file.type.startsWith('image/')) {
                    showNotification('Можно загружать только изображения!', 'error');
                    continue;
                }

                if (file.size > 10 * 1024 * 1024) {
                    showNotification(`Файл ${file.name} слишком большой (максимум 10MB)`, 'error');
                    continue;
                }

                formData.append('image', file);
                formData.append('action', 'upload_image');

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        uploadedImages.push(result.image_path);
                        addImageToGrid(result.image_path);
                        showNotification('Изображение загружено!', 'success');
                    } else {
                        showNotification(result.message || 'Ошибка загрузки', 'error');
                    }
                } catch (error) {
                    showNotification('Ошибка сети при загрузке', 'error');
                }

                // Очищаем FormData для следующего файла
                formData.delete('image');
            }

            updatePreview();
        }

        function addImageToGrid(imagePath) {
            const grid = document.getElementById('imagesGrid');
            const isFirst = uploadedImages.length === 1;

            const imageDiv = document.createElement('div');
            imageDiv.className = 'image-item';
            imageDiv.setAttribute('data-image', imagePath);

            imageDiv.innerHTML = `
                <img src="${imagePath}" alt="Изображение товара">
                <div class="image-actions">
                    <button type="button" class="image-btn image-btn-main" title="${isFirst ? 'Главное фото' : 'Сделать главным'}" onclick="makeMainImage('${imagePath}')">
                        <i class="fas fa-${isFirst ? 'star' : 'far fa-star'}"></i>
                    </button>
                    <button type="button" class="image-btn image-btn-delete" title="Удалить" onclick="deleteImage('${imagePath}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;

            grid.appendChild(imageDiv);
        }

        async function deleteImage(imagePath) {
            if (!confirm('Удалить это изображение?')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'delete_image');
                formData.append('image_path', imagePath);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Удаляем из массива
                    uploadedImages = uploadedImages.filter(img => img !== imagePath);

                    // Удаляем из DOM
                    const imageItem = document.querySelector(`[data-image="${imagePath}"]`);
                    if (imageItem) imageItem.remove();

                    showNotification('Изображение удалено', 'success');
                    updatePreview();
                } else {
                    showNotification(result.message || 'Ошибка удаления', 'error');
                }
            } catch (error) {
                showNotification('Ошибка сети', 'error');
            }
        }

        function makeMainImage(imagePath) {
            // Перемещаем в начало массива
            uploadedImages = uploadedImages.filter(img => img !== imagePath);
            uploadedImages.unshift(imagePath);

            // Обновляем иконки
            document.querySelectorAll('.image-item').forEach(item => {
                const btn = item.querySelector('.image-btn-main i');
                const isMain = item.getAttribute('data-image') === imagePath;
                btn.className = isMain ? 'fas fa-star' : 'far fa-star';
                item.querySelector('.image-btn-main').title = isMain ? 'Главное фото' : 'Сделать главным';
            });

            updatePreview();
            showNotification('Главное изображение изменено', 'success');
        }

        // === ПРЕДВАРИТЕЛЬНЫЙ ПРОСМОТР ===

        function updatePreview() {
            const name = document.querySelector('[name="name"]').value || 'Название товара';
            const price = document.querySelector('[name="price"]').value || 0;

            document.getElementById('previewName').textContent = name;
            document.getElementById('previewPrice').textContent = new Intl.NumberFormat('ru-RU').format(price) + ' ₽';

            const previewImage = document.getElementById('previewImage');
            if (uploadedImages.length > 0) {
                previewImage.innerHTML = `<img src="${uploadedImages[0]}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">`;
            } else {
                previewImage.innerHTML = '<i class="fas fa-image"></i>';
            }
        }

        function updateSeoPreview() {
            const name = document.querySelector('[name="name"]').value;
            const metaTitle = document.querySelector('[name="meta_title"]').value;
            const metaDescription = document.querySelector('[name="meta_description"]').value;

            document.querySelector('.seo-title').textContent = metaTitle || name || 'Название товара';
            document.querySelector('.seo-description').textContent = metaDescription || 'Описание товара для поисковых систем...';
        }

        // === СОХРАНЕНИЕ ТОВАРА ===

        async function saveProduct() {
            const form = document.getElementById('productForm');
            const formData = new FormData(form);

            // Добавляем массив изображений
            formData.append('images', JSON.stringify(uploadedImages));
            formData.append('action', 'save_product');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message, 'success');

                    // Если создали новый товар, перенаправляем на редактирование
                    if (result.product_id && !document.querySelector('[name="product_id"]').value) {
                        setTimeout(() => {
                            window.location.href = `product_editor_advanced.php?id=${result.product_id}`;
                        }, 1500);
                    }
                } else {
                    showNotification(result.message || 'Ошибка сохранения', 'error');
                }
            } catch (error) {
                showNotification('Ошибка сети при сохранении', 'error');
            }
        }

        function previewProduct() {
            const productId = document.querySelector('[name="product_id"]').value;
            if (productId) {
                window.open(`index.php?page=product&id=${productId}`, '_blank');
            } else {
                showNotification('Сначала сохраните товар', 'info');
            }
        }

        // === УВЕДОМЛЕНИЯ ===

        function showNotification(message, type = 'info') {
            // Удаляем старые уведомления
            document.querySelectorAll('.notification').forEach(n => n.remove());

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${message}
            `;

            document.body.appendChild(notification);

            // Автоматическое скрытие через 4 секунды
            setTimeout(() => {
                notification.style.animation = 'slideInRight 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // === ГОРЯЧИЕ КЛАВИШИ ===

        document.addEventListener('keydown', function(e) {
            // Ctrl + S для сохранения
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                saveProduct();
            }
        });

        // === ИНИЦИАЛИЗАЦИЯ ===

        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
            updateSeoPreview();

            // Автосохранение каждые 30 секунд
            setInterval(() => {
                const name = document.querySelector('[name="name"]').value;
                if (name.trim()) {
                    localStorage.setItem('product_draft_' + (<?= $product['id'] ?? 'new' ?>), JSON.stringify({
                        name: name,
                        description: document.querySelector('[name="description"]').value,
                        price: document.querySelector('[name="price"]').value,
                        timestamp: Date.now()
                    }));
                }
            }, 30000);
        });
    </script>
</body>
</html>

<?php

// === ФУНКЦИИ ДЛЯ ОБРАБОТКИ ИЗОБРАЖЕНИЙ ===

function uploadProductImageAdvanced($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Ошибка загрузки файла'];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Недопустимый тип файла'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Файл слишком большой (максимум 10MB)'];
    }

    // Создаем папку если не существует
    if (!file_exists(PRODUCT_IMAGES_DIR)) {
        mkdir(PRODUCT_IMAGES_DIR, 0755, true);
    }

    // Генерируем уникальное имя файла
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'product_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = PRODUCT_IMAGES_DIR . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'image_path' => $filePath];
    } else {
        return ['success' => false, 'message' => 'Не удалось сохранить файл'];
    }
}

function deleteProductImageAdvanced($imagePath) {
    if (file_exists($imagePath)) {
        if (unlink($imagePath)) {
            return ['success' => true, 'message' => 'Файл удален'];
        } else {
            return ['success' => false, 'message' => 'Не удалось удалить файл'];
        }
    }
    return ['success' => true, 'message' => 'Файл не найден'];
}

function saveProductAdvanced($data, $files = []) {
    try {
        $productId = (int)($data['product_id'] ?? 0);
        $images = json_decode($data['images'] ?? '[]', true) ?: [];

        $productData = [
            'name' => trim($data['name'] ?? ''),
            'category_id' => (int)($data['category_id'] ?? 1),
            'price' => (float)($data['price'] ?? 0),
            'old_price' => !empty($data['old_price']) ? (float)$data['old_price'] : null,
            'stock' => (int)($data['stock'] ?? 0),
            'weight' => (int)($data['weight'] ?? 100),
            'short_description' => trim($data['short_description'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'meta_title' => trim($data['meta_title'] ?? ''),
            'meta_description' => trim($data['meta_description'] ?? ''),
            'meta_keywords' => trim($data['meta_keywords'] ?? ''),
            'is_active' => !empty($data['is_active']),
            'is_featured' => !empty($data['is_featured']),
            'is_new' => !empty($data['is_new']),
            'images' => $images,
            'sku' => trim($data['sku']) ?: null
        ];

        if ($productId) {
            // Обновление существующего товара
            $result = updateProduct($productId, $productData, [], $images);
        } else {
            // Создание нового товара
            $result = createProduct($productData);
        }

        if ($result['success'] && !$productId) {
            $result['product_id'] = $result['product']['id'] ?? null;
        }

        return $result;

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
    }
}

?>
