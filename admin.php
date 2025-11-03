<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>АкваСбор - Аквариумная студия Pro</title>
    <meta name="description" content="Полнофункциональная PWA для аквариумистов с анализом воды, подбором рыб и растений">
    <meta name="theme-color" content="#159895">

    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="АкваСбор">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a5f7a 0%, #159895 100%);
            color: #333;
            min-height: 100vh;
            padding: 10px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        header {
            background: linear-gradient(135deg, #002B36 0%, #004D61 100%);
            color: white;
            padding: 20px 15px;
            text-align: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #57C5B6;
        }

        .tagline {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Навигация */
        .navigation {
            display: flex;
            overflow-x: auto;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .navigation::-webkit-scrollbar {
            display: none;
        }

        .nav-btn {
            flex: 0 0 auto;
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .nav-btn:hover {
            background: #e9ecef;
        }

        .nav-btn.active {
            background: white;
            color: #159895;
            border-bottom-color: #159895;
        }

        /* Секции контента */
        .content-section {
            display: none;
            padding: 20px 15px;
            min-height: 400px;
        }

        .content-section.active {
            display: block;
        }

        h2 {
            color: #002B36;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #57C5B6;
            font-size: 1.4rem;
        }

        h3 {
            color: #004D61;
            margin: 20px 0 12px;
            font-size: 1.2rem;
        }

        h4 {
            color: #004D61;
            margin: 15px 0 10px;
            font-size: 1.1rem;
        }

        /* Модальные окна */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 12px;
            max-width: 90%;
            max-height: 90%;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        /* Фото загрузка */
        .photo-upload {
            margin: 20px 0;
            padding: 20px;
            border: 2px dashed #57C5B6;
            border-radius: 8px;
            text-align: center;
            background: #f8fdff;
        }

        .photo-upload input {
            display: none;
        }

        .upload-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #57C5B6;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            margin: 10px;
            font-weight: 500;
        }

        .upload-btn:hover {
            background: #45a89a;
        }

        /* Галерея фото */
        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .photo-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .photo-item:hover img {
            transform: scale(1.05);
        }

        .photo-delete {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(244, 67, 54, 0.8);
            color: white;
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-delete:hover {
            background: rgba(244, 67, 54, 1);
        }

        /* Заметки */
        .notes-section {
            margin: 20px 0;
        }

        .note-input {
            width: 100%;
            min-height: 100px;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }

        .note-input:focus {
            border-color: #57C5B6;
            outline: none;
        }

        .notes-list {
            margin-top: 15px;
        }

        .note-item {
            background: #f8f9fa;
            border-left: 4px solid #57C5B6;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 0 6px 6px 0;
        }

        .note-date {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }

        .note-text {
            line-height: 1.5;
        }

        /* Тесты воды */
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .test-card {
            background: #f8fdff;
            border: 2px solid #57C5B6;
            border-radius: 8px;
            padding: 20px;
        }

        .test-card h4 {
            color: #004D61;
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .test-input-group {
            margin-bottom: 15px;
        }

        .test-input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #004D61;
        }

        .test-input-group input {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .test-input-group input:focus {
            border-color: #57C5B6;
            outline: none;
        }

        .test-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            line-height: 1.3;
        }

        .test-result {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }

        .test-result.good {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .test-result.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .test-result.danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Списки рыб и растений */
        .species-selector {
            margin: 20px 0;
        }

        .species-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .species-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .species-card:hover {
            border-color: #57C5B6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .species-card.selected {
            border-color: #57C5B6;
            background: #f8fdff;
        }

        .species-card h4 {
            color: #004D61;
            margin-bottom: 8px;
        }

        .species-info {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }

        /* Совместимость */
        .compatibility-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .compatibility-matrix {
            margin: 20px 0;
            overflow-x: auto;
        }

        .compatibility-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 600px;
        }

        .compatibility-table th,
        .compatibility-table td {
            padding: 8px 12px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .compatibility-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #004D61;
            position: sticky;
            top: 0;
        }

        .compat-excellent {
            background: #d4edda;
            color: #155724;
        }

        .compat-good {
            background: #d1ecf1;
            color: #0c5460;
        }

        .compat-caution {
            background: #fff3cd;
            color: #856404;
        }

        .compat-poor {
            background: #f8d7da;
            color: #721c24;
        }

        /* Калькуляторы */
        .calculator-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .calc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .calc-card {
            background: white;
            border: 2px solid #57C5B6;
            border-radius: 8px;
            padding: 20px;
        }

        .calc-input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .calc-input-group {
            margin-bottom: 15px;
        }

        .calc-input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #004D61;
        }

        .calc-input-group input,
        .calc-input-group select {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .calc-input-group input:focus,
        .calc-input-group select:focus {
            border-color: #57C5B6;
            outline: none;
        }

        .calc-result {
            background: #e8f5e8;
            border: 2px solid #4CAF50;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }

        .calc-result h5 {
            color: #2e7d32;
            margin-bottom: 10px;
        }

        /* Азотный цикл */
        .nitrogen-stages {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stage-card {
            background: white;
            border: 2px solid #57C5B6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stage-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #57C5B6;
            color: white;
            border-radius: 50%;
            line-height: 40px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .stage-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Экспертные блоки */
        .expert-advice {
            background: #e8f5e8;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 6px 6px 0;
        }

        .expert-advice h4 {
            color: #2e7d32;
            margin-bottom: 8px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .expert-advice p {
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .expert-source {
            font-size: 12px;
            color: #558b2f;
            font-style: italic;
        }

        /* Кнопки */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            margin: 5px;
        }

        .btn-primary {
            background: #159895;
            color: white;
        }

        .btn-primary:hover {
            background: #128285;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #57C5B6;
            color: white;
        }

        .btn-secondary:hover {
            background: #45a89a;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .btn-success {
            background: #4CAF50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
        }

        .btn-info {
            background: #2196F3;
            color: white;
        }

        .btn-info:hover {
            background: #1976D2;
        }

        /* Toast уведомления */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #323232;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.3);
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
            font-size: 14px;
            max-width: 90%;
        }

        .toast.show {
            opacity: 1;
        }

        /* Прелоадер */
        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1a5f7a 0%, #159895 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
        }

        .preloader-logo {
            font-size: 3rem;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        .preloader-text {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }

        .preloader-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .preloader.hidden {
            opacity: 0;
            pointer-events: none;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .test-grid {
                grid-template-columns: 1fr;
            }

            .species-grid {
                grid-template-columns: 1fr;
            }

            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }

            .navigation {
                padding: 0 10px;
            }

            .nav-btn {
                padding: 10px 15px;
                font-size: 13px;
            }

            .calc-input-row {
                grid-template-columns: 1fr;
            }

            .nitrogen-stages {
                grid-template-columns: 1fr;
            }
        }

        /* Дополнительные стили для контента */
        .info-card {
            background: #f0f8ff;
            border: 1px solid #b0d4f1;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .info-card h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .warning-card {
            background: #fff3e0;
            border: 1px solid #ffb74d;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .warning-card h4 {
            color: #f57c00;
            margin-bottom: 10px;
        }

        .parameter-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }

        .parameter-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #159895;
        }

        .tips-list {
            list-style: none;
            padding: 0;
        }

        .tips-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 25px;
        }

        .tips-list li:before {
            content: "💡";
            position: absolute;
            left: 0;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #57C5B6);
            transition: width 0.3s ease;
        }

    </style>
</head>
<body>
    <!-- Прелоадер -->
    <div id="preloader" class="preloader">
        <div class="preloader-logo">🐠</div>
        <div class="preloader-text">АкваСбор загружается...</div>
        <div class="preloader-spinner"></div>
    </div>

    <div class="container">
        <header>
            <div class="logo">🐠 АкваСбор - Аквариумная студия</div>
            <div class="tagline">Полнофункциональная PWA для профессиональных аквариумистов</div>
            <button id="installBtn" class="btn btn-secondary" style="margin-top: 10px; display: none;">📱 Установить приложение</button>
        </header>

        <!-- Панель подписки -->
        <div id="subscriptionBar" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #333; padding: 10px; text-align: center; font-weight: bold;">
            <span id="subscriptionStatus">Пробная версия</span>
            <button id="upgradeBtn" class="btn btn-warning" style="margin-left: 15px; padding: 5px 15px;">
                💎 Апгрейд до PRO
            </button>
        </div>

        <!-- Навигация -->
        <nav class="navigation">
            <button class="nav-btn active" data-section="my-aquarium">🏠 Мой аквариум</button>
            <button class="nav-btn" data-section="water-analysis">🧪 Анализ воды</button>
            <button class="nav-btn" data-section="fish-compatibility">🐠 Совместимость рыб</button>
            <button class="nav-btn" data-section="plant-compatibility">🌿 Подбор растений</button>
            <button class="nav-btn" data-section="calculators">🧮 Калькуляторы</button>
            <button class="nav-btn" data-section="nitrogen-cycle">🔄 Азотный цикл</button>
            <button class="nav-btn" data-section="aquascaping">🎨 Акваскейпинг</button>
            <button class="nav-btn" data-section="lighting">💡 Освещение</button>
        </nav>

        <!-- Раздел "Мой аквариум" -->
        <section id="my-aquarium" class="content-section active">
            <h2>🏠 Мой аквариум</h2>

            <!-- Фото галерея -->
            <div class="photo-upload">
                <h3>📸 Галерея фотографий</h3>
                <p style="color: #666; margin: 10px 0; font-size: 14px;">Загружайте фото своего аквариума, рыб и растений. Поддерживается drag & drop.</p>
                <input type="file" id="photoInput" accept="image/*" multiple>
                <label for="photoInput" class="upload-btn">📎 Загрузить фото</label>
                <button id="takePhoto" class="upload-btn">📷 Сделать фото</button>
                <div id="photoGallery" class="photo-gallery"></div>
            </div>

            <!-- Заметки -->
            <div class="notes-section">
                <h3>📝 Заметки и наблюдения</h3>
                <p style="color: #666; margin: 10px 0; font-size: 14px;">Ведите дневник своего аквариума: поведение рыб, состояние растений, проводимые работы.</p>
                <textarea id="noteInput" class="note-input" placeholder="Введите ваши наблюдения, изменения в аквариуме, поведение рыб...&#10;&#10;Примеры заметок:&#10;- Подменил 30% воды&#10;- Заметил нерест у гуппи&#10;- Добавил новое растение&#10;- Рыбы стали активнее после смены корма"></textarea>
                <button id="addNote" class="btn btn-primary">💾 Добавить заметку</button>
                <div id="notesList" class="notes-list"></div>
            </div>

            <!-- Расширенные тесты воды -->
            <h3>🧪 Полная панель тестов воды</h3>
            <div class="test-grid">
                <div class="test-card">
                    <h4>📊 Основные параметры</h4>
                    <div class="test-input-group">
                        <label>pH (кислотность)</label>
                        <input type="number" id="test-ph" step="0.1" min="0" max="14" placeholder="6.5-8.0">
                        <div class="test-info">Кислотность воды влияет на токсичность аммиака и усвоение питательных веществ</div>
                    </div>
                    <div class="test-input-group">
                        <label>Температура (°C)</label>
                        <input type="number" id="test-temp" min="0" max="40" placeholder="22-28">
                        <div class="test-info">Температура влияет на метаболизм рыб и растворимость кислорода</div>
                    </div>
                    <div id="basic-result" class="test-result" style="display: none;"></div>
                </div>

                <div class="test-card">
                    <h4>⚠️ Азотистые соединения</h4>
                    <div class="test-input-group">
                        <label>NH₃/NH₄ (Аммиак/Аммоний, мг/л)</label>
                        <input type="number" id="test-ammonia" step="0.01" min="0" placeholder="0">
                        <div class="test-info">Самый опасный параметр! Токсичен для рыб даже в малых количествах</div>
                    </div>
                    <div class="test-input-group">
                        <label>NO₂ (Нитриты, мг/л)</label>
                        <input type="number" id="test-nitrites" step="0.01" min="0" placeholder="0">
                        <div class="test-info">Промежуточный продукт разложения, связывает кислород в крови рыб</div>
                    </div>
                    <div class="test-input-group">
                        <label>NO₃ (Нитраты, мг/л)</label>
                        <input type="number" id="test-nitrates" min="0" placeholder="< 20">
                        <div class="test-info">Конечный продукт азотного цикла, накапливается со временем</div>
                    </div>
                    <div id="nitrogen-result" class="test-result" style="display: none;"></div>
                </div>

                <div class="test-card">
                    <h4>💎 Жесткость воды</h4>
                    <div class="test-input-group">
                        <label>GH (Общая жесткость, °dH)</label>
                        <input type="number" id="test-gh" min="0" placeholder="4-16">
                        <div class="test-info">Содержание солей кальция и магния, влияет на осморегуляцию рыб</div>
                    </div>
                    <div class="test-input-group">
                        <label>KH (Карбонатная жесткость, °dH)</label>
                        <input type="number" id="test-kh" min="0" placeholder="3-10">
                        <div class="test-info">Буферная емкость воды, предотвращает скачки pH</div>
                    </div>
                    <div id="hardness-result" class="test-result" style="display: none;"></div>
                </div>

                <div class="test-card">
                    <h4>🧬 Микроэлементы</h4>
                    <div class="test-input-group">
                        <label>PO₄ (Фосфаты, мг/л)</label>
                        <input type="number" id="test-phosphates" step="0.01" min="0" placeholder="< 0.5">
                        <div class="test-info">Основное питание для водорослей, контролируйте уровень</div>
                    </div>
                    <div class="test-input-group">
                        <label>Fe (Железо, мг/л)</label>
                        <input type="number" id="test-iron" step="0.01" min="0" placeholder="0.1-0.5">
                        <div class="test-info">Необходимо для фотосинтеза растений, быстро окисляется</div>
                    </div>
                    <div class="test-input-group">
                        <label>Cu (Медь, мг/л)</label>
                        <input type="number" id="test-copper" step="0.001" min="0" placeholder="< 0.005">
                        <div class="test-info">Крайне токсична для креветок и других беспозвоночных</div>
                    </div>
                    <div id="micro-result" class="test-result" style="display: none;"></div>
                </div>

                <div class="test-card">
                    <h4>🌊 Дополнительные тесты</h4>
                    <div class="test-input-group">
                        <label>CO₂ (Углекислый газ, мг/л)</label>
                        <input type="number" id="test-co2" step="1" min="0" placeholder="20-30">
                        <div class="test-info">Важен для растений, рассчитывается по pH и KH</div>
                    </div>
                    <div class="test-input-group">
                        <label>O₂ (Кислород, мг/л)</label>
                        <input type="number" id="test-oxygen" step="0.1" min="0" placeholder="> 5">
                        <div class="test-info">Жизненно важен для рыб, снижается при высокой температуре</div>
                    </div>
                    <div class="test-input-group">
                        <label>Соленость (‰)</label>
                        <input type="number" id="test-salinity" step="0.1" min="0" placeholder="0 (пресная)">
                        <div class="test-info">Для морских аквариумов 35‰, для пресноводных 0‰</div>
                    </div>
                    <div id="additional-result" class="test-result" style="display: none;"></div>
                </div>
            </div>

            <button id="analyzeWater" class="btn btn-primary">🔬 Провести полный анализ воды</button>

            <!-- Списки рыб и растений в аквариуме -->
            <div class="species-selector">
                <h3>🐠 Рыбы в моем аквариуме</h3>
                <div id="myFishList" class="species-grid"></div>
                <button id="addFish" class="btn btn-secondary">➕ Добавить рыбу</button>
            </div>

            <div class="species-selector">
                <h3>🌿 Растения в моем аквариуме</h3>
                <div id="myPlantList" class="species-grid"></div>
                <button id="addPlant" class="btn btn-secondary">➕ Добавить растение</button>
            </div>
        </section>

        <!-- Раздел "Анализ воды" -->
        <section id="water-analysis" class="content-section">
            <h2>🧪 Научный анализ параметров воды</h2>

            <div class="info-card">
                <h4>📋 Инструкция по тестированию</h4>
                <ul class="tips-list">
                    <li>Тестируйте воду утром до кормления рыб</li>
                    <li>Используйте капельные тесты для точности</li>
                    <li>Ведите журнал регулярных измерений</li>
                    <li>При проблемах тестируйте ежедневно</li>
                </ul>
            </div>

            <div class="expert-advice">
                <h4>👨‍🔬 Совет эксперта</h4>
                <p>Стабильность параметров важнее их абсолютных значений. Рыбы лучше адаптируются к стабильной, но не идеальной среде, чем к постоянно меняющимся условиям.</p>
                <div class="expert-source">Профессор ихтиологии М.В. Кочетов</div>
            </div>

            <!-- Улучшенная панель тестов воды -->
            <div class="test-grid">
                <div class="test-card">
                    <h4>🧪 Экспресс-тесты для начинающих</h4>
                    <div class="test-input-group">
                        <label>Тест-полоски (общий обзор)</label>
                        <select id="test-strip">
                            <option value="">Выберите результат</option>
                            <option value="perfect">Идеально (все в норме)</option>
                            <option value="good">Хорошо (небольшие отклонения)</option>
                            <option value="warning">Требует внимания</option>
                            <option value="danger">Критично</option>
                        </select>
                    </div>
                    <div class="test-input-group">
                        <label>Прозрачность воды</label>
                        <select id="water-clarity">
                            <option value="">Выберите прозрачность</option>
                            <option value="crystal">Кристально чистая</option>
                            <option value="slightly-hazy">Слегка мутная</option>
                            <option value="hazy">Мутная</option>
                            <option value="very-hazy">Очень мутная</option>
                        </select>
                    </div>
                    <div class="test-input-group">
                        <label>Запах воды</label>
                        <select id="water-smell">
                            <option value="">Выберите запах</option>
                            <option value="neutral">Нейтральный</option>
                            <option value="earthy">Землистый</option>
                            <option value="rotten">Гнилостный</option>
                            <option value="chemical">Химический</option>
                        </select>
                    </div>
                    <button class="btn btn-info" onclick="quickWaterTest()">🚀 Быстрый анализ</button>
                </div>

                <div class="test-card">
                    <h4>🔬 Профессиональные тесты</h4>
                    <div class="test-input-group">
                        <label>TDS (общее содержание солей, ppm)</label>
                        <input type="number" id="test-tds" placeholder="100-300">
                    </div>
                    <div class="test-input-group">
                        <label>ORP (окислительно-восстановительный потенциал, mV)</label>
                        <input type="number" id="test-orp" placeholder="200-400">
                    </div>
                    <div class="test-input-group">
                        <label>Удельная электропроводность (мкСм/см)</label>
                        <input type="number" id="test-conductivity" placeholder="100-800">
                    </div>
                    <div class="test-info">Эти параметры важны для продвинутых аквариумистов и профессиональных установок</div>
                </div>

                <div class="test-card">
                    <h4>📈 Специализированные тесты</h4>
                    <div class="test-input-group">
                        <label>Кальций (Ca²⁺, мг/л)</label>
                        <input type="number" id="test-calcium" step="0.1" placeholder="20-60">
                    </div>
                    <div class="test-input-group">
                        <label>Магний (Mg²⁺, мг/л)</label>
                        <input type="number" id="test-magnesium" step="0.1" placeholder="5-15">
                    </div>
                    <div class="test-input-group">
                        <label>Калий (K⁺, мг/л)</label>
                        <input type="number" id="test-potassium" step="0.1" placeholder="5-15">
                    </div>
                    <div class="test-info">Важные элементы для растений и морских аквариумов</div>
                </div>
            </div>

            <div id="waterAnalysisHistory">
                <h3>📈 История анализов</h3>
                <div id="analysisChart" style="margin: 20px 0; min-height: 200px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #666;">
                    График изменения параметров (будет отображаться после нескольких измерений)
                </div>
                <div id="analysisEntries"></div>
            </div>

            <!-- Новый раздел с экспертными советами по воде -->
            <div class="expert-advice">
                <h4>💧 Советы по стабилизации параметров воды</h4>
                <div class="tips-list">
                    <li><strong>Буферизация pH:</strong> Используйте коралловую крошку или измельченные ракушки для стабилизации pH в щелочной зоне</li>
                    <li><strong>Контроль аммиака:</strong> При обнаружении аммиака немедленно сделайте подмену 50% воды и добавьте кондиционер с детоксификатором</li>
                    <li><strong>Управление нитратами:</strong> Быстрорастущие растения (роголистник, гигрофила) эффективно поглощают нитраты</li>
                    <li><strong>Стабильность жесткости:</strong> Измельченный доломит в фильтре поможет стабилизировать GH и KH</li>
                </div>
                <div class="expert-source">Советы основаны на методике Дианы Вальстад "Экология аквариума"</div>
            </div>

            <div class="info-card">
                <h4>🧬 Водоподготовка для разных типов аквариумов</h4>
                <div class="parameter-card">
                    <strong>Пресноводный травник:</strong> 
                    <ul>
                        <li>pH: 6.5-7.2</li>
                        <li>GH: 4-8°dH</li>
                        <li>KH: 3-6°dH</li>
                        <li>NO3: 10-20 мг/л</li>
                    </ul>
                </div>
                <div class="parameter-card">
                    <strong>Цихлидник:</strong>
                    <ul>
                        <li>pH: 7.5-8.5</li>
                        <li>GH: 10-20°dH</li>
                        <li>KH: 8-15°dH</li>
                        <li>NO3: < 30 мг/л</li>
                    </ul>
                </div>
                <div class="parameter-card">
                    <strong>Креветочник:</strong>
                    <ul>
                        <li>pH: 6.5-7.5</li>
                        <li>GH: 6-8°dH</li>
                        <li>KH: 2-5°dH</li>
                        <li>TDS: 150-250 ppm</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Раздел "Совместимость рыб" -->
        <section id="fish-compatibility" class="content-section">
            <h2>🐠 Совместимость рыб</h2>

            <div class="info-card">
                <h4>ℹ️ Как пользоваться</h4>
                <p>Выберите рыб, которых планируете содержать вместе. Система покажет их совместимость с подробными пояснениями и советами экспертов.</p>
            </div>

            <div class="species-selector">
                <h3>Выберите рыб для проверки совместимости</h3>
                <div id="fishCompatibilityGrid" class="species-grid"></div>
            </div>

            <div id="compatibilityResults" class="compatibility-section" style="display: none;">
                <h3>📊 Результаты анализа совместимости</h3>
                <div id="compatibilityMatrix"></div>
                <div id="compatibilityAdvice"></div>
            </div>

            <div class="expert-advice">
                <h4>🎯 Советы по содержанию рыб</h4>
                <ul class="tips-list">
                    <li>Учитывайте размеры взрослых рыб при выборе</li>
                    <li>Стайных рыб содержите группами от 6 особей</li>
                    <li>Территориальным видам обеспечьте укрытия</li>
                    <li>Подбирайте рыб с похожими требованиями к воде</li>
                    <li>Новых рыб добавляйте постепенно</li>
                </ul>
            </div>
        </section>

        <!-- Раздел "Подбор растений" -->
        <section id="plant-compatibility" class="content-section">
            <h2>🌿 Подбор растений</h2>

            <div class="calc-grid">
                <div class="calc-card">
                    <h4>💡 Параметры вашего аквариума</h4>
                    <div class="calc-input-group">
                        <label>Уровень освещения</label>
                        <select id="plantLighting">
                            <option value="low">Слабое (0.25-0.5 Вт/л)</option>
                            <option value="medium" selected>Среднее (0.5-0.8 Вт/л)</option>
                            <option value="high">Сильное (0.8-1.5 Вт/л)</option>
                            <option value="very-high">Очень сильное (1.5+ Вт/л)</option>
                        </select>
                    </div>
                    <div class="calc-input-group">
                        <label>CO₂ система</label>
                        <select id="plantCO2">
                            <option value="none">Нет</option>
                            <option value="diy">Самодельная</option>
                            <option value="professional">Профессиональная</option>
                        </select>
                    </div>
                    <div class="calc-input-group">
                        <label>Тип грунта</label>
                        <select id="plantSubstrate">
                            <option value="inert">Инертный (песок, галька)</option>
                            <option value="nutritive" selected>Питательный</option>
                            <option value="complete">Полная система с подложкой</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" onclick="findSuitablePlants()">🔍 Подобрать растения</button>
                </div>

                <div class="calc-card">
                    <h4>📏 Размеры аквариума</h4>
                    <div class="calc-input-row">
                        <div class="calc-input-group">
                            <label>Длина (см)</label>
                            <input type="number" id="tankLength" placeholder="60">
                        </div>
                        <div class="calc-input-group">
                            <label>Ширина (см)</label>
                            <input type="number" id="tankWidth" placeholder="30">
                        </div>
                    </div>
                    <div class="calc-input-row">
                        <div class="calc-input-group">
                            <label>Высота (см)</label>
                            <input type="number" id="tankHeight" placeholder="40">
                        </div>
                        <div class="calc-input-group">
                            <label>pH воды</label>
                            <input type="number" id="tankPH" step="0.1" placeholder="7.0">
                        </div>
                    </div>
                    <div class="calc-result" id="tankInfo" style="display: none;"></div>
                </div>
            </div>

            <div id="plantRecommendations" class="species-grid" style="display: none;"></div>

            <div class="expert-advice">
                <h4>🌱 Секреты успешного травника</h4>
                <ul class="tips-list">
                    <li>Начинайте с неприхотливых растений</li>
                    <li>Соблюдайте баланс света, CO₂ и удобрений</li>
                    <li>Обрезка стимулирует рост растений</li>
                    <li>Быстрорастущие растения подавляют водоросли</li>
                    <li>Растения на переднем плане должны быть низкими</li>
                </ul>
            </div>

            <div class="info-card">
                <h4>📊 Расчет освещения для растений</h4>
                <p><strong>Слабое освещение:</strong> Анубиас, Яванский мох, Криптокорины</p>
                <p><strong>Среднее освещение:</strong> Валлиснерия, Эхинодорусы, Людвигии</p>
                <p><strong>Сильное освещение:</strong> Роталы, Хемиантусы, почвопокровные</p>
                <p><strong>Очень сильное освещение:</strong> Глоссостигма, Хемиантус куба, требовательные красные растения</p>
            </div>
        </section>

        <!-- Раздел "Калькуляторы" -->
        <section id="calculators" class="content-section">
            <h2>🧮 Аквариумные калькуляторы</h2>

            <div class="calc-grid">
                <!-- Калькулятор объема -->
                <div class="calc-card">
                    <h4>📏 Расчет объема аквариума</h4>
                    <div class="calc-input-row">
                        <div class="calc-input-group">
                            <label>Длина (см)</label>
                            <input type="number" id="calcLength" onchange="calculateVolume()">
                        </div>
                        <div class="calc-input-group">
                            <label>Ширина (см)</label>
                            <input type="number" id="calcWidth" onchange="calculateVolume()">
                        </div>
                    </div>
                    <div class="calc-input-group">
                        <label>Высота воды (см)</label>
                        <input type="number" id="calcHeight" onchange="calculateVolume()">
                        <div class="test-info">Высота столба воды (без учета грунта и декора)</div>
                    </div>
                    <div id="volumeResult" class="calc-result" style="display: none;"></div>
                </div>

                <!-- Калькулятор грунта -->
                <div class="calc-card">
                    <h4>🏔️ Расчет количества грунта</h4>
                    <div class="calc-input-row">
                        <div class="calc-input-group">
                            <label>Длина дна (см)</label>
                            <input type="number" id="substratLength" onchange="calculateSubstrate()">
                        </div>
                        <div class="calc-input-group">
                            <label>Ширина дна (см)</label>
                            <input type="number" id="substratWidth" onchange="calculateSubstrate()">
                        </div>
                    </div>
                    <div class="calc-input-group">
                        <label>Желаемая толщина грунта (см)</label>
                        <input type="number" id="substratDepth" value="5" onchange="calculateSubstrate()">
                        <div class="test-info">Рекомендуется 4-6 см для растений, 2-3 см для рыб</div>
                    </div>
                    <div id="substrateResult" class="calc-result" style="display: none;"></div>
                </div>

                <!-- Калькулятор освещения -->
                <div class="calc-card">
                    <h4>💡 Расчет освещения</h4>
                    <div class="calc-input-row">
                        <div class="calc-input-group">
                            <label>Объем аквариума (л)</label>
                            <input type="number" id="lightVolume" onchange="calculateLighting()">
                        </div>
                        <div class="calc-input-group">
                            <label>Тип аквариума</label>
                            <select id="lightType" onchange="calculateLighting()">
                                <option value="fish">Только рыбы</option>
                                <option value="easy-plants">Простые растения</option>
                                <option value="planted">Травник</option>
                                <option value="high-tech">Хай-тек</option>
                            </select>
                        </div>
                    </div>
                    <div id="lightingResult" class="calc-result" style="display: none;"></div>
                </div>

                <!-- Калькулятор подмен воды -->
                <div class="calc-card">
                    <h4>💧 Расчет подмен воды</h4>
                    <div class="calc-input-row">
                        <div class="calc-input-group">
                            <label>Объем аквариума (л)</label>
                            <input type="number" id="changeVolume" onchange="calculateWaterChange()">
                        </div>
                        <div class="calc-input-group">
                            <label>% подмены</label>
                            <input type="number" id="changePercent" value="25" min="10" max="80" onchange="calculateWaterChange()">
                        </div>
                    </div>
                    <div class="calc-input-group">
                        <label>Частота подмен</label>
                        <select id="changeFrequency" onchange="calculateWaterChange()">
                            <option value="weekly">Еженедельно</option>
                            <option value="biweekly">Раз в 2 недели</option>
                            <option value="monthly">Ежемесячно</option>
                        </select>
                    </div>
                    <div id="waterChangeResult" class="calc-result" style="display: none;"></div>
                </div>

                <!-- Калькулятор стекла -->
                <div class="calc-card">
                    <h4>🏗️ Расчет толщины стекла</h4>
                    <div class="calc-input-row">
                        <div class="calc-input-group">
                            <label>Высота аквариума (см)</label>
                            <input type="number" id="glassHeight" onchange="calculateGlass()">
                        </div>
                        <div class="calc-input-group">
                            <label>Длина аквариума (см)</label>
                            <input type="number" id="glassLength" onchange="calculateGlass()">
                        </div>
                    </div>
                    <div class="calc-input-group">
                        <label>Тип конструкции</label>
                        <select id="glassType" onchange="calculateGlass()">
                            <option value="standard">Стандартная</option>
                            <option value="reinforced">С ребрами жесткости</option>
                            <option value="frameless">Безрамная</option>
                        </select>
                    </div>
                    <div id="glassResult" class="calc-result" style="display: none;"></div>
                </div>

                <!-- Калькулятор тумбы -->
                <div class="calc-card">
                    <h4>🪑 Расчет тумбы</h4>
                    <div class="calc-input-row">
                        <div class="calc-input-group">
                            <label>Объем аквариума (л)</label>
                            <input type="number" id="cabinetVolume" onchange="calculateCabinet()">
                        </div>
                        <div class="calc-input-group">
                            <label>Материал тумбы</label>
                            <select id="cabinetMaterial" onchange="calculateCabinet()">
                                <option value="chipboard">ДСП</option>
                                <option value="plywood">Фанера</option>
                                <option value="wood">Массив дерева</option>
                                <option value="metal">Металлический каркас</option>
                            </select>
                        </div>
                    </div>
                    <div id="cabinetResult" class="calc-result" style="display: none;"></div>
                </div>
            </div>

            <div class="expert-advice">
                <h4>🔧 Советы по сборке аквариума</h4>
                <ul class="tips-list">
                    <li>Всегда используйте специальный аквариумный силикон</li>
                    <li>Дайте силикону высохнуть минимум 24 часа</li>
                    <li>Тестируйте аквариум на протечки перед запуском</li>
                    <li>Тумба должна выдерживать вес с запасом в 1.5-2 раза</li>
                    <li>Обеспечьте ровную поверхность под аквариум</li>
                </ul>
            </div>
        </section>

        <!-- Раздел "Азотный цикл" -->
        <section id="nitrogen-cycle" class="content-section">
            <h2>🔄 Азотный цикл в аквариуме</h2>

            <div class="info-card">
                <h4>🎯 Что такое азотный цикл?</h4>
                <p>Азотный цикл - это процесс превращения токсичных отходов жизнедеятельности рыб в менее вредные соединения благодаря полезным бактериям. Это основа биологической фильтрации.</p>
            </div>

            <div class="nitrogen-stages">
                <div class="stage-card">
                    <div class="stage-number">1</div>
                    <div class="stage-icon">🐠</div>
                    <h4>Образование аммиака</h4>
                    <p>Рыбы выделяют аммиак (NH₃) через жабры и с отходами. Разлагается корм и отмершие растения.</p>
                    <div class="expert-advice">
                        <p><strong>Контроль:</strong> Не перекармливайте, убирайте остатки корма, подменивайте воду.</p>
                    </div>
                </div>

                <div class="stage-card">
                    <div class="stage-number">2</div>
                    <div class="stage-icon">🦠</div>
                    <h4>Нитрификация 1-я стадия</h4>
                    <p>Бактерии Nitrosomonas окисляют аммиак (NH₃) в нитриты (NO₂). Процесс занимает 2-3 недели для установления.</p>
                    <div class="expert-advice">
                        <p><strong>Помощь:</strong> Добавьте живые бактерии, обеспечьте аэрацию, не промывайте фильтр слишком часто.</p>
                    </div>
                </div>

                <div class="stage-card">
                    <div class="stage-number">3</div>
                    <div class="stage-icon">🦠</div>
                    <h4>Нитрификация 2-я стадия</h4>
                    <p>Бактерии Nitrobacter превращают нитриты (NO₂) в нитраты (NO₃). Устанавливается через 4-6 недель.</p>
                    <div class="expert-advice">
                        <p><strong>Важно:</strong> Нитриты токсичны! При их обнаружении - подмена воды и усиление биофильтрации.</p>
                    </div>
                </div>

                <div class="stage-card">
                    <div class="stage-number">4</div>
                    <div class="stage-icon">🌱</div>
                    <h4>Потребление нитратов</h4>
                    <p>Растения поглощают нитраты как удобрение. Нитраты также удаляются подменами воды.</p>
                    <div class="expert-advice">
                        <p><strong>Баланс:</strong> Быстрорастущие растения + регулярные подмены воды = стабильная система.</p>
                    </div>
                </div>
            </div>

            <div class="expert-advice">
                <h4>📋 Запуск нового аквариума: пошаговая инструкция</h4>
                <div style="display: grid; gap: 15px; margin: 15px 0;">
                    <div class="parameter-card">
                        <strong>Неделя 1:</strong> Установка оборудования, заливка воды, добавление грунта и декора. Запуск фильтра.
                    </div>
                    <div class="parameter-card">
                        <strong>Неделя 2:</strong> Добавление живых бактерий, первые неприхотливые растения. Тестирование аммиака.
                    </div>
                    <div class="parameter-card">
                        <strong>Неделя 3-4:</strong> Появление нитритов (пик цикла). Ежедневные тесты, при необходимости подмены воды.
                    </div>
                    <div class="parameter-card">
                        <strong>Неделя 5-6:</strong> Снижение нитритов, рост нитратов. Можно добавлять первых рыб (1-2 особи).
                    </div>
                    <div class="parameter-card">
                        <strong>Неделя 7-8:</strong> Стабилизация параметров. Постепенное заселение остальных обитателей.
                    </div>
                </div>
            </div>

            <div class="warning-card">
                <h4>⚠️ Критические ошибки при запуске</h4>
                <ul class="tips-list" style="color: #d84315;">
                    <li>Запуск большого количества рыб сразу</li>
                    <li>Кормление в первые недели запуска</li>
                    <li>Частая промывка или замена фильтрующих материалов</li>
                    <li>Использование лекарств, убивающих бактерии</li>
                    <li>Игнорирование тестов воды</li>
                </ul>
            </div>

            <div class="info-card">
                <h4>🔬 Научные факты об азотном цикле</h4>
                <p><strong>Температурная зависимость:</strong> При 15°C цикл идет в 2 раза медленнее, чем при 25°C</p>
                <p><strong>pH влияние:</strong> При pH < 7 аммиак менее токсичен, но бактерии работают медленнее</p>
                <p><strong>Кислород:</strong> Нитрифицирующие бактерии потребляют много кислорода (4.6 мг O₂ на 1 мг NH₃)</p>
                <p><strong>Соотношения:</strong> 1 мг NH₃ → 3.3 мг NO₂ → 4.4 мг NO₃</p>
            </div>
        </section>

        <!-- Новый раздел "Акваскейпинг" -->
        <section id="aquascaping" class="content-section">
            <h2>🎨 Акваскейпинг: искусство подводного ландшафта</h2>

            <div class="info-card">
                <h4>🌿 Что такое акваскейпинг?</h4>
                <p>Акваскейпинг - это искусство создания гармоничных подводных ландшафтов, сочетающее принципы дизайна, биологии и экологии. Основатель современного акваскейпинга - Такаши Амано.</p>
            </div>

            <div class="expert-advice">
                <h4>🎯 Основные принципы акваскейпинга</h4>
                <ul class="tips-list">
                    <li><strong>Золотое сечение:</strong> Размещайте фокальные точки на расстоянии 1/3 от краев аквариума</li>
                    <li><strong>Глубина перспективы:</strong> Создавайте иллюзию глубины с помощью правильного расположения элементов</li>
                    <li><strong>Баланс:</strong> Достигайте визуального равновесия между пустым и заполненным пространством</li>
                    <li><strong>Естественность:</strong> Воссоздавайте природные ландшафты, избегая симметрии</li>
                </ul>
            </div>

            <h3>🎨 Стили акваскейпинга</h3>

            <div class="calc-grid">
                <div class="info-card">
                    <h4>🏞️ Nature Style (Природный стиль)</h4>
                    <p><strong>Основатель:</strong> Такаши Амано</p>
                    <p><strong>Философия:</strong> Воссоздание природных ландшафтов под водой</p>
                    <p><strong>Принципы:</strong></p>
                    <ul class="tips-list">
                        <li>Асимметрия и золотое сечение</li>
                        <li>Использование натуральных материалов</li>
                        <li>Минимум разных видов растений</li>
                        <li>Создание глубины перспективы</li>
                    </ul>
                    <p><strong>Материалы:</strong> Seiryu stone, Manten stone, коряги Manzanita</p>
                    <p><strong>Растения:</strong> Glossostigma, Riccia, Eleocharis, Rotala</p>
                    <p><strong>Hardscape ratio:</strong> 60% объема до посадки растений</p>
                </div>

                <div class="info-card">
                    <h4>🇳🇱 Dutch Style (Голландский стиль)</h4>
                    <p><strong>Происхождение:</strong> Нидерланды, 1930-е годы</p>
                    <p><strong>Философия:</strong> Подводный сад с многообразием растений</p>
                    <p><strong>Принципы:</strong></p>
                    <ul class="tips-list">
                        <li>Террасное расположение</li>
                        <li>Уличная перспектива</li>
                        <li>Контрастные цвета и текстуры</li>
                        <li>Фокальные точки (point plants)</li>
                    </ul>
                    <p><strong>Структура:</strong> 30% переднего, 40% среднего, 30% заднего плана</p>
                    <p><strong>Растения:</strong> 15-20 видов разных цветов и форм</p>
                    <p><strong>Обслуживание:</strong> Еженедельная стрижка и пересадка</p>
                </div>

                <div class="info-card">
                    <h4>🗻 Iwagumi (Ивагуми)</h4>
                    <p><strong>Концепция:</strong> Композиция из камней</p>
                    <p><strong>Правила:</strong></p>
                    <ul class="tips-list">
                        <li>Нечетное количество камней (3, 5, 7)</li>
                        <li>Oyaishi (главный камень) - самый большой</li>
                        <li>Fukuishi (второстепенные) поддерживают композицию</li>
                        <li>Soeishi (акцентные) добавляют динамику</li>
                    </ul>
                    <p><strong>Выбор растений:</strong> 1-3 вида почвопокровных</p>
                    <p><strong>Сложность:</strong> Высокая - нет места для ошибок</p>
                    <p><strong>Типичные растения:</strong> Hemianthus cuba, Glossostigma, Eleocharis</p>
                </div>

                <div class="info-card">
                    <h4>🌴 Jungle Style (Джунгли)</h4>
                    <p><strong>Концепция:</strong> Густые заросли без четкой структуры</p>
                    <p><strong>Подход:</strong> Естественный хаос с контролируемым ростом</p>
                    <p><strong>Преимущества:</strong></p>
                    <ul class="tips-list">
                        <li>Более естественный вид</li>
                        <li>Проще в обслуживании</li>
                        <li>Лучше для пугливых рыб</li>
                        <li>Скрывает оборудование</li>
                    </ul>
                    <p><strong>Растения:</strong> Быстрорастущие стеблевые</p>
                    <p><strong>Обслуживание:</strong> Минимальная стрижка, природный отбор</p>
                    <p><strong>Рыбы:</strong> Стайные виды, креветки</p>
                </div>
            </div>

            <div class="expert-advice">
                <h4>📐 Золотое сечение в акваскейпинге</h4>
                <div class="parameter-card">
                    <strong>Правило золотого сечения 1:1.618</strong><br>
                    Применение: Фокальные точки на расстоянии 1/3 от краев
                </div>
                <div class="parameter-card">
                    <strong>Соотношение высот</strong><br>
                    Задний план в 1.6 раза выше среднего плана
                </div>
                <div class="parameter-card">
                    <strong>Треугольная композиция</strong><br>
                    Основные элементы образуют треугольник для динамики
                </div>
                <div class="expert-source">Основано на принципах IAPLC (International Aquatic Plants Layout Contest)</div>
            </div>

            <h3>🛠️ Практические советы по созданию акваскейпа</h3>

            <div class="calc-grid">
                <div class="info-card">
                    <h4>📝 Планирование композиции</h4>
                    <ul class="tips-list">
                        <li>Нарисуйте эскиз перед началом работы</li>
                        <li>Определите фокальную точку</li>
                        <li>Продумайте расположение оборудования</li>
                        <li>Учтите рост растений</li>
                        <li>Создайте несколько вариантов</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h4>🏗️ Техника установки</h4>
                    <ul class="tips-list">
                        <li>Сначала установите основной хардскейп</li>
                        <li>Затем добавьте субстрат и грунт</li>
                        <li>Посадите растения от заднего плана к переднему</li>
                        <li>Используйте пинцеты для точной посадки</li>
                        <li>Заполняйте аквариум водой медленно</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h4>🌱 Выбор растений</h4>
                    <ul class="tips-list">
                        <li>Сочетайте разные текстуры и цвета</li>
                        <li>Учитывайте скорость роста</li>
                        <li>Подбирайте растения по требованиям к свету</li>
                        <li>Используйте почвопокровные для переднего плана</li>
                        <li>Добавляйте акцентные растения для цвета</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h4>🎯 Подбор рыб</h4>
                    <ul class="tips-list">
                        <li>Выбирайте рыб, подходящих стилю</li>
                        <li>Учитывайте поведение и размер</li>
                        <li>Стайные рыбы усиливают динамику</li>
                        <li>Донные виды оживляют передний план</li>
                        <li>Избегайте рыб, повреждающих растения</li>
                    </ul>
                </div>
            </div>

            <div class="warning-card">
                <h4>⚠️ Распространенные ошибки начинающих</h4>
                <ul class="tips-list">
                    <li><strong>Симметрия:</strong> Создает статичный и неестественный вид</li>
                    <li><strong>Перегруженность:</strong> Слишком много элементов мешает восприятию</li>
                    <li><strong>Неправильный масштаб:</strong> Крупные растения на переднем плане</li>
                    <li><strong>Игнорирование роста:</strong> Неучет конечных размеров растений</li>
                    <li><strong>Отсутствие фокуса:</strong> Композиция без центрального элемента</li>
                </ul>
            </div>

            <div class="expert-advice">
                <h4>🏆 Советы от чемпионов IAPLC</h4>
                <div style="display: grid; gap: 15px; margin: 15px 0;">
                    <div class="parameter-card">
                        <strong>Такаши Амано:</strong> "Изучайте природу - лучшие композиции созданы ею"
                    </div>
                    <div class="parameter-card">
                        <strong>Филипе Оливейра:</strong> "Не бойтесь экспериментировать с новыми материалами"
                    </div>
                    <div class="parameter-card">
                        <strong>Джош Сим:</strong> "Свет - это кисть, а растения - краски акваскейпера"
                    </div>
                    <div class="parameter-card">
                        <strong>Аманда Лангер:</strong> "Терпение - ключ к успешному акваскейпу"
                    </div>
                </div>
            </div>
        </section>

        <!-- Раздел "Освещение" -->
        <section id="lighting" class="content-section">
            <h2>💡 Освещение аквариума</h2>

            <div class="calc-grid">
                <div class="calc-card">
                    <h4>🔆 Калькулятор освещения</h4>
                    <div class="calc-input-group">
                        <label>Длина аквариума (см)</label>
                        <input type="number" id="lightCalcLength" onchange="calculateDetailedLighting()">
                    </div>
                    <div class="calc-input-group">
                        <label>Ширина аквариума (см)</label>
                        <input type="number" id="lightCalcWidth" onchange="calculateDetailedLighting()">
                    </div>
                    <div class="calc-input-group">
                        <label>Высота столба воды (см)</label>
                        <input type="number" id="lightCalcHeight" onchange="calculateDetailedLighting()">
                    </div>
                    <div class="calc-input-group">
                        <label>Тип аквариума</label>
                        <select id="lightCalcType" onchange="calculateDetailedLighting()">
                            <option value="fish-only">Только рыбы</option>
                            <option value="low-light">Теневыносливые растения</option>
                            <option value="medium-light">Средние требования</option>
                            <option value="high-light">Светолюбивые растения</option>
                            <option value="carpet">Почвопокровные растения</option>
                        </select>
                    </div>
                    <div id="detailedLightResult" class="calc-result" style="display: none;"></div>
                </div>

                <div class="calc-card">
                    <h4>📊 Параметры освещения</h4>
                    <div class="parameter-card">
                        <div class="parameter-value">6500K</div>
                        <div>Оптимальная цветовая температура для растений</div>
                    </div>
                    <div class="parameter-card">
                        <div class="parameter-value">30-50 мкмоль</div>
                        <div>PAR для простых растений (м²/с)</div>
                    </div>
                    <div class="parameter-card">
                        <div class="parameter-value">50-80 мкмоль</div>
                        <div>PAR для требовательных растений</div>
                    </div>
                    <div class="parameter-card">
                        <div class="parameter-value">8-10 часов</div>
                        <div>Продолжительность светового дня</div>
                    </div>
                </div>
            </div>

            <h3>💡 Типы аквариумного освещения</h3>

            <div class="calc-grid">
                <div class="info-card">
                    <h4>🔵 LED освещение</h4>
                    <p><strong>Плюсы:</strong> Экономичность, долгий срок службы, регулировка спектра</p>
                    <p><strong>Минусы:</strong> Высокая начальная стоимость, точечный свет</p>
                    <p><strong>Лучше для:</strong> Всех типов аквариумов, особенно травников</p>
                    <div class="expert-advice">
                        <p><strong>Совет эксперта:</strong> Выбирайте LED с полным спектром и возможностью диммирования</p>
                    </div>
                </div>

                <div class="info-card">
                    <h4>💡 Люминесцентные лампы T5/T8</h4>
                    <p><strong>Плюсы:</strong> Равномерное освещение, проверенная технология</p>
                    <p><strong>Минусы:</strong> Нагрев, потеря яркости со временем</p>
                    <p><strong>Лучше для:</strong> Простых растений, бюджетных установок</p>
                    <div class="expert-advice">
                        <p><strong>Совет эксперта:</strong> T5 эффективнее T8, меняйте лампы каждый год</p>
                    </div>
                </div>

                <div class="info-card">
                    <h4>🔥 Металлогалогенные лампы</h4>
                    <p><strong>Плюсы:</strong> Мощный свет, естественный спектр</p>
                    <p><strong>Минусы:</strong> Сильный нагрев, высокое потребление</p>
                    <p><strong>Лучше для:</strong> Глубоких аквариумов, морских рифов</p>
                    <div class="expert-advice">
                        <p><strong>Совет эксперта:</strong> Требуют принудительное охлаждение, дорогие в обслуживании</p>
                    </div>
                </div>
            </div>

            <div class="expert-advice">
                <h4>🌈 Спектр освещения для растений</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0;">
                    <div class="parameter-card" style="background: #ffebee;">
                        <strong>Красный (660-700 нм)</strong><br>
                        Стимулирует рост стеблей, цветение
                    </div>
                    <div class="parameter-card" style="background: #e8f5e8;">
                        <strong>Зеленый (500-600 нм)</strong><br>
                        Проникает глубже, общий фотосинтез
                    </div>
                    <div class="parameter-card" style="background: #e3f2fd;">
                        <strong>Синий (400-500 нм)</strong><br>
                        Компактный рост, здоровые листья
                    </div>
                    <div class="parameter-card" style="background: #f3e5f5;">
                        <strong>УФ (350-400 нм)</strong><br>
                        Защитные пигменты, яркая окраска
                    </div>
                </div>
            </div>

            <div class="warning-card">
                <h4>⚠️ Проблемы с освещением</h4>
                <ul class="tips-list">
                    <li><strong>Водоросли:</strong> Избыток света + дефицит CO₂ = вспышка водорослей</li>
                    <li><strong>Вытягивание растений:</strong> Недостаток света, растения тянутся вверх</li>
                    <li><strong>Желтение листьев:</strong> Старые лампы или неподходящий спектр</li>
                    <li><strong>Медленный рост:</strong> Слишком короткий световой день</li>
                </ul>
            </div>

            <div class="expert-advice">
                <h4>🕐 Режимы освещения</h4>
                <div class="parameter-card">
                    <strong>Рассвет (2 часа):</strong> Постепенное увеличение яркости с 0% до 100%
                </div>
                <div class="parameter-card">
                    <strong>День (6-8 часов):</strong> Полная мощность освещения
                </div>
                <div class="parameter-card">
                    <strong>Закат (2 часа):</strong> Плавное снижение до 0%
                </div>
                <div class="parameter-card">
                    <strong>Ночь (8-10 часов):</strong> Полная темнота или лунный свет (1%)
                </div>
            </div>

            <div class="info-card">
                <h4>🔧 Практические советы по установке</h4>
                <ul class="tips-list">
                    <li>Устанавливайте светильники на высоте 20-30 см над водой</li>
                    <li>Используйте отражатели для увеличения эффективности</li>
                    <li>Предусмотрите защиту от влаги</li>
                    <li>Равномерно распределяйте источники света</li>
                    <li>Контролируйте температуру воды при мощном освещении</li>
                </ul>
            </div>
        </section>
    </div>

    <!-- Toast уведомления -->
    <div id="toast" class="toast"></div>

    <script>
        // ============================================
        // ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ И ДАННЫЕ
        // ============================================

        let currentSection = 'my-aquarium';
        let aquariumData = JSON.parse(localStorage.getItem('aquariumData')) || {
            photos: [],
            notes: [],
            fish: [],
            plants: [],
            waterTests: [],
            selectedFishForCompatibility: []
        };

        // Система подписок
        const subscriptionPlans = {
            trial: {
                name: 'Пробная версия',
                price: 0,
                duration: 14,
                features: [
                    'Базовый анализ воды',
                    'Совместимость до 3 рыб',
                    'Ограниченная база растений',
                    'Реклама в приложении'
                ],
                limitations: {
                    maxFishCompatibility: 3,
                    advancedTests: false,
                    expertAdvice: false,
                    offlineMode: false
                }
            },
            pro: {
                name: 'PRO версия',
                price: 399,
                duration: 30,
                features: [
                    'Расширенный анализ воды',
                    'Неограниченная совместимость рыб',
                    'Полная база растений и рыб',
                    'Экспертные советы и рекомендации',
                    'Работа офлайн',
                    'Приоритетная поддержка',
                    'Экспорт данных'
                ],
                limitations: {
                    maxFishCompatibility: null,
                    advancedTests: true,
                    expertAdvice: true,
                    offlineMode: true
                }
            }
        };

        let currentSubscription = {
            plan: 'trial',
            expires: null,
            isActive: false
        };

        // Расширенная база данных рыб
        const fishDatabase = {
            "neon": {
                "name": "Неон голубой",
                "scientificName": "Paracheirodon innesi",
                "size": "3-4 см",
                "temp": "22-26°C",
                "ph": "6.0-7.0",
                "gh": "2-12°dH",
                "behavior": "Мирная стайная",
                "care": "Легкий",
                "compatibility": ["neon", "guppy", "corydoras", "tetra", "rasbora"],
                "incompatible": ["angelfish", "cichlid", "oscar"],
                "food": ["микро гранулы", "дафния", "циклоп", "артемия"],
                "tips": "Содержать стайкой от 10 особей. Любит мягкую воду и приглушенное освещение.",
                "breeding": "Нерест в мягкой кислой воде при 24-25°C",
                "lifespan": "5-8 лет",
                "origin": "Южная Америка",
                "minTankSize": 40,
                "schoolSize": "10+",
                "waterFlow": "Слабое",
                "lighting": "Приглушенное"
            },
            "guppy": {
                "name": "Гуппи",
                "scientificName": "Poecilia reticulata",
                "size": "4-6 см",
                "temp": "22-28°C",
                "ph": "7.0-8.5",
                "gh": "10-25°dH",
                "behavior": "Мирная активная",
                "care": "Очень легкий",
                "compatibility": ["guppy", "molly", "platy", "neon", "corydoras"],
                "incompatible": ["angelfish", "cichlid", "barb"],
                "food": ["хлопья", "гранулы", "живой корм", "растительный"],
                "tips": "Неприхотливая живородящая рыба. Быстро размножаются.",
                "breeding": "Живородящие, размножаются каждые 3-4 недели",
                "lifespan": "2-3 года",
                "origin": "Южная Америка",
                "minTankSize": 20,
                "schoolSize": "3+",
                "waterFlow": "Среднее",
                "lighting": "Яркое"
            },
            "angelfish": {
                "name": "Скалярия",
                "scientificName": "Pterophyllum scalare",
                "size": "15-20 см",
                "temp": "24-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "behavior": "Территориальная",
                "care": "Средний",
                "compatibility": ["angelfish", "corydoras", "discus"],
                "incompatible": ["neon", "guppy", "small-fish"],
                "food": ["хлопья", "гранулы", "живой корм"],
                "tips": "Нужен высокий аквариум. Могут поедать мелких рыб.",
                "breeding": "Нерест на листья растений или плоские поверхности",
                "lifespan": "8-12 лет",
                "origin": "Южная Америка",
                "minTankSize": 200,
                "schoolSize": "2",
                "waterFlow": "Слабое",
                "lighting": "Среднее"
            },
            "corydoras": {
                "name": "Коридорас крапчатый",
                "scientificName": "Corydoras paleatus",
                "size": "5-8 см",
                "temp": "20-26°C",
                "ph": "6.0-7.5",
                "gh": "5-18°dH",
                "behavior": "Мирная донная",
                "care": "Легкий",
                "compatibility": ["corydoras", "neon", "guppy", "angelfish", "tetra"],
                "incompatible": ["aggressive-cichlids"],
                "food": ["тонущие таблетки", "живой корм", "остатки корма"],
                "tips": "Стайные донные рыбы. Нужен мягкий грунт.",
                "breeding": "Нерест стимулируется подменой прохладной воды",
                "lifespan": "5-8 лет",
                "origin": "Южная Америка",
                "minTankSize": 80,
                "schoolSize": "6+",
                "waterFlow": "Среднее",
                "lighting": "Любое"
            },
            "betta": {
                "name": "Петушок",
                "scientificName": "Betta splendens",
                "size": "6-7 см",
                "temp": "24-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "behavior": "Агрессивная к сородичам",
                "care": "Средний",
                "compatibility": ["corydoras", "tetra", "rasbora"],
                "incompatible": ["betta", "guppy", "angelfish"],
                "food": ["гранулы для петушков", "мотыль", "дафния"],
                "tips": "Самцов содержать поодиночке. Строят пенные гнезда.",
                "breeding": "Самец строит пенное гнездо на поверхности",
                "lifespan": "3-5 лет",
                "origin": "Юго-Восточная Азия",
                "minTankSize": 15,
                "schoolSize": "1",
                "waterFlow": "Очень слабое",
                "lighting": "Среднее"
            },
            "molly": {
                "name": "Моллинезия",
                "scientificName": "Poecilia sphenops",
                "size": "8-12 см",
                "temp": "22-28°C",
                "ph": "7.0-8.5",
                "gh": "15-30°dH",
                "behavior": "Мирная активная",
                "care": "Легкий",
                "compatibility": ["molly", "guppy", "platy", "corydoras"],
                "incompatible": ["aggressive-cichlids"],
                "food": ["хлопья", "растительный корм", "спирулина"],
                "tips": "Любят жесткую щелочную воду. Можно добавлять соль.",
                "breeding": "Живородящие, как гуппи",
                "lifespan": "3-5 лет",
                "origin": "Центральная Америка",
                "minTankSize": 60,
                "schoolSize": "3+",
                "waterFlow": "Среднее",
                "lighting": "Яркое"
            },
            "tetra": {
                "name": "Тетра кардинал",
                "scientificName": "Paracheirodon axelrodi",
                "size": "4-5 см",
                "temp": "23-27°C",
                "ph": "5.5-6.8",
                "gh": "2-8°dH",
                "behavior": "Мирная стайная",
                "care": "Средний",
                "compatibility": ["tetra", "neon", "corydoras", "angelfish"],
                "incompatible": ["aggressive-fish"],
                "food": ["микро гранулы", "живой корм", "замороженный"],
                "tips": "Более требовательны к воде чем неоны. Стайная от 8 особей.",
                "breeding": "Сложный нерест в очень мягкой воде",
                "lifespan": "5-6 лет",
                "origin": "Южная Америка",
                "minTankSize": 60,
                "schoolSize": "8+",
                "waterFlow": "Слабое",
                "lighting": "Приглушенное"
            },
            "platy": {
                "name": "Пецилия",
                "scientificName": "Xiphophorus maculatus",
                "size": "5-7 см",
                "temp": "18-25°C",
                "ph": "7.0-8.2",
                "gh": "10-25°dH",
                "behavior": "Мирная активная",
                "care": "Очень легкий",
                "compatibility": ["platy", "guppy", "molly", "corydoras", "neon"],
                "incompatible": ["aggressive-cichlids"],
                "food": ["хлопья", "растительный корм", "живой корм"],
                "tips": "Выносливая живородящая рыба. Легко размножается.",
                "breeding": "Живородящие, мальки крупные",
                "lifespan": "3-4 года",
                "origin": "Центральная Америка",
                "minTankSize": 40,
                "schoolSize": "3+",
                "waterFlow": "Среднее",
                "lighting": "Яркое"
            },
            "swordtail": {
                "name": "Меченосец",
                "scientificName": "Xiphophorus hellerii",
                "size": "8-12 см",
                "temp": "20-28°C",
                "ph": "7.0-8.0",
                "gh": "10-25°dH",
                "behavior": "Мирная активная",
                "care": "Легкий",
                "compatibility": ["swordtail", "guppy", "molly", "platy", "corydoras"],
                "incompatible": ["aggressive-cichlids"],
                "food": ["хлопья", "гранулы", "растительный корм"],
                "tips": "Самцы имеют характерный меч на хвосте. Прыгучие.",
                "breeding": "Живородящие, очень плодовитые",
                "lifespan": "3-5 лет",
                "origin": "Центральная Америка",
                "minTankSize": 80,
                "schoolSize": "3+",
                "waterFlow": "Среднее",
                "lighting": "Яркое"
            },
            "rasbora": {
                "name": "Расбора клинопятнистая",
                "scientificName": "Trigonostigma heteromorpha",
                "size": "4-5 см",
                "temp": "22-26°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "behavior": "Мирная стайная",
                "care": "Легкий",
                "compatibility": ["rasbora", "neon", "tetra", "corydoras", "betta"],
                "incompatible": ["large-cichlids"],
                "food": ["мелкие хлопья", "живой корм", "замороженный"],
                "tips": "Активные стайные рыбы. Любят густые заросли растений.",
                "breeding": "Нерест под листья растений",
                "lifespan": "4-6 лет",
                "origin": "Юго-Восточная Азия",
                "minTankSize": 60,
                "schoolSize": "8+",
                "waterFlow": "Слабое",
                "lighting": "Среднее"
            },
            "danio": {
                "name": "Данио рерио",
                "scientificName": "Danio rerio",
                "size": "5-6 см",
                "temp": "18-25°C",
                "ph": "6.5-7.5",
                "gh": "5-20°dH",
                "behavior": "Мирная активная",
                "care": "Очень легкий",
                "compatibility": ["danio", "neon", "guppy", "corydoras", "tetra"],
                "incompatible": ["slow-fish"],
                "food": ["хлопья", "мелкие гранулы", "живой корм"],
                "tips": "Очень активные и выносливые. Держатся в верхних слоях.",
                "breeding": "Разбрасывают икру среди растений",
                "lifespan": "3-5 лет",
                "origin": "Южная Азия",
                "minTankSize": 40,
                "schoolSize": "6+",
                "waterFlow": "Среднее",
                "lighting": "Яркое"
            },
            "barb": {
                "name": "Барбус суматранский",
                "scientificName": "Puntigrus tetrazona",
                "size": "6-7 см",
                "temp": "20-26°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "behavior": "Активная стайная",
                "care": "Легкий",
                "compatibility": ["barb", "danio", "rasbora"],
                "incompatible": ["guppy", "angelfish", "betta", "slow-fish"],
                "food": ["хлопья", "гранулы", "живой корм"],
                "tips": "Могут обкусывать плавники медлительных рыб. Стайные.",
                "breeding": "Разбрасывают икру среди растений",
                "lifespan": "4-6 лет",
                "origin": "Юго-Восточная Азия",
                "minTankSize": 80,
                "schoolSize": "6+",
                "waterFlow": "Среднее",
                "lighting": "Яркое"
            },
            "goldfish": {
                "name": "Золотая рыбка",
                "scientificName": "Carassius auratus",
                "size": "15-25 см",
                "temp": "16-24°C",
                "ph": "6.5-8.0",
                "gh": "10-20°dH",
                "behavior": "Мирная медлительная",
                "care": "Средний",
                "compatibility": ["goldfish"],
                "incompatible": ["tropical-fish", "small-fish"],
                "food": ["специальные гранулы", "растительный корм"],
                "tips": "Холодноводные рыбы. Производят много отходов.",
                "breeding": "Нерест весной при понижении температуры",
                "lifespan": "10-30 лет",
                "origin": "Китай",
                "minTankSize": 200,
                "schoolSize": "1-2",
                "waterFlow": "Слабое",
                "lighting": "Яркое"
            },
            "discus": {
                "name": "Дискус",
                "scientificName": "Symphysodon discus",
                "size": "18-20 см",
                "temp": "28-30°C",
                "ph": "6.0-6.8",
                "gh": "1-8°dH",
                "behavior": "Мирная спокойная",
                "care": "Сложный",
                "compatibility": ["discus", "angelfish", "corydoras"],
                "incompatible": ["active-fish", "aggressive-fish"],
                "food": ["специальные гранулы", "говяжье сердце", "артемия"],
                "tips": "Короли аквариума. Требуют идеальной воды и высокой температуры.",
                "breeding": "Кормят мальков кожными выделениями",
                "lifespan": "10-15 лет",
                "origin": "Южная Америка",
                "minTankSize": 400,
                "schoolSize": "4+",
                "waterFlow": "Очень слабое",
                "lighting": "Приглушенное"
            },
            "cichlid": {
                "name": "Цихлазома северум",
                "scientificName": "Heros efasciatus",
                "size": "15-18 см",
                "temp": "24-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "behavior": "Территориальная",
                "care": "Средний",
                "compatibility": ["cichlid", "large-catfish"],
                "incompatible": ["small-fish", "peaceful-fish"],
                "food": ["гранулы для цихлид", "живой корм", "растительный"],
                "tips": "Формируют пары. Могут быть агрессивны в период нереста.",
                "breeding": "Охраняют мальков, образуя семью",
                "lifespan": "8-12 лет",
                "origin": "Южная Америка",
                "minTankSize": 300,
                "schoolSize": "2",
                "waterFlow": "Среднее",
                "lighting": "Среднее"
            },
            "oscar": {
                "name": "Астронотус (Оскар)",
                "scientificName": "Astronotus ocellatus",
                "size": "30-35 см",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "5-20°dH",
                "behavior": "Агрессивная крупная",
                "care": "Средний",
                "compatibility": ["oscar", "large-catfish"],
                "incompatible": ["all-small-fish"],
                "food": ["крупные гранулы", "рыба", "креветки", "черви"],
                "tips": "Очень крупные и умные рыбы. Узнают хозяина.",
                "breeding": "Откладывают икру на камни",
                "lifespan": "12-18 лет",
                "origin": "Южная Америка",
                "minTankSize": 500,
                "schoolSize": "1-2",
                "waterFlow": "Среднее",
                "lighting": "Яркое"
            },
            "ancistrus": {
                "name": "Анциструс",
                "scientificName": "Ancistrus cirrhosus",
                "size": "12-15 см",
                "temp": "20-28°C",
                "ph": "6.0-7.5",
                "gh": "2-20°dH",
                "behavior": "Мирная донная",
                "care": "Легкий",
                "compatibility": ["ancistrus", "all-peaceful-fish"],
                "incompatible": ["aggressive-cichlids"],
                "food": ["таблетки для сомов", "водоросли", "овощи"],
                "tips": "Отличные чистильщики стекол. Активны ночью.",
                "breeding": "Самец охраняет икру в укрытии",
                "lifespan": "6-10 лет",
                "origin": "Южная Америка",
                "minTankSize": 100,
                "schoolSize": "1",
                "waterFlow": "Среднее",
                "lighting": "Приглушенное"
            },
            "cory_bronze": {
                "name": "Коридорас бронзовый",
                "scientificName": "Corydoras aeneus",
                "size": "6-8 см",
                "temp": "20-26°C",
                "ph": "6.0-8.0",
                "gh": "5-18°dH",
                "behavior": "Мирная донная",
                "care": "Легкий",
                "compatibility": ["corydoras", "all-peaceful-fish"],
                "incompatible": ["aggressive-cichlids"],
                "food": ["тонущие таблетки", "живой корм", "остатки"],
                "tips": "Похожи на крапчатых, но более выносливы.",
                "breeding": "Клеят икру на стекла и растения",
                "lifespan": "5-8 лет",
                "origin": "Южная Америка",
                "minTankSize": 80,
                "schoolSize": "6+",
                "waterFlow": "Среднее",
                "lighting": "Любое"
            },
            "neon_black": {
                "name": "Неон черный",
                "scientificName": "Hyphessobrycon herbertaxelrodi",
                "size": "3-4 см",
                "temp": "22-26°C",
                "ph": "5.5-7.0",
                "gh": "2-15°dH",
                "behavior": "Мирная стайная",
                "care": "Легкий",
                "compatibility": ["neon", "tetra", "corydoras", "rasbora"],
                "incompatible": ["large-fish"],
                "food": ["микро корма", "дафния", "циклоп"],
                "tips": "Более выносливы чем голубые неоны.",
                "breeding": "Нерест в мягкой воде",
                "lifespan": "4-6 лет",
                "origin": "Южная Америка",
                "minTankSize": 40,
                "schoolSize": "10+",
                "waterFlow": "Слабое",
                "lighting": "Приглушенное"
            },
            "cherry_barb": {
                "name": "Барбус вишневый",
                "scientificName": "Puntius titteya",
                "size": "4-5 см",
                "temp": "22-26°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "behavior": "Мирная стайная",
                "care": "Легкий",
                "compatibility": ["cherry_barb", "neon", "rasbora", "corydoras"],
                "incompatible": ["aggressive-fish"],
                "food": ["мелкие хлопья", "живой корм", "растительный"],
                "tips": "Спокойнее других барбусов. Самцы краснеют при нересте.",
                "breeding": "Разбрасывают икру среди мелколистных растений",
                "lifespan": "4-6 лет",
                "origin": "Шри-Ланка",
                "minTankSize": 60,
                "schoolSize": "6+",
                "waterFlow": "Слабое",
                "lighting": "Среднее"
            },
            "zebra_danio": {
                "name": "Данио рерио зебра",
                "scientificName": "Danio rerio",
                "size": "5-6 см",
                "temp": "16-26°C",
                "ph": "6.0-8.0",
                "gh": "5-20°dH",
                "behavior": "Мирная очень активная",
                "care": "Очень легкий",
                "compatibility": ["danio", "barb", "rasbora", "corydoras"],
                "incompatible": ["slow-fish", "long-finned"],
                "food": ["хлопья", "живой корм", "замороженный"],
                "tips": "Самые выносливые аквариумные рыбы. Постоянно в движении.",
                "breeding": "Разбрасывают икру утром",
                "lifespan": "3-5 лет",
                "origin": "Индия",
                "minTankSize": 40,
                "schoolSize": "6+",
                "waterFlow": "Сильное",
                "lighting": "Яркое"
            },
            "glass_catfish": {
                "name": "Сом стеклянный",
                "scientificName": "Kryptopterus bicirrhis",
                "size": "12-15 см",
                "temp": "22-26°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "behavior": "Мирная стайная",
                "care": "Средний",
                "compatibility": ["glass_catfish", "angelfish", "discus", "corydoras"],
                "incompatible": ["aggressive-fish"],
                "food": ["живой корм", "замороженный", "тонущие гранулы"],
                "tips": "Прозрачные сомы. Держатся в толще воды стайкой.",
                "breeding": "Сложно в аквариуме",
                "lifespan": "6-8 лет",
                "origin": "Юго-Восточная Азия",
                "minTankSize": 150,
                "schoolSize": "5+",
                "waterFlow": "Среднее",
                "lighting": "Приглушенное"
            },
            "ram_cichlid": {
                "name": "Рамирези (Хромис-бабочка)",
                "scientificName": "Mikrogeophagus ramirezi",
                "size": "5-7 см",
                "temp": "26-30°C",
                "ph": "6.0-7.0",
                "gh": "5-12°dH",
                "behavior": "Мирная территориальная",
                "care": "Средний",
                "compatibility": ["ram_cichlid", "neon", "corydoras", "angelfish"],
                "incompatible": ["aggressive-cichlids"],
                "food": ["мелкие гранулы", "живой корм", "замороженный"],
                "tips": "Красивые карликовые цихлиды. Нужна теплая мягкая вода.",
                "breeding": "Формируют пары, охраняют потомство",
                "lifespan": "2-4 года",
                "origin": "Южная Америка",
                "minTankSize": 80,
                "schoolSize": "2",
                "waterFlow": "Слабое",
                "lighting": "Среднее"
            },
            "oto_catfish": {
                "name": "Отоцинклюс",
                "scientificName": "Otocinclus affinis",
                "size": "3-4 см",
                "temp": "20-26°C",
                "ph": "6.0-7.5",
                "gh": "2-15°dH",
                "behavior": "Мирная донная",
                "care": "Средний",
                "compatibility": ["oto_catfish", "neon", "guppy", "tetra", "shrimp"],
                "incompatible": ["aggressive-fish"],
                "food": ["водоросли", "таблетки", "овощи"],
                "tips": "Мелкие сомики-водорослееды. Чувствительны к качеству воды.",
                "breeding": "Редко размножаются в аквариуме",
                "lifespan": "3-5 лет",
                "origin": "Южная Америка",
                "minTankSize": 40,
                "schoolSize": "4+",
                "waterFlow": "Среднее",
                "lighting": "Среднее"
            },
            "honey_gourami": {
                "name": "Гурами медовый",
                "scientificName": "Trichogaster chuna",
                "size": "4-5 см",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "behavior": "Мирная спокойная",
                "care": "Легкий",
                "compatibility": ["honey_gourami", "neon", "corydoras", "rasbora"],
                "incompatible": ["aggressive-fish", "fin-nippers"],
                "food": ["хлопья", "живой корм", "замороженный"],
                "tips": "Мирные лабиринтовые рыбы. Дышат атмосферным воздухом.",
                "breeding": "Самец строит пенное гнездо",
                "lifespan": "4-6 лет",
                "origin": "Индия",
                "minTankSize": 60,
                "schoolSize": "2-3",
                "waterFlow": "Слабое",
                "lighting": "Среднее"
            },
            "cardinal_tetra": {
                "name": "Тетра кардинал красный",
                "scientificName": "Paracheirodon axelrodi",
                "size": "4-5 см",
                "temp": "23-27°C",
                "ph": "5.0-6.5",
                "gh": "1-8°dH",
                "behavior": "Мирная стайная",
                "care": "Средний",
                "compatibility": ["cardinal_tetra", "neon", "corydoras", "angelfish"],
                "incompatible": ["hard-water-fish"],
                "food": ["микро гранулы", "живой корм", "замороженный"],
                "tips": "Более яркие чем неоны. Полоса идет по всему телу.",
                "breeding": "Очень сложный нерест",
                "lifespan": "4-6 лет",
                "origin": "Южная Америка",
                "minTankSize": 60,
                "schoolSize": "8+",
                "waterFlow": "Слабое",
                "lighting": "Приглушенное"
            },
            "white_cloud": {
                "name": "Белоплавничка",
                "scientificName": "Tanichthys albonubes",
                "size": "3-4 см",
                "temp": "16-22°C",
                "ph": "6.0-8.0",
                "gh": "5-20°dH",
                "behavior": "Мирная стайная",
                "care": "Очень легкий",
                "compatibility": ["white_cloud", "danio", "goldfish"],
                "incompatible": ["tropical-fish"],
                "food": ["мелкие хлопья", "живой корм", "замороженный"],
                "tips": "Холодноводные рыбки. Неприхотливы к температуре.",
                "breeding": "Легко размножаются среди растений",
                "lifespan": "3-5 лет",
                "origin": "Китай",
                "minTankSize": 40,
                "schoolSize": "6+",
                "waterFlow": "Среднее",
                "lighting": "Яркое"
            },
            "endler_guppy": {
                "name": "Эндлера гуппи",
                "scientificName": "Poecilia wingei",
                "size": "2-3 см",
                "temp": "22-28°C",
                "ph": "7.0-8.5",
                "gh": "15-30°dH",
                "behavior": "Мирная активная",
                "care": "Очень легкий",
                "compatibility": ["endler_guppy", "guppy", "neon", "corydoras"],
                "incompatible": ["large-fish"],
                "food": ["микро хлопья", "живой корм", "замороженный"],
                "tips": "Миниатюрные родственники гуппи. Очень активные.",
                "breeding": "Живородящие, как гуппи",
                "lifespan": "2-3 года",
                "origin": "Венесуэла",
                "minTankSize": 20,
                "schoolSize": "5+",
                "waterFlow": "Слабое",
                "lighting": "Яркое"
            },
            "tiger_barb": {
                "name": "Барбус четырёхполосый",
                "scientificName": "Puntigrus tetrazona",
                "size": "6-7 см",
                "temp": "20-26°C",
                "ph": "6.0-8.0",
                "gh": "5-20°dH",
                "behavior": "Активная стайная",
                "care": "Легкий",
                "compatibility": ["tiger_barb", "danio", "rasbora"],
                "incompatible": ["long-finned", "slow-fish"],
                "food": ["хлопья", "гранулы", "живой корм"],
                "tips": "Очень активные. Могут щипать плавники других рыб.",
                "breeding": "Разбрасывают икру среди растений",
                "lifespan": "5-7 лет",
                "origin": "Юго-Восточная Азия",
                "minTankSize": 80,
                "schoolSize": "6+",
                "waterFlow": "Среднее",
                "lighting": "Яркое"
            },
            "kuhli_loach": {
                "name": "Вьюн кули",
                "scientificName": "Pangio kuhlii",
                "size": "8-10 см",
                "temp": "22-28°C",
                "ph": "5.5-7.0",
                "gh": "2-12°dH",
                "behavior": "Мирная донная",
                "care": "Средний",
                "compatibility": ["kuhli_loach", "neon", "guppy", "corydoras"],
                "incompatible": ["aggressive-fish"],
                "food": ["тонущие корма", "живой корм", "остатки"],
                "tips": "Змееобразные донные рыбы. Любят зарываться в песок.",
                "breeding": "Очень редко размножаются в аквариуме",
                "lifespan": "8-12 лет",
                "origin": "Юго-Восточная Азия",
                "minTankSize": 80,
                "schoolSize": "3+",
                "waterFlow": "Слабое",
                "lighting": "Приглушенное"
            }
        };

        // Расширенная база растений
        const plantDatabase = {
            "anubias": {
                "name": "Анубиас Бартера",
                "scientificName": "Anubias barteri",
                "light": "Слабое-среднее (20-40 мкмоль)",
                "co2": "Не обязательно",
                "temp": "22-28°C",
                "ph": "6.0-8.0",
                "gh": "3-15°dH",
                "growth": "Медленный",
                "care": "Очень легкий",
                "placement": "Передний/средний план",
                "fertilizer": "Жидкие удобрения раз в неделю",
                "tips": "Не закапывать корневище! Растет на корягах и камнях.",
                "propagation": "Деление корневища",
                "origin": "Западная Африка",
                "height": "10-30 см",
                "width": "15-40 см",
                "substrate": "Любой",
                "waterFlow": "Слабое-среднее"
            },
            "javaMoss": {
                "name": "Яванский мох",
                "scientificName": "Taxiphyllum barbieri",
                "light": "Слабое-сильное (10-50 мкмоль)",
                "co2": "Не обязательно",
                "temp": "20-30°C",
                "ph": "5.0-9.0",
                "gh": "2-20°dH",
                "growth": "Быстрый",
                "care": "Очень легкий",
                "placement": "Везде, на любых поверхностях",
                "fertilizer": "Не требует",
                "tips": "Универсальное растение для нерестовых аквариумов.",
                "propagation": "Деление, любой кусочек приживается",
                "origin": "Юго-Восточная Азия",
                "height": "3-10 см",
                "width": "Неограниченно",
                "substrate": "Не требует",
                "waterFlow": "Любое"
            },
            "vallisneria": {
                "name": "Валлиснерия спиральная",
                "scientificName": "Vallisneria spiralis",
                "light": "Среднее-сильное (40-60 мкмоль)",
                "co2": "Желательно",
                "temp": "22-28°C",
                "ph": "6.5-8.0",
                "gh": "8-20°dH",
                "growth": "Быстрый",
                "care": "Легкий",
                "placement": "Задний план",
                "fertilizer": "Корневые таблетки + жидкие",
                "tips": "Размножается побегами. Создает красивые заросли.",
                "propagation": "Дочерние растения на побегах",
                "origin": "Космополит",
                "height": "30-60 см",
                "width": "2-3 см",
                "substrate": "Питательный",
                "waterFlow": "Среднее-сильное"
            },
            "cryptocoryne": {
                "name": "Криптокорина Вендта",
                "scientificName": "Cryptocoryne wendtii",
                "light": "Слабое-среднее (25-45 мкмоль)",
                "co2": "Желательно",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "growth": "Медленный",
                "care": "Средний",
                "placement": "Передний/средний план",
                "fertilizer": "Корневые таблетки",
                "tips": "Подвержена криптокориновой болезни при резких изменениях.",
                "propagation": "Дочерние растения от корней",
                "origin": "Шри-Ланка",
                "height": "10-30 см",
                "width": "15-25 см",
                "substrate": "Питательный",
                "waterFlow": "Слабое"
            },
            "ludwigia": {
                "name": "Людвигия красная",
                "scientificName": "Ludwigia repens",
                "light": "Среднее-сильное (50-80 мкмоль)",
                "co2": "Обязательно",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "growth": "Быстрый",
                "care": "Средний-сложный",
                "placement": "Средний/задний план",
                "fertilizer": "Комплексные + железо",
                "tips": "Для красной окраски нужно яркое освещение и CO₂.",
                "propagation": "Черенкование верхушек",
                "origin": "Северная Америка",
                "height": "20-50 см",
                "width": "3-6 см",
                "substrate": "Питательный",
                "waterFlow": "Среднее"
            },
            "hornwort": {
                "name": "Роголистник",
                "scientificName": "Ceratophyllum demersum",
                "light": "Среднее-сильное (40-70 мкмоль)",
                "co2": "Не обязательно",
                "temp": "18-30°C",
                "ph": "6.0-8.0",
                "gh": "5-20°dH",
                "growth": "Очень быстрый",
                "care": "Очень легкий",
                "placement": "Плавающее или укорененное",
                "fertilizer": "Поглощает из воды",
                "tips": "Отличный потребитель нитратов. Подавляет водоросли.",
                "propagation": "Деление стебля",
                "origin": "Космополит",
                "height": "30-100 см",
                "width": "2-4 см",
                "substrate": "Не требует",
                "waterFlow": "Любое"
            },
            "amazonSword": {
                "name": "Эхинодорус Амазонка",
                "scientificName": "Echinodorus amazonicus",
                "light": "Среднее (40-60 мкмоль)",
                "co2": "Желательно",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "8-15°dH",
                "growth": "Средний",
                "care": "Легкий",
                "placement": "Центральный/задний план",
                "fertilizer": "Корневые таблетки обязательно",
                "tips": "Классическое растение для начинающих. Крупные листья.",
                "propagation": "Дочерние растения на цветочной стрелке",
                "origin": "Южная Америка",
                "height": "40-60 см",
                "width": "25-40 см",
                "substrate": "Питательный",
                "waterFlow": "Слабое-среднее"
            },
            "javaFern": {
                "name": "Папоротник Яванский",
                "scientificName": "Microsorum pteropus",
                "light": "Слабое-среднее (20-40 мкмоль)",
                "co2": "Не обязательно",
                "temp": "20-30°C",
                "ph": "6.0-8.0",
                "gh": "5-20°dH",
                "growth": "Медленный",
                "care": "Очень легкий",
                "placement": "Средний план",
                "fertilizer": "Жидкие удобрения",
                "tips": "Не закапывать корни! Привязывать к корягам.",
                "propagation": "Дочерние растения на листьях",
                "origin": "Юго-Восточная Азия",
                "height": "15-30 см",
                "width": "15-25 см",
                "substrate": "Не требует",
                "waterFlow": "Слабое-среднее"
            },
            "rotala": {
                "name": "Ротала круглолистная",
                "scientificName": "Rotala rotundifolia",
                "light": "Сильное (60-80 мкмоль)",
                "co2": "Обязательно",
                "temp": "22-28°C",
                "ph": "6.0-7.0",
                "gh": "5-12°dH",
                "growth": "Быстрый",
                "care": "Сложный",
                "placement": "Задний план",
                "fertilizer": "Комплексные + микроэлементы",
                "tips": "Капризное растение, требует стабильных условий.",
                "propagation": "Черенкование",
                "origin": "Юго-Восточная Азия",
                "height": "30-60 см",
                "width": "3-5 см",
                "substrate": "Питательный",
                "waterFlow": "Среднее"
            },
            "cabomba": {
                "name": "Кабомба каролинская",
                "scientificName": "Cabomba caroliniana",
                "light": "Сильное (70-100 мкмоль)",
                "co2": "Обязательно",
                "temp": "22-26°C",
                "ph": "6.0-7.0",
                "gh": "3-8°dH",
                "growth": "Быстрый",
                "care": "Сложный",
                "placement": "Задний план",
                "fertilizer": "Комплексные удобрения",
                "tips": "Очень требовательна к освещению и CO₂.",
                "propagation": "Черенкование верхушек",
                "origin": "Северная Америка",
                "height": "30-80 см",
                "width": "8-15 см",
                "substrate": "Мелкий, питательный",
                "waterFlow": "Слабое"
            },
            "limnophila": {
                "name": "Лимнофила сидячая",
                "scientificName": "Limnophila sessiliflora",
                "light": "Среднее-сильное (50-70 мкмоль)",
                "co2": "Желательно",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "growth": "Быстрый",
                "care": "Средний",
                "placement": "Задний план",
                "fertilizer": "Жидкие удобрения",
                "tips": "Хорошо переносит стрижку, быстро восстанавливается.",
                "propagation": "Черенкование",
                "origin": "Юго-Восточная Азия",
                "height": "30-60 см",
                "width": "5-10 см",
                "substrate": "Питательный",
                "waterFlow": "Среднее"
            },
            "hygrophila": {
                "name": "Гигрофила разнолистная",
                "scientificName": "Hygrophila difformis",
                "light": "Среднее (40-60 мкмоль)",
                "co2": "Желательно",
                "temp": "22-28°C",
                "ph": "6.0-8.0",
                "gh": "5-20°dH",
                "growth": "Быстрый",
                "care": "Легкий",
                "placement": "Средний/задний план",
                "fertilizer": "Жидкие удобрения",
                "tips": "Листья меняют форму в зависимости от освещения.",
                "propagation": "Черенкование, боковые побеги",
                "origin": "Юго-Восточная Азия",
                "height": "30-60 см",
                "width": "15-25 см",
                "substrate": "Любой",
                "waterFlow": "Слабое-среднее"
            },
            "elodea": {
                "name": "Элодея канадская",
                "scientificName": "Elodea canadensis",
                "light": "Среднее (40-60 мкмоль)",
                "co2": "Не обязательно",
                "temp": "18-24°C",
                "ph": "6.5-8.0",
                "gh": "8-20°dH",
                "growth": "Быстрый",
                "care": "Очень легкий",
                "placement": "Задний план или плавающая",
                "fertilizer": "Поглощает из воды",
                "tips": "Холодноводное растение, не любит высокие температуры.",
                "propagation": "Деление стебля",
                "origin": "Северная Америка",
                "height": "50-100 см",
                "width": "2-3 см",
                "substrate": "Не требует",
                "waterFlow": "Любое"
            },
            "alternanthera": {
                "name": "Альтернантера Рейнека",
                "scientificName": "Alternanthera reineckii",
                "light": "Сильное (70-100 мкмоль)",
                "co2": "Обязательно",
                "temp": "22-28°C",
                "ph": "6.0-7.0",
                "gh": "5-15°dH",
                "growth": "Средний",
                "care": "Сложный",
                "placement": "Средний план",
                "fertilizer": "Комплексные + железо",
                "tips": "Красивые красно-фиолетовые листья при ярком свете.",
                "propagation": "Черенкование",
                "origin": "Южная Америка",
                "height": "25-50 см",
                "width": "10-15 см",
                "substrate": "Питательный",
                "waterFlow": "Слабое-среднее"
            },
            "bacopa": {
                "name": "Бакопа каролинская",
                "scientificName": "Bacopa caroliniana",
                "light": "Среднее-сильное (50-70 мкмоль)",
                "co2": "Желательно",
                "temp": "20-28°C",
                "ph": "6.0-8.0",
                "gh": "5-20°dH",
                "growth": "Средний",
                "care": "Легкий-средний",
                "placement": "Средний/задний план",
                "fertilizer": "Жидкие удобрения",
                "tips": "Может расти как надводно, так и подводно.",
                "propagation": "Черенкование",
                "origin": "Северная Америка",
                "height": "30-60 см",
                "width": "3-6 см",
                "substrate": "Питательный",
                "waterFlow": "Слабое-среднее"
            },
            "marsilea": {
                "name": "Марсилия четырехлистная",
                "scientificName": "Marsilea crenata",
                "light": "Среднее (40-60 мкмоль)",
                "co2": "Желательно",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "growth": "Средний",
                "care": "Средний",
                "placement": "Передний план (ковер)",
                "fertilizer": "Корневые таблетки",
                "tips": "Образует красивый ковер из четырехлистников.",
                "propagation": "Побеги",
                "origin": "Австралия",
                "height": "5-15 см",
                "width": "Ковровое",
                "substrate": "Питательный",
                "waterFlow": "Слабое"
            },
            "riccia": {
                "name": "Риччия плавающая",
                "scientificName": "Riccia fluitans",
                "light": "Сильное (60-100 мкмоль)",
                "co2": "Обязательно",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "growth": "Быстрый",
                "care": "Средний-сложный",
                "placement": "Ковер или плавающая",
                "fertilizer": "Жидкие удобрения",
                "tips": "Для ковра нужно привязывать к сетке.",
                "propagation": "Деление слоевища",
                "origin": "Космополит",
                "height": "1-3 см",
                "width": "Ковровое",
                "substrate": "Не требует",
                "waterFlow": "Слабое"
            },
            "sagittaria": {
                "name": "Стрелолист шиловидный",
                "scientificName": "Sagittaria subulata",
                "light": "Среднее (40-60 мкмоль)",
                "co2": "Желательно",
                "temp": "20-28°C",
                "ph": "6.5-7.5",
                "gh": "8-20°dH",
                "growth": "Средний",
                "care": "Легкий",
                "placement": "Передний/средний план",
                "fertilizer": "Корневые таблетки",
                "tips": "Образует густые заросли тонких листьев.",
                "propagation": "Побеги",
                "origin": "Северная Америка",
                "height": "15-40 см",
                "width": "0.5-1 см",
                "substrate": "Питательный",
                "waterFlow": "Среднее"
            },
            "myriophyllum": {
                "name": "Уруть красностебельная",
                "scientificName": "Myriophyllum tuberculatum",
                "light": "Сильное (70-100 мкмоль)",
                "co2": "Обязательно",
                "temp": "22-28°C",
                "ph": "6.0-7.0",
                "gh": "5-12°dH",
                "growth": "Быстрый",
                "care": "Сложный",
                "placement": "Задний план",
                "fertilizer": "Комплексные + железо",
                "tips": "Красивые перистые листья, требует идеальных условий.",
                "propagation": "Черенкование",
                "origin": "Азия",
                "height": "30-80 см",
                "width": "8-15 см",
                "substrate": "Питательный",
                "waterFlow": "Среднее"
            },
            "glossostigma": {
                "name": "Глоссостигма повойничковая",
                "scientificName": "Glossostigma elatinoides",
                "light": "Очень сильное (80-120 мкмоль)",
                "co2": "Обязательно",
                "temp": "20-26°C",
                "ph": "6.0-7.0",
                "gh": "5-12°dH",
                "growth": "Средний",
                "care": "Очень сложный",
                "placement": "Передний план (ковер)",
                "fertilizer": "Комплексные удобрения",
                "tips": "Одно из самых требовательных ковровых растений.",
                "propagation": "Побеги",
                "origin": "Австралия, Новая Зеландия",
                "height": "2-6 см",
                "width": "Ковровое",
                "substrate": "Питательный, мелкий",
                "waterFlow": "Слабое"
            },
            "hemianthus": {
                "name": "Хемиантус куба",
                "scientificName": "Hemianthus callitrichoides",
                "light": "Очень сильное (80-120 мкмоль)",
                "co2": "Обязательно",
                "temp": "20-25°C",
                "ph": "6.0-7.0",
                "gh": "0-10°dH",
                "growth": "Медленный",
                "care": "Очень сложный",
                "placement": "Передний план (ковер)",
                "fertilizer": "Комплексные микроудобрения",
                "tips": "Мельчайший ковер, требует профессионального подхода.",
                "propagation": "Деление ковра",
                "origin": "Куба",
                "height": "1-3 см",
                "width": "Ковровое",
                "substrate": "Питательный, очень мелкий",
                "waterFlow": "Очень слабое"
            },
            "nymphoides": {
                "name": "Нимфоидес водный",
                "scientificName": "Nymphoides aquatica",
                "light": "Среднее-сильное (50-80 мкмоль)",
                "co2": "Желательно",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "growth": "Средний",
                "care": "Средний",
                "placement": "Средний план",
                "fertilizer": "Корневые таблетки",
                "tips": "Красивые сердцевидные листья, как у кувшинки.",
                "propagation": "Деление корневища",
                "origin": "Северная Америка",
                "height": "20-40 см",
                "width": "15-30 см",
                "substrate": "Питательный",
                "waterFlow": "Слабое"
            },
            "aponogeton": {
                "name": "Апоногетон волнистый",
                "scientificName": "Aponogeton undulatus",
                "light": "Среднее (40-60 мкмоль)",
                "co2": "Желательно",
                "temp": "22-28°C",
                "ph": "6.5-7.5",
                "gh": "8-20°dH",
                "growth": "Средний",
                "care": "Средний",
                "placement": "Центральный/задний план",
                "fertilizer": "Корневые таблетки",
                "tips": "Луковичное растение с волнистыми листьями.",
                "propagation": "Дочерние луковички",
                "origin": "Шри-Ланка",
                "height": "30-60 см",
                "width": "15-25 см",
                "substrate": "Питательный",
                "waterFlow": "Слабое-среднее"
            },
            "bucephalandra": {
                "name": "Буцефаландра",
                "scientificName": "Bucephalandra sp.",
                "light": "Слабое-среднее (20-50 мкмоль)",
                "co2": "Не обязательно",
                "temp": "22-28°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "growth": "Очень медленный",
                "care": "Средний",
                "placement": "Передний/средний план",
                "fertilizer": "Жидкие удобрения",
                "tips": "Эпифит, растет на корягах. Много различных видов.",
                "propagation": "Деление корневища",
                "origin": "Борнео",
                "height": "5-20 см",
                "width": "10-30 см",
                "substrate": "Не требует",
                "waterFlow": "Слабое-среднее"
            },
            "pogostemon": {
                "name": "Погостемон Хелфери",
                "scientificName": "Pogostemon helferi",
                "light": "Сильное (60-80 мкмоль)",
                "co2": "Обязательно",
                "temp": "22-28°C",
                "ph": "6.0-7.0",
                "gh": "5-15°dH",
                "growth": "Средний",
                "care": "Сложный",
                "placement": "Передний план",
                "fertilizer": "Комплексные удобрения",
                "tips": "Узнаваемые 'звездочки' из волнистых листьев.",
                "propagation": "Боковые побеги",
                "origin": "Таиланд",
                "height": "5-15 см",
                "width": "8-12 см",
                "substrate": "Питательный",
                "waterFlow": "Среднее"
            },
            "eleocharis": {
                "name": "Ситняг игольчатый",
                "scientificName": "Eleocharis acicularis",
                "light": "Среднее-сильное (50-80 мкмоль)",
                "co2": "Желательно",
                "temp": "18-26°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "growth": "Средний",
                "care": "Средний",
                "placement": "Передний план (ковер)",
                "fertilizer": "Корневые таблетки",
                "tips": "Образует густой травяной ковер.",
                "propagation": "Побеги",
                "origin": "Космополит",
                "height": "5-15 см",
                "width": "Ковровое",
                "substrate": "Питательный",
                "waterFlow": "Среднее"
            },
            "fissidens": {
                "name": "Фиссиденс благородный",
                "scientificName": "Fissidens nobilis",
                "light": "Слабое-среднее (30-50 мкмоль)",
                "co2": "Желательно",
                "temp": "20-26°C",
                "ph": "6.0-7.5",
                "gh": "5-15°dH",
                "growth": "Очень медленный",
                "care": "Сложный",
                "placement": "На корягах, камнях",
                "fertilizer": "Жидкие микроудобрения",
                "tips": "Редкий мох с необычной структурой листьев.",
                "propagation": "Деление",
                "origin": "Юго-Восточная Азия",
                "height": "3-8 см",
                "width": "5-15 см",
                "substrate": "Не требует",
                "waterFlow": "Слабое"
            },
            "pearlweed": {
                "name": "Хемиантус микрантемоидес",
                "scientificName": "Hemianthus micranthemoides",
                "light": "Сильное (60-100 мкмоль)",
                "co2": "Обязательно",
                "temp": "20-26°C",
                "ph": "6.0-7.0",
                "gh": "5-12°dH",
                "growth": "Быстрый",
                "care": "Сложный",
                "placement": "Передний/средний план",
                "fertilizer": "Комплексные удобрения",
                "tips": "Мелкие листочки, создает эффект 'жемчужной травы'.",
                "propagation": "Черенкование",
                "origin": "Северная Америка",
                "height": "10-30 см",
                "width": "15-25 см",
                "substrate": "Питательный",
                "waterFlow": "Среднее"
            },
            "hydrocotyle": {
                "name": "Гидрокотила белоголовая",
                "scientificName": "Hydrocotyle leucocephala",
                "light": "Среднее (40-60 мкмоль)",
                "co2": "Желательно",
                "temp": "22-28°C",
                "ph": "6.0-8.0",
                "gh": "5-20°dH",
                "growth": "Быстрый",
                "care": "Легкий",
                "placement": "Задний план или плавающая",
                "fertilizer": "Жидкие удобрения",
                "tips": "Круглые листья на длинных стеблях. Быстрорастущая.",
                "propagation": "Черенкование",
                "origin": "Южная Америка",
                "height": "30-80 см",
                "width": "5-8 см",
                "substrate": "Любой",
                "waterFlow": "Слабое-среднее"
            }
        };

        // Улучшенная таблица совместимости рыб
        const enhancedCompatibilityMatrix = {
            // Peaceful Community Fish
            'neon': {
                'neon': { level: 'excellent', reason: 'Стайные рыбы, лучше содержать группами от 10 особей' },
                'guppy': { level: 'good', reason: 'Мирные рыбы со схожими требованиями' },
                'corydoras': { level: 'excellent', reason: 'Идеальные соседи, разные зоны обитания' },
                'rasbora': { level: 'excellent', reason: 'Стайные рыбы с одинаковыми требованиями' },
                'tetra': { level: 'excellent', reason: 'Близкие родственники, прекрасно уживаются' },
                'platy': { level: 'good', reason: 'Мирные живородящие' },
                'molly': { level: 'good', reason: 'Спокойные соседи' },
                'betta': { level: 'caution', reason: 'Зависит от характера петушка, возможны конфликты' },
                'angelfish': { level: 'poor', reason: 'Скалярии могут поедать мелких неонов' },
                'barb': { level: 'poor', reason: 'Барбусы могут обкусывать плавники' },
                'oscar': { level: 'danger', reason: 'Крупные хищники, съедят неонов' }
            },

            'guppy': {
                'guppy': { level: 'excellent', reason: 'Стайные, лучше содержать группами' },
                'neon': { level: 'good', reason: 'Мирные рыбы со схожими требованиями' },
                'platy': { level: 'excellent', reason: 'Родственные виды, идеальные соседи' },
                'molly': { level: 'excellent', reason: 'Совместимы по поведению и требованиям' },
                'corydoras': { level: 'excellent', reason: 'Разные зоны обитания' },
                'rasbora': { level: 'good', reason: 'Мирные стайные рыбы' },
                'betta': { level: 'caution', reason: 'Самцы петушков могут атаковать ярких гуппи' },
                'angelfish': { level: 'poor', reason: 'Могут поедать мальков и мелких гуппи' }
            },

            // Semi-aggressive Fish
            'angelfish': {
                'angelfish': { level: 'caution', reason: 'Территориальные, нужен простор' },
                'corydoras': { level: 'excellent', reason: 'Разные зоны обитания' },
                'discus': { level: 'good', reason: 'Родственные виды с похожими требованиями' },
                'neon': { level: 'poor', reason: 'Могут поедать мелких рыб' },
                'guppy': { level: 'poor', reason: 'Рассматривают как добычу' },
                'barb': { level: 'poor', reason: 'Барбусы обкусывают длинные плавники' }
            },

            'betta': {
                'betta': { level: 'danger', reason: 'Самцы агрессивны друг к другу' },
                'corydoras': { level: 'excellent', reason: 'Мирные донные рыбы' },
                'neon': { level: 'caution', reason: 'Зависит от характера петушка' },
                'rasbora': { level: 'good', reason: 'Быстрые, редко конфликтуют' },
                'guppy': { level: 'caution', reason: 'Яркие плавники гуппи могут провоцировать' },
                'angelfish': { level: 'poor', reason: 'Конфликты за территорию' }
            },

            'barb': {
                'barb': { level: 'excellent', reason: 'Стайные, содержать группами от 6 особей' },
                'danio': { level: 'good', reason: 'Активные рыбы с похожим поведением' },
                'corydoras': { level: 'good', reason: 'Разные зоны обитания' },
                'neon': { level: 'poor', reason: 'Обкусывают плавники медлительным рыбам' },
                'guppy': { level: 'poor', reason: 'Обкусывают красивые плавники' },
                'betta': { level: 'poor', reason: 'Конфликты из-за агрессивности барбусов' },
                'angelfish': { level: 'poor', reason: 'Обкусывают длинные плавники' }
            }
        };

        // ============================================
        // PWA И ИНИЦИАЛИЗАЦИЯ
        // ============================================

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.getElementById('preloader').classList.add('hidden');
                initializeApp();
            }, 1500);
        });

        function initializeApp() {
            console.log('🚀 АкваСбор Pro инициализирован');

            // Инициализация системы подписок
            initializeSubscriptionSystem();

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('✅ Service Worker зарегистрирован');
                        showToast('✅ Приложение готово к работе офлайн');
                    })
                    .catch(error => {
                        console.log('❌ Ошибка Service Worker:', error);
                    });
            }

            setupNavigation();
            setupMyAquarium();
            setupFishCompatibility();
            setupPlantCompatibility();
            loadAquariumData();
            setupPWAInstall();
            setupSubscriptionButton();
        }

        // ============================================
        // СИСТЕМА ПОДПИСОК
        // ============================================

        function initializeSubscriptionSystem() {
            const savedSubscription = localStorage.getItem('aquariumSubscription');
            
            if (savedSubscription) {
                currentSubscription = JSON.parse(savedSubscription);
                currentSubscription.isActive = new Date(currentSubscription.expires) > new Date();
            } else {
                // Start trial period
                currentSubscription = {
                    plan: 'trial',
                    expires: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000), // 14 days
                    isActive: true
                };
                saveSubscription();
            }
            
            updateUIForSubscription();
        }

        function saveSubscription() {
            localStorage.setItem('aquariumSubscription', JSON.stringify(currentSubscription));
        }

        function updateUIForSubscription() {
            const proElements = document.querySelectorAll('.pro-feature');
            const trialElements = document.querySelectorAll('.trial-feature');
            
            if (currentSubscription.plan === 'pro' && currentSubscription.isActive) {
                proElements.forEach(el => el.style.display = 'block');
                trialElements.forEach(el => el.style.display = 'none');
                document.getElementById('subscriptionStatus').textContent = 'PRO версия активна';
            } else {
                proElements.forEach(el => el.style.display = 'none');
                trialElements.forEach(el => el.style.display = 'block');
                document.getElementById('subscriptionStatus').textContent = 'Пробная версия';
            }
        }

        function setupSubscriptionButton() {
            document.getElementById('upgradeBtn').addEventListener('click', showSubscriptionModal);
        }

        function showSubscriptionModal() {
            const modalHTML = `
                <div class="modal-overlay">
                    <div class="modal-content" style="max-width: 500px;">
                        <h3>💎 АкваСбор PRO</h3>
                        
                        <div class="subscription-plans" style="display: grid; gap: 20px; margin: 20px 0;">
                            <div class="plan-card" style="border: 2px solid #FFD700; border-radius: 10px; padding: 20px; background: #FFF9E6;">
                                <h4>${subscriptionPlans.pro.name}</h4>
                                <div class="price" style="font-size: 2rem; color: #159895; margin: 10px 0;">
                                    ${subscriptionPlans.pro.price} ₽
                                    <small style="font-size: 1rem; color: #666;">/месяц</small>
                                </div>
                                <ul style="text-align: left; margin: 15px 0;">
                                    ${subscriptionPlans.pro.features.map(feature => `<li>✅ ${feature}</li>`).join('')}
                                </ul>
                                <button class="btn btn-primary" onclick="purchaseSubscription('pro')" style="width: 100%; padding: 12px;">
                                    🛒 Купить PRO версию
                                </button>
                            </div>
                        </div>
                        
                        <div class="current-plan" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <h4>Текущий план</h4>
                            <p>${currentSubscription.plan === 'pro' ? 'PRO' : 'Пробная'} версия</p>
                            <p>Истекает: ${new Date(currentSubscription.expires).toLocaleDateString('ru-RU')}</p>
                        </div>
                        
                        <button class="btn btn-secondary" onclick="closeModal()" style="margin-top: 15px;">
                            Закрыть
                        </button>
                    </div>
                </div>
            `;
            
            const modal = document.createElement('div');
            modal.innerHTML = modalHTML;
            document.body.appendChild(modal);
        }

        function purchaseSubscription(plan) {
            // In a real app, this would integrate with a payment processor
            currentSubscription = {
                plan: plan,
                expires: new Date(Date.now() + subscriptionPlans[plan].duration * 24 * 60 * 60 * 1000),
                isActive: true
            };
            
            saveSubscription();
            updateUIForSubscription();
            closeModal();
            showToast(`🎉 Поздравляем! Вы получили ${subscriptionPlans[plan].name}!`);
        }

        // ============================================
        // УЛУЧШЕННАЯ СОВМЕСТИМОСТЬ РЫБ
        // ============================================

        function getEnhancedCompatibility(fish1Key, fish2Key) {
            if (fish1Key === fish2Key) {
                const fishData = fishDatabase[fish1Key];
                return {
                    level: fishData.behavior.includes('стайная') ? 'excellent' : 'caution',
                    symbol: fishData.behavior.includes('стайная') ? '✅' : '⚠️',
                    reason: fishData.behavior.includes('стайная') ? 
                        'Одинаковый вид, стайные рыбы' : 'Территориальные, могут конфликтовать'
                };
            }

            const compatibility = enhancedCompatibilityMatrix[fish1Key]?.[fish2Key] || 
                               enhancedCompatibilityMatrix[fish2Key]?.[fish1Key];

            if (compatibility) {
                const symbols = {
                    'excellent': '✅',
                    'good': '👍', 
                    'caution': '⚠️',
                    'poor': '❌',
                    'danger': '🚨'
                };
                
                return {
                    level: compatibility.level,
                    symbol: symbols[compatibility.level],
                    reason: compatibility.reason
                };
            }

            // Fallback: check basic compatibility
            const fish1 = fishDatabase[fish1Key];
            const fish2 = fishDatabase[fish2Key];
            
            if (!fish1 || !fish2) {
                return { level: 'unknown', symbol: '❓', reason: 'Неизвестная совместимость' };
            }

            // Basic compatibility rules
            if (fish1.behavior.includes('агрессивная') && fish2.behavior.includes('мирная')) {
                return { level: 'poor', symbol: '❌', reason: 'Агрессивная рыба с мирной' };
            }

            if (fish1.size !== fish2.size && Math.max(parseInt(fish1.size), parseInt(fish2.size)) > 
                Math.min(parseInt(fish1.size), parseInt(fish2.size)) * 2) {
                return { level: 'danger', symbol: '🚨', reason: 'Большая разница в размерах' };
            }

            return { level: 'caution', symbol: '⚠️', reason: 'Требуется наблюдение' };
        }

        // ============================================
        // ОСТАЛЬНЫЕ ФУНКЦИИ (сохранены из оригинального кода)
        // ============================================

        function showToast(message, duration = 3000) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, duration);
        }

        function setupPWAInstall() {
            let deferredPrompt;
            const installBtn = document.getElementById('installBtn');

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                installBtn.style.display = 'inline-block';
            });

            installBtn.addEventListener('click', async () => {
                if (!deferredPrompt) {
                    showToast('ℹ️ Приложение уже установлено');
                    return;
                }

                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;

                if (outcome === 'accepted') {
                    showToast('✅ Приложение установлено!');
                } else {
                    showToast('ℹ️ Установка отменена');
                }

                deferredPrompt = null;
                installBtn.style.display = 'none';
            });
        }

        function setupNavigation() {
            const navButtons = document.querySelectorAll('.nav-btn');
            const sections = document.querySelectorAll('.content-section');

            navButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetSection = btn.dataset.section;

                    navButtons.forEach(b => b.classList.remove('active'));
                    sections.forEach(s => s.classList.remove('active'));

                    btn.classList.add('active');
                    document.getElementById(targetSection).classList.add('active');

                    currentSection = targetSection;
                });
            });
        }

        function setupMyAquarium() {
            setupPhotoUpload();
            setupNotes();
            setupWaterTests();
            setupSpeciesManagement();
        }

        function setupPhotoUpload() {
            const photoInput = document.getElementById('photoInput');
            const takePhoto = document.getElementById('takePhoto');
            const uploadArea = document.querySelector('.photo-upload');

            photoInput.addEventListener('change', handlePhotoUpload);
            takePhoto.addEventListener('click', handleTakePhoto);

            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.backgroundColor = '#e8f5e8';
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.style.backgroundColor = '#f8fdff';
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.backgroundColor = '#f8fdff';

                const files = Array.from(e.dataTransfer.files);
                files.forEach(file => {
                    if (file.type.startsWith('image/')) {
                        processPhotoFile(file);
                    }
                });
            });
        }

        function handlePhotoUpload(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => processPhotoFile(file));
        }

        function handleTakePhoto() {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' } // Задняя камера на мобильных
                })
                .then(stream => {
                    createCameraModal(stream);
                })
                .catch(error => {
                    showToast('❌ Камера недоступна: ' + error.message);
                });
            } else {
                showToast('❌ Камера не поддерживается браузером');
            }
        }

        function createCameraModal(stream) {
            const modalHTML = `
                <div class="modal-overlay">
                    <div class="modal-content" style="max-width: 90vw;">
                        <h3>📷 Сделать фото аквариума</h3>
                        <video id="cameraVideo" autoplay style="width: 100%; max-width: 400px; border-radius: 8px;"></video>
                        <canvas id="photoCanvas" style="display: none;"></canvas>
                        <div style="margin-top: 15px;">
                            <button class="btn btn-primary" onclick="capturePhoto()">📸 Сделать снимок</button>
                            <button class="btn btn-secondary" onclick="closeCameraModal()">❌ Отмена</button>
                        </div>
                    </div>
                </div>
            `;

            const modal = document.createElement('div');
            modal.innerHTML = modalHTML;
            modal.id = 'cameraModal';
            document.body.appendChild(modal);

            const video = document.getElementById('cameraVideo');
            video.srcObject = stream;

            window.capturePhoto = function() {
                const canvas = document.getElementById('photoCanvas');
                const context = canvas.getContext('2d');

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0);

                canvas.toBlob(blob => {
                    processPhotoFile(blob, 'camera-' + Date.now() + '.jpg');
                    closeCameraModal();
                }, 'image/jpeg', 0.8);
            };

            window.closeCameraModal = function() {
                stream.getTracks().forEach(track => track.stop());
                document.getElementById('cameraModal').remove();
            };
        }

        function processPhotoFile(file, filename) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const photo = {
                    id: Date.now() + Math.random(),
                    data: e.target.result,
                    name: filename || file.name || 'photo.jpg',
                    date: new Date().toISOString(),
                    size: file.size || e.target.result.length
                };

                aquariumData.photos.push(photo);
                saveAquariumData();
                renderPhotoGallery();
                showToast('✅ Фото добавлено в галерею');
            };
            reader.readAsDataURL(file);
        }

        function renderPhotoGallery() {
            const gallery = document.getElementById('photoGallery');
            gallery.innerHTML = '';

            if (aquariumData.photos.length === 0) {
                gallery.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Фотографий пока нет. Загрузите первое фото!</p>';
                return;
            }

            aquariumData.photos.forEach(photo => {
                const photoItem = document.createElement('div');
                photoItem.className = 'photo-item';
                photoItem.innerHTML = `
                    <img src="${photo.data}" alt="${photo.name}">
                    <button class="photo-delete" onclick="deletePhoto('${photo.id}')">×</button>
                `;

                photoItem.addEventListener('click', (e) => {
                    if (!e.target.classList.contains('photo-delete')) {
                        openPhotoModal(photo);
                    }
                });

                gallery.appendChild(photoItem);
            });
        }

        function openPhotoModal(photo) {
            const modalHTML = `
                <div class="modal-overlay" onclick="closeModal()">
                    <div class="modal-content" style="max-width: 90vw;" onclick="event.stopPropagation()">
                        <h3>${photo.name}</h3>
                        <img src="${photo.data}" style="width: 100%; max-width: 600px; border-radius: 8px;">
                        <p style="color: #666; margin: 10px 0;">
                            Добавлено: ${new Date(photo.date).toLocaleDateString('ru-RU', {
                                year: 'numeric', month: 'long', day: 'numeric', 
                                hour: '2-digit', minute: '2-digit'
                            })}
                        </p>
                        <button class="btn btn-danger" onclick="deletePhoto('${photo.id}'); closeModal();">🗑️ Удалить фото</button>
                        <button class="btn btn-secondary" onclick="closeModal()">Закрыть</button>
                    </div>
                </div>
            `;

            const modal = document.createElement('div');
            modal.innerHTML = modalHTML;
            document.body.appendChild(modal);
        }

        function deletePhoto(photoId) {
            aquariumData.photos = aquariumData.photos.filter(p => p.id != photoId);
            saveAquariumData();
            renderPhotoGallery();
            showToast('🗑️ Фото удалено');
        }

        function setupNotes() {
            const addNote = document.getElementById('addNote');
            const noteInput = document.getElementById('noteInput');

            addNote.addEventListener('click', () => {
                const text = noteInput.value.trim();
                if (!text) {
                    showToast('⚠️ Введите текст заметки');
                    return;
                }

                const note = {
                    id: Date.now(),
                    text: text,
                    date: new Date().toISOString()
                };

                aquariumData.notes.push(note);
                saveAquariumData();
                renderNotes();
                noteInput.value = '';
                showToast('✅ Заметка добавлена');
            });

            noteInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.ctrlKey) {
                    addNote.click();
                }
            });
        }

        function renderNotes() {
            const notesList = document.getElementById('notesList');
            notesList.innerHTML = '';

            if (aquariumData.notes.length === 0) {
                notesList.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Заметок пока нет. Добавьте первую запись!</p>';
                return;
            }

            const sortedNotes = aquariumData.notes.sort((a, b) => new Date(b.date) - new Date(a.date));

            sortedNotes.forEach(note => {
                const noteItem = document.createElement('div');
                noteItem.className = 'note-item';

                const date = new Date(note.date).toLocaleDateString('ru-RU', {
                    year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });

                noteItem.innerHTML = `
                    <div class="note-date">${date}</div>
                    <div class="note-text">${note.text}</div>
                    <button class="btn btn-danger" style="padding: 5px 10px; font-size: 12px; margin-top: 10px;" onclick="deleteNote(${note.id})">🗑️ Удалить</button>
                `;

                notesList.appendChild(noteItem);
            });
        }

        function deleteNote(noteId) {
            aquariumData.notes = aquariumData.notes.filter(n => n.id !== noteId);
            saveAquariumData();
            renderNotes();
            showToast('🗑️ Заметка удалена');
        }

        function setupWaterTests() {
            const analyzeButton = document.getElementById('analyzeWater');
            analyzeButton.addEventListener('click', analyzeWaterParameters);

            const testInputs = document.querySelectorAll('.test-card input');
            testInputs.forEach(input => {
                input.addEventListener('input', updateIndividualResults);
            });
        }

        function updateIndividualResults() {
            updateBasicResults();
            updateNitrogenResults();
            updateHardnessResults();
            updateMicroResults();
            updateAdditionalResults();
        }

        function updateBasicResults() {
            const ph = parseFloat(document.getElementById('test-ph').value);
            const temp = parseFloat(document.getElementById('test-temp').value);
            const resultDiv = document.getElementById('basic-result');

            if (!ph && !temp) {
                resultDiv.style.display = 'none';
                return;
            }

            let status = 'good';
            let messages = [];

            if (ph) {
                if (ph < 6.0 || ph > 8.5) {
                    status = 'danger';
                    messages.push(`⚠️ pH ${ph} - критическое значение!`);
                } else if (ph < 6.5 || ph > 7.8) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚡ pH ${ph} - требует мониторинга`);
                } else {
                    messages.push(`✅ pH ${ph} - оптимально`);
                }
            }

            if (temp) {
                if (temp < 18 || temp > 32) {
                    status = 'danger';
                    messages.push(`🌡️ Температура ${temp}°C - ОПАСНО!`);
                } else if (temp < 22 || temp > 28) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`🌡️ Температура ${temp}°C - не идеально`);
                } else {
                    messages.push(`🌡️ Температура ${temp}°C - отлично`);
                }
            }

            resultDiv.className = `test-result ${status}`;
            resultDiv.innerHTML = messages.join('<br>');
            resultDiv.style.display = 'block';
        }

        function updateNitrogenResults() {
            const ammonia = parseFloat(document.getElementById('test-ammonia').value);
            const nitrites = parseFloat(document.getElementById('test-nitrites').value);
            const nitrates = parseFloat(document.getElementById('test-nitrates').value);
            const resultDiv = document.getElementById('nitrogen-result');

            if (!ammonia && nitrites !== 0 && !nitrites && !nitrates) {
                resultDiv.style.display = 'none';
                return;
            }

            let status = 'good';
            let messages = [];

            if (ammonia !== undefined && !isNaN(ammonia)) {
                if (ammonia > 0.25) {
                    status = 'danger';
                    messages.push(`🚨 NH₃/NH₄ ${ammonia} мг/л - КРИТИЧНО!`);
                } else if (ammonia > 0) {
                    status = 'warning';
                    messages.push(`⚠️ NH₃/NH₄ ${ammonia} мг/л - следы обнаружены`);
                } else {
                    messages.push(`✅ NH₃/NH₄ ${ammonia} мг/л - отлично`);
                }
            }

            if (nitrites !== undefined && !isNaN(nitrites)) {
                if (nitrites > 0.25) {
                    status = 'danger';
                    messages.push(`🚨 NO₂ ${nitrites} мг/л - ОПАСНО!`);
                } else if (nitrites > 0) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚠️ NO₂ ${nitrites} мг/л - есть нитриты`);
                } else {
                    messages.push(`✅ NO₂ ${nitrites} мг/л - отлично`);
                }
            }

            if (nitrates !== undefined && !isNaN(nitrates)) {
                if (nitrates > 50) {
                    status = status === 'good' ? 'danger' : status;
                    messages.push(`🚨 NO₃ ${nitrates} мг/л - критически высокие!`);
                } else if (nitrates > 25) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚠️ NO₃ ${nitrates} мг/л - повышены`);
                } else {
                    messages.push(`✅ NO₃ ${nitrates} мг/л - в норме`);
                }
            }

            resultDiv.className = `test-result ${status}`;
            resultDiv.innerHTML = messages.join('<br>');
            resultDiv.style.display = 'block';
        }

        function updateHardnessResults() {
            const gh = parseFloat(document.getElementById('test-gh').value);
            const kh = parseFloat(document.getElementById('test-kh').value);
            const resultDiv = document.getElementById('hardness-result');

            if (!gh && !kh) {
                resultDiv.style.display = 'none';
                return;
            }

            let status = 'good';
            let messages = [];

            if (gh !== undefined && !isNaN(gh)) {
                if (gh < 2 || gh > 25) {
                    status = 'warning';
                    messages.push(`⚠️ GH ${gh}°dH - экстремальное значение`);
                } else if (gh < 4 || gh > 16) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚡ GH ${gh}°dH - приемлемо`);
                } else {
                    messages.push(`✅ GH ${gh}°dH - оптимально`);
                }
            }

            if (kh !== undefined && !isNaN(kh)) {
                if (kh < 1 || kh > 15) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚠️ KH ${kh}°dH - проблемы с буферностью`);
                } else if (kh < 3 || kh > 10) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚡ KH ${kh}°dH - приемлемо`);
                } else {
                    messages.push(`✅ KH ${kh}°dH - отлично`);
                }
            }

            resultDiv.className = `test-result ${status}`;
            resultDiv.innerHTML = messages.join('<br>');
            resultDiv.style.display = 'block';
        }

        function updateMicroResults() {
            const phosphates = parseFloat(document.getElementById('test-phosphates').value);
            const iron = parseFloat(document.getElementById('test-iron').value);
            const copper = parseFloat(document.getElementById('test-copper').value);
            const resultDiv = document.getElementById('micro-result');

            if (!phosphates && !iron && !copper) {
                resultDiv.style.display = 'none';
                return;
            }

            let status = 'good';
            let messages = [];

            if (phosphates !== undefined && !isNaN(phosphates)) {
                if (phosphates > 1.0) {
                    status = 'danger';
                    messages.push(`🚨 PO₄ ${phosphates} мг/л - вспышка водорослей!`);
                } else if (phosphates > 0.5) {
                    status = 'warning';
                    messages.push(`⚠️ PO₄ ${phosphates} мг/л - повышены`);
                } else {
                    messages.push(`✅ PO₄ ${phosphates} мг/л - в норме`);
                }
            }

            if (iron !== undefined && !isNaN(iron)) {
                if (iron > 1.0) {
                    status = status === 'good' ? 'danger' : status;
                    messages.push(`🚨 Fe ${iron} мг/л - избыток, токсично!`);
                } else if (iron > 0.5) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚠️ Fe ${iron} мг/л - повышено`);
                } else if (iron < 0.05) {
                    messages.push(`⚡ Fe ${iron} мг/л - дефицит для растений`);
                } else {
                    messages.push(`✅ Fe ${iron} мг/л - оптимально`);
                }
            }

            if (copper !== undefined && !isNaN(copper)) {
                if (copper > 0.02) {
                    status = 'danger';
                    messages.push(`🚨 Cu ${copper} мг/л - ТОКСИЧНО ДЛЯ КРЕВЕТОК!`);
                } else if (copper > 0.0054) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚠️ Cu ${copper} мг/л - превышен EPA критерий`);
                } else {
                    messages.push(`✅ Cu ${copper} мг/л - безопасно`);
                }
            }

            resultDiv.className = `test-result ${status}`;
            resultDiv.innerHTML = messages.join('<br>');
            resultDiv.style.display = 'block';
        }

        function updateAdditionalResults() {
            const co2 = parseFloat(document.getElementById('test-co2').value);
            const oxygen = parseFloat(document.getElementById('test-oxygen').value);
            const salinity = parseFloat(document.getElementById('test-salinity').value);
            const resultDiv = document.getElementById('additional-result');

            if (!co2 && !oxygen && !salinity) {
                resultDiv.style.display = 'none';
                return;
            }

            let status = 'good';
            let messages = [];

            if (co2 !== undefined && !isNaN(co2)) {
                if (co2 > 35) {
                    status = 'danger';
                    messages.push(`🚨 CO₂ ${co2} мг/л - опасно для рыб!`);
                } else if (co2 < 15) {
                    messages.push(`⚡ CO₂ ${co2} мг/л - мало для растений`);
                } else {
                    messages.push(`✅ CO₂ ${co2} мг/л - оптимально`);
                }
            }

            if (oxygen !== undefined && !isNaN(oxygen)) {
                if (oxygen < 4) {
                    status = 'danger';
                    messages.push(`🚨 O₂ ${oxygen} мг/л - критически мало!`);
                } else if (oxygen < 6) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚠️ O₂ ${oxygen} мг/л - недостаточно`);
                } else {
                    messages.push(`✅ O₂ ${oxygen} мг/л - хорошо`);
                }
            }

            if (salinity !== undefined && !isNaN(salinity)) {
                if (salinity > 1 && salinity < 30) {
                    status = status === 'good' ? 'warning' : status;
                    messages.push(`⚠️ Соленость ${salinity}‰ - солоноватая вода`);
                } else if (salinity >= 30) {
                    messages.push(`🌊 Соленость ${salinity}‰ - морская вода`);
                } else {
                    messages.push(`💧 Соленость ${salinity}‰ - пресная вода`);
                }
            }

            resultDiv.className = `test-result ${status}`;
            resultDiv.innerHTML = messages.join('<br>');
            resultDiv.style.display = 'block';
        }

        function analyzeWaterParameters() {
            const testData = {
                ph: parseFloat(document.getElementById('test-ph').value) || null,
                temp: parseFloat(document.getElementById('test-temp').value) || null,
                ammonia: parseFloat(document.getElementById('test-ammonia').value) || null,
                nitrites: parseFloat(document.getElementById('test-nitrites').value) || null,
                nitrates: parseFloat(document.getElementById('test-nitrates').value) || null,
                gh: parseFloat(document.getElementById('test-gh').value) || null,
                kh: parseFloat(document.getElementById('test-kh').value) || null,
                phosphates: parseFloat(document.getElementById('test-phosphates').value) || null,
                iron: parseFloat(document.getElementById('test-iron').value) || null,
                copper: parseFloat(document.getElementById('test-copper').value) || null,
                co2: parseFloat(document.getElementById('test-co2').value) || null,
                oxygen: parseFloat(document.getElementById('test-oxygen').value) || null,
                salinity: parseFloat(document.getElementById('test-salinity').value) || null,
                date: new Date().toISOString()
            };

            const hasData = Object.values(testData).some(value => value !== null);

            if (!hasData) {
                showToast('⚠️ Заполните хотя бы один параметр для анализа');
                return;
            }

            aquariumData.waterTests.push(testData);
            saveAquariumData();
            generateFullAnalysis(testData);
            showToast('✅ Анализ воды сохранен в истории');
        }

        function generateFullAnalysis(testData) {
            let analysisHTML = `
                <div class="modal-overlay" onclick="closeModal()">
                    <div class="modal-content" style="max-width: 800px; max-height: 90vh;" onclick="event.stopPropagation()">
                        <h3>🔬 Результаты полного анализа воды</h3>
                        <div style="margin: 20px 0;">
                            <strong>Дата тестирования:</strong> ${new Date(testData.date).toLocaleDateString('ru-RU', {
                                year: 'numeric', month: 'long', day: 'numeric', 
                                hour: '2-digit', minute: '2-digit'
                            })}
                        </div>
            `;

            // Анализируем каждый параметр с экспертными советами
            if (testData.ammonia !== null && testData.ammonia > 0) {
                analysisHTML += `
                    <div class="warning-card">
                        <h4>⚠️ Обнаружен аммиак: ${testData.ammonia} мг/л</h4>
                        <p><strong>Причины:</strong> Перекорм, перенаселение, недостаточная биофильтрация</p>
                        <p><strong>Действия:</strong></p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Немедленная подмена 50% воды</li>
                            <li>Прекратить кормление на 1-2 дня</li>
                            <li>Усилить аэрацию</li>
                            <li>Добавить живые бактерии</li>
                        </ul>
                    </div>
                `;
            }

            if (testData.nitrites !== null && testData.nitrites > 0) {
                analysisHTML += `
                    <div class="warning-card">
                        <h4>⚠️ Обнаружены нитриты: ${testData.nitrites} мг/л</h4>
                        <p><strong>Опасность:</strong> Блокируют перенос кислорода в крови рыб</p>
                        <p><strong>Экстренные меры:</strong></p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Подмена 30-50% воды ежедневно</li>
                            <li>Добавить соль 1-2 г/л для защиты рыб</li>
                            <li>Не промывать фильтр</li>
                            <li>Контролировать ежедневно</li>
                        </ul>
                    </div>
                `;
            }

            // Рекомендации по совместимости с рыбами
            if (aquariumData.fish.length > 0) {
                analysisHTML += `<h4>🐠 Совместимость с вашими рыбами</h4>`;
                aquariumData.fish.forEach(fish => {
                    const fishData = fishDatabase[fish.key];
                    if (fishData) {
                        let compatibility = checkWaterCompatibility(testData, fishData);
                        analysisHTML += `
                            <div class="parameter-card">
                                <strong>${fishData.name}:</strong> ${compatibility.message}
                                ${compatibility.advice ? `<br><em>${compatibility.advice}</em>` : ''}
                            </div>
                        `;
                    }
                });
            }

            analysisHTML += `
                        <div style="margin-top: 20px;">
                            <button class="btn btn-primary" onclick="closeModal()">Понятно</button>
                        </div>
                    </div>
                </div>
            `;

            const modal = document.createElement('div');
            modal.innerHTML = analysisHTML;
            document.body.appendChild(modal);
        }

        function checkWaterCompatibility(testData, fishData) {
            let issues = [];

            if (testData.ph !== null) {
                const [minPH, maxPH] = fishData.ph.split('-').map(p => parseFloat(p));
                if (testData.ph < minPH || testData.ph > maxPH) {
                    issues.push(`pH не подходит (нужен ${fishData.ph})`);
                }
            }

            if (testData.temp !== null) {
                const [minTemp, maxTemp] = fishData.temp.split('-').map(t => parseFloat(t));
                if (testData.temp < minTemp || testData.temp > maxTemp) {
                    issues.push(`температура не подходит (нужна ${fishData.temp})`);
                }
            }

            if (issues.length === 0) {
                return { message: '✅ Параметры подходят', advice: null };
            } else {
                return { 
                    message: '⚠️ Есть проблемы: ' + issues.join(', '),
                    advice: 'Скорректируйте параметры для комфорта рыб'
                };
            }
        }

        function setupSpeciesManagement() {
            const addFish = document.getElementById('addFish');
            const addPlant = document.getElementById('addPlant');

            addFish.addEventListener('click', () => showSpeciesSelector('fish'));
            addPlant.addEventListener('click', () => showSpeciesSelector('plant'));
        }

        function showSpeciesSelector(type) {
            const database = type === 'fish' ? fishDatabase : plantDatabase;

            let modalHTML = `
                <div class="modal-overlay" onclick="closeModal()">
                    <div class="modal-content" style="max-width: 900px;" onclick="event.stopPropagation()">
                        <h3>Выберите ${type === 'fish' ? 'рыбу' : 'растение'} для добавления в аквариум</h3>
                        <div class="species-grid" style="margin: 20px 0;">
            `;

            Object.keys(database).forEach(key => {
                const species = database[key];
                modalHTML += `
                    <div class="species-card" onclick="addSpeciesToAquarium('${type}', '${key}')">
                        <h4>${species.name}</h4>
                        <div style="font-size: 12px; color: #666; margin: 5px 0;">
                            <em>${species.scientificName}</em>
                        </div>
                        <div class="species-info">
                            ${type === 'fish' ? 
                                `Размер: ${species.size}<br>Температура: ${species.temp}<br>pH: ${species.ph}<br>Уход: ${species.care}` :
                                `Освещение: ${species.light}<br>CO₂: ${species.co2}<br>Рост: ${species.growth}<br>Уход: ${species.care}`
                            }
                        </div>
                    </div>
                `;
            });

            modalHTML += `
                        </div>
                        <button class="btn btn-secondary" onclick="closeModal()">Закрыть</button>
                    </div>
                </div>
            `;

            const modal = document.createElement('div');
            modal.innerHTML = modalHTML;
            document.body.appendChild(modal);
        }

        function addSpeciesToAquarium(type, speciesKey) {
            const database = type === 'fish' ? fishDatabase : plantDatabase;
            const species = database[speciesKey];

            const item = {
                id: Date.now(),
                key: speciesKey,
                name: species.name,
                dateAdded: new Date().toISOString(),
                notes: ''
            };

            if (type === 'fish') {
                aquariumData.fish.push(item);
            } else {
                aquariumData.plants.push(item);
            }

            saveAquariumData();
            renderSpeciesLists();
            closeModal();
            showToast(`✅ ${species.name} добавлен${type === 'fish' ? 'а' : 'о'} в ваш аквариум`);
        }

        function renderSpeciesLists() {
            renderMyFish();
            renderMyPlants();
        }

        function renderMyFish() {
            const fishList = document.getElementById('myFishList');
            fishList.innerHTML = '';

            if (aquariumData.fish.length === 0) {
                fishList.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Рыб в аквариуме пока нет. Добавьте первых обитателей!</p>';
                return;
            }

            aquariumData.fish.forEach(fish => {
                const fishData = fishDatabase[fish.key];
                const fishCard = document.createElement('div');
                fishCard.className = 'species-card';
                fishCard.innerHTML = `
                    <h4>${fish.name}</h4>
                    <div style="font-size: 12px; color: #666; margin: 5px 0;">
                        <em>${fishData.scientificName}</em>
                    </div>
                    <div class="species-info">
                        Размер: ${fishData.size}<br>
                        Температура: ${fishData.temp}<br>
                        pH: ${fishData.ph}<br>
                        Продолжительность жизни: ${fishData.lifespan}<br>
                        Добавлена: ${new Date(fish.dateAdded).toLocaleDateString('ru-RU')}
                    </div>
                    <div style="margin-top: 10px;">
                        <button class="btn btn-info" style="padding: 5px 10px; font-size: 12px;" onclick="showSpeciesInfo('fish', '${fish.key}')">ℹ️ Подробнее</button>
                        <button class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="removeSpecies('fish', ${fish.id})">🗑️ Удалить</button>
                    </div>
                `;
                fishList.appendChild(fishCard);
            });
        }

        function renderMyPlants() {
            const plantList = document.getElementById('myPlantList');
            plantList.innerHTML = '';

            if (aquariumData.plants.length === 0) {
                plantList.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Растений в аквариуме пока нет. Создайте свой подводный сад!</p>';
                return;
            }

            aquariumData.plants.forEach(plant => {
                const plantData = plantDatabase[plant.key];
                const plantCard = document.createElement('div');
                plantCard.className = 'species-card';
                plantCard.innerHTML = `
                    <h4>${plant.name}</h4>
                    <div style="font-size: 12px; color: #666; margin: 5px 0;">
                        <em>${plantData.scientificName}</em>
                    </div>
                    <div class="species-info">
                        Освещение: ${plantData.light}<br>
                        CO₂: ${plantData.co2}<br>
                        Рост: ${plantData.growth}<br>
                        Размещение: ${plantData.placement}<br>
                        Добавлено: ${new Date(plant.dateAdded).toLocaleDateString('ru-RU')}
                    </div>
                    <div style="margin-top: 10px;">
                        <button class="btn btn-info" style="padding: 5px 10px; font-size: 12px;" onclick="showSpeciesInfo('plant', '${plant.key}')">ℹ️ Подробнее</button>
                        <button class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="removeSpecies('plant', ${plant.id})">🗑️ Удалить</button>
                    </div>
                `;
                plantList.appendChild(plantCard);
            });
        }

        function showSpeciesInfo(type, speciesKey) {
            const database = type === 'fish' ? fishDatabase : plantDatabase;
            const species = database[speciesKey];

            let modalHTML = `
                <div class="modal-overlay" onclick="closeModal()">
                    <div class="modal-content" onclick="event.stopPropagation()">
                        <h3>${species.name}</h3>
                        <div style="font-style: italic; color: #666; margin: 10px 0;">
                            ${species.scientificName} • ${species.origin}
                        </div>
            `;

            if (type === 'fish') {
                modalHTML += `
                    <div class="info-card">
                        <h4>📊 Основные параметры</h4>
                        <p><strong>Размер:</strong> ${species.size}</p>
                        <p><strong>Температура:</strong> ${species.temp}</p>
                        <p><strong>pH:</strong> ${species.ph}</p>
                        <p><strong>GH:</strong> ${species.gh}</p>
                        <p><strong>Поведение:</strong> ${species.behavior}</p>
                        <p><strong>Сложность содержания:</strong> ${species.care}</p>
                        <p><strong>Продолжительность жизни:</strong> ${species.lifespan}</p>
                    </div>

                    <div class="expert-advice">
                        <h4>🍽️ Кормление</h4>
                        <p><strong>Рацион:</strong> ${species.food.join(', ')}</p>
                        <p>${species.tips}</p>
                    </div>

                    <div class="info-card">
                        <h4>🐟 Разведение</h4>
                        <p>${species.breeding}</p>
                    </div>
                `;
            } else {
                modalHTML += `
                    <div class="info-card">
                        <h4>🌱 Параметры выращивания</h4>
                        <p><strong>Освещение:</strong> ${species.light}</p>
                        <p><strong>CO₂:</strong> ${species.co2}</p>
                        <p><strong>Температура:</strong> ${species.temp}</p>
                        <p><strong>pH:</strong> ${species.ph}</p>
                        <p><strong>GH:</strong> ${species.gh}</p>
                        <p><strong>Скорость роста:</strong> ${species.growth}</p>
                        <p><strong>Размещение:</strong> ${species.placement}</p>
                    </div>

                    <div class="expert-advice">
                        <h4>🌿 Уход и удобрения</h4>
                        <p><strong>Удобрения:</strong> ${species.fertilizer}</p>
                        <p>${species.tips}</p>
                    </div>

                    <div class="info-card">
                        <h4>🌱 Размножение</h4>
                        <p>${species.propagation}</p>
                    </div>
                `;
            }

            modalHTML += `
                        <button class="btn btn-primary" onclick="closeModal()">Закрыть</button>
                    </div>
                </div>
            `;

            const modal = document.createElement('div');
            modal.innerHTML = modalHTML;
            document.body.appendChild(modal);
        }

        function removeSpecies(type, id) {
            if (type === 'fish') {
                aquariumData.fish = aquariumData.fish.filter(f => f.id !== id);
            } else {
                aquariumData.plants = aquariumData.plants.filter(p => p.id !== id);
            }
            saveAquariumData();
            renderSpeciesLists();
            showToast('🗑️ Удалено из аквариума');
        }

        function setupFishCompatibility() {
            renderFishCompatibilityGrid();
        }

        function renderFishCompatibilityGrid() {
            const grid = document.getElementById('fishCompatibilityGrid');
            grid.innerHTML = '';

            Object.keys(fishDatabase).forEach(key => {
                const fish = fishDatabase[key];
                const fishCard = document.createElement('div');
                fishCard.className = 'species-card';
                fishCard.setAttribute('data-fish-key', key);

                fishCard.innerHTML = `
                    <h4>${fish.name}</h4>
                    <div style="font-size: 12px; color: #666; margin: 5px 0;">
                        <em>${fish.scientificName}</em>
                    </div>
                    <div class="species-info">
                        Размер: ${fish.size}<br>
                        Температура: ${fish.temp}<br>
                        Поведение: ${fish.behavior}<br>
                        Уход: ${fish.care}
                    </div>
                `;

                fishCard.addEventListener('click', () => {
                    // Проверка ограничений подписки
                    if (currentSubscription.plan === 'trial' && 
                        aquariumData.selectedFishForCompatibility && 
                        aquariumData.selectedFishForCompatibility.length >= subscriptionPlans.trial.limitations.maxFishCompatibility) {
                        showToast(`⚠️ Пробная версия позволяет проверять совместимость только ${subscriptionPlans.trial.limitations.maxFishCompatibility} рыб. Апгрейдните до PRO!`);
                        return;
                    }

                    toggleFishSelection(key, fishCard);
                });

                grid.appendChild(fishCard);
            });
        }

        function toggleFishSelection(fishKey, cardElement) {
            const selected = aquariumData.selectedFishForCompatibility || [];
            const index = selected.indexOf(fishKey);

            if (index > -1) {
                // Убираем из выбранных
                selected.splice(index, 1);
                cardElement.classList.remove('selected');
            } else {
                // Добавляем в выбранные
                selected.push(fishKey);
                cardElement.classList.add('selected');
            }

            aquariumData.selectedFishForCompatibility = selected;
            saveAquariumData();

            // Обновляем результаты совместимости
            if (selected.length >= 2) {
                showCompatibilityResults(selected);
            } else {
                document.getElementById('compatibilityResults').style.display = 'none';
            }
        }

        function showCompatibilityResults(selectedFish) {
            const resultsSection = document.getElementById('compatibilityResults');
            const matrixContainer = document.getElementById('compatibilityMatrix');
            const adviceContainer = document.getElementById('compatibilityAdvice');

            // Создаем таблицу совместимости
            let tableHTML = `
                <div class="compatibility-matrix">
                    <table class="compatibility-table">
                        <thead>
                            <tr>
                                <th>Рыба</th>
            `;

            selectedFish.forEach(fishKey => {
                tableHTML += `<th>${fishDatabase[fishKey].name}</th>`;
            });
            tableHTML += '</tr></thead><tbody>';

            selectedFish.forEach(fishKey1 => {
                tableHTML += `<tr><th>${fishDatabase[fishKey1].name}</th>`;
                selectedFish.forEach(fishKey2 => {
                    const compatibility = getEnhancedCompatibility(fishKey1, fishKey2);
                    tableHTML += `<td class="compat-${compatibility.level}">${compatibility.symbol}</td>`;
                });
                tableHTML += '</tr>';
            });

            tableHTML += '</tbody></table></div>';
            matrixContainer.innerHTML = tableHTML;

            // Генерируем советы
            let adviceHTML = '<h4>📋 Анализ совместимости и рекомендации</h4>';

            // Проверяем проблемные пары
            let issues = [];
            let warnings = [];
            let goodPairs = [];

            for (let i = 0; i < selectedFish.length; i++) {
                for (let j = i + 1; j < selectedFish.length; j++) {
                    const fish1 = selectedFish[i];
                    const fish2 = selectedFish[j];
                    const compat = getEnhancedCompatibility(fish1, fish2);

                    const fish1Name = fishDatabase[fish1].name;
                    const fish2Name = fishDatabase[fish2].name;

                    if (compat.level === 'poor' || compat.level === 'danger') {
                        issues.push({
                            pair: `${fish1Name} + ${fish2Name}`,
                            reason: compat.reason
                        });
                    } else if (compat.level === 'caution') {
                        warnings.push({
                            pair: `${fish1Name} + ${fish2Name}`,
                            reason: compat.reason
                        });
                    } else if (compat.level === 'excellent') {
                        goodPairs.push(`${fish1Name} + ${fish2Name}`);
                    }
                }
            }

            if (issues.length > 0) {
                adviceHTML += '<div class="warning-card"><h4>⚠️ Критические проблемы совместимости</h4><ul>';
                issues.forEach(issue => {
                    adviceHTML += `<li><strong>${issue.pair}:</strong> ${issue.reason}</li>`;
                });
                adviceHTML += '</ul></div>';
            }

            if (warnings.length > 0) {
                adviceHTML += '<div class="info-card"><h4>⚡ Требуют внимания</h4><ul>';
                warnings.forEach(warning => {
                    adviceHTML += `<li><strong>${warning.pair}:</strong> ${warning.reason}</li>`;
                });
                adviceHTML += '</ul></div>';
            }

            if (goodPairs.length > 0) {
                adviceHTML += '<div class="expert-advice"><h4>✅ Отличные сочетания</h4><p>' + goodPairs.join(', ') + '</p></div>';
            }

            // Добавляем общие советы по содержанию
            adviceHTML += `
                <div class="expert-advice">
                    <h4>🎯 Общие рекомендации для выбранных рыб</h4>
                    ${generateGeneralAdvice(selectedFish)}
                </div>
            `;

            adviceContainer.innerHTML = adviceHTML;
            resultsSection.style.display = 'block';
        }

        function generateGeneralAdvice(selectedFish) {
            let advice = '<ul class="tips-list">';

            // Анализ требований к воде
            let tempRanges = [];
            let phRanges = [];
            let behaviors = [];

            selectedFish.forEach(fishKey => {
                const fish = fishDatabase[fishKey];
                tempRanges.push(fish.temp);
                phRanges.push(fish.ph);
                behaviors.push(fish.behavior);
            });

            advice += '<li><strong>Параметры воды:</strong> Поддерживайте стабильные параметры, подходящие для всех видов</li>';

            if (behaviors.some(b => b.includes('стайная'))) {
                advice += '<li><strong>Стайные рыбы:</strong> Содержите стайных рыб группами от 6-8 особей</li>';
            }

            if (behaviors.some(b => b.includes('территориальная'))) {
                advice += '<li><strong>Территориальность:</strong> Предусмотрите укрытия и разделите территории</li>';
            }

            advice += '<li><strong>Кормление:</strong> Учитывайте пищевые потребности разных видов</li>';
            advice += '<li><strong>Размер аквариума:</strong> Обеспечьте достаточный объем для всех рыб</li>';
            advice += '</ul>';

            return advice;
        }

        function setupPlantCompatibility() {
            // Функционал уже настроен в HTML с помощью onclick
        }

        function findSuitablePlants() {
            const lighting = document.getElementById('plantLighting').value;
            const co2 = document.getElementById('plantCO2').value;
            const substrate = document.getElementById('plantSubstrate').value;
            const length = parseFloat(document.getElementById('tankLength').value) || 60;
            const width = parseFloat(document.getElementById('tankWidth').value) || 30;
            const height = parseFloat(document.getElementById('tankHeight').value) || 40;
            const ph = parseFloat(document.getElementById('tankPH').value) || 7.0;

            const suitablePlants = [];

            Object.keys(plantDatabase).forEach(plantKey => {
                const plant = plantDatabase[plantKey];
                let suitability = calculatePlantSuitability(plant, lighting, co2, substrate, ph);

                if (suitability.score > 50) {
                    suitablePlants.push({
                        key: plantKey,
                        plant: plant,
                        score: suitability.score,
                        advice: suitability.advice
                    });
                }
            });

            // Сортируем по подходящности
            suitablePlants.sort((a, b) => b.score - a.score);

            displayPlantRecommendations(suitablePlants);
            displayTankInfo(length, width, height);
        }

        function calculatePlantSuitability(plant, lighting, co2, substrate, ph) {
            let score = 70; // базовый балл
            let advice = [];

            // Проверка освещения
            if (lighting === 'low' && plant.light.includes('Слабое')) score += 20;
            else if (lighting === 'medium' && plant.light.includes('Среднее')) score += 20;
            else if (lighting === 'high' && plant.light.includes('Сильное')) score += 20;
            else if (lighting === 'very-high' && plant.light.includes('Очень сильное')) score += 20;
            else if (plant.light.includes('Слабое-сильное')) score += 15;
            else score -= 10;

            // Проверка CO₂
            if (plant.co2 === 'Не обязательно' && co2 === 'none') score += 15;
            else if (plant.co2 === 'Желательно' && co2 !== 'none') score += 15;
            else if (plant.co2 === 'Обязательно' && co2 === 'professional') score += 20;
            else if (plant.co2 === 'Обязательно' && co2 === 'none') {
                score -= 30;
                advice.push('Требуется система CO₂');
            }

            // Проверка грунта
            if (substrate === 'nutritive' || substrate === 'complete') score += 10;

            // Проверка pH
            const plantPH = plant.ph.split('-');
            const minPH = parseFloat(plantPH[0]);
            const maxPH = parseFloat(plantPH[1]);

            if (ph >= minPH && ph <= maxPH) score += 10;
            else {
                score -= 15;
                advice.push(`pH не подходит (нужен ${plant.ph})`);
            }

            return { score, advice };
        }

        function displayPlantRecommendations(suitablePlants) {
            const container = document.getElementById('plantRecommendations');
            container.innerHTML = '';

            if (suitablePlants.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Растения не найдены для указанных условий. Попробуйте изменить параметры.</p>';
                container.style.display = 'block';
                return;
            }

            suitablePlants.forEach(item => {
                const plantCard = document.createElement('div');
                plantCard.className = 'species-card';

                let scoreColor = '#4CAF50';
                if (item.score < 70) scoreColor = '#FF9800';
                if (item.score < 50) scoreColor = '#f44336';

                plantCard.innerHTML = `
                    <h4>${item.plant.name}</h4>
                    <div style="font-size: 12px; color: #666; margin: 5px 0;">
                        <em>${item.plant.scientificName}</em>
                    </div>
                    <div style="background: ${scoreColor}; color: white; padding: 5px 10px; border-radius: 15px; display: inline-block; margin: 5px 0; font-size: 12px; font-weight: bold;">
                        Подходящность: ${item.score}%
                    </div>
                    <div class="species-info">
                        Освещение: ${item.plant.light}<br>
                        CO₂: ${item.plant.co2}<br>
                        Рост: ${item.plant.growth}<br>
                        Размещение: ${item.plant.placement}
                    </div>
                    ${item.advice.length > 0 ? `
                        <div style="background: #fff3cd; padding: 8px; border-radius: 4px; margin-top: 10px; font-size: 12px;">
                            <strong>Рекомендации:</strong><br>
                            ${item.advice.join('<br>')}
                        </div>
                    ` : ''}
                    <button class="btn btn-secondary" style="margin-top: 10px; padding: 5px 10px; font-size: 12px;" onclick="showSpeciesInfo('plant', '${item.key}')">📖 Подробнее</button>
                `;

                container.appendChild(plantCard);
            });

            container.style.display = 'grid';
        }

        function displayTankInfo(length, width, height) {
            const infoContainer = document.getElementById('tankInfo');
            const volume = (length * width * height) / 1000;

            let infoHTML = `
                <h5>📊 Информация о вашем аквариуме</h5>
                <p><strong>Объем:</strong> ${volume.toFixed(0)} литров</p>
                <p><strong>Площадь дна:</strong> ${(length * width / 10000).toFixed(2)} м²</p>
                <p><strong>Рекомендации по растениям:</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px; font-size: 14px;">
                    <li>Передний план (до 15 см): почвопокровные растения</li>
                    <li>Средний план (15-30 см): кустовые растения</li>
                    <li>Задний план (30+ см): длинностебельные растения</li>
                </ul>
            `;

            infoContainer.innerHTML = infoHTML;
            infoContainer.style.display = 'block';
        }

        function calculateVolume() {
            const length = parseFloat(document.getElementById('calcLength').value);
            const width = parseFloat(document.getElementById('calcWidth').value);
            const height = parseFloat(document.getElementById('calcHeight').value);
            const resultDiv = document.getElementById('volumeResult');

            if (!length || !width || !height) {
                resultDiv.style.display = 'none';
                return;
            }

            const volume = (length * width * height) / 1000;
            const waterWeight = volume * 1.02; // примерный вес с учетом солей

            resultDiv.innerHTML = `
                <h5>📊 Результаты расчета</h5>
                <p><strong>Объем воды:</strong> ${volume.toFixed(1)} литров</p>
                <p><strong>Вес воды:</strong> ${waterWeight.toFixed(1)} кг</p>
                <p><strong>Общий вес аквариума:</strong> ~${(waterWeight + volume * 0.3).toFixed(1)} кг</p>

                <div class="expert-advice" style="margin-top: 15px;">
                    <h4>🔧 Рекомендации по установке</h4>
                    <p><strong>Тумба:</strong> Должна выдерживать ${Math.round(waterWeight * 1.5)} кг</p>
                    <p><strong>Пол:</strong> Нагрузка ${Math.round(waterWeight / ((length * width) / 10000))} кг/м²</p>
                    <p><strong>Стекло:</strong> Для аквариума ${height} см высотой нужно стекло ${calculateGlassThickness(height, length)} мм</p>
                </div>
            `;
            resultDiv.style.display = 'block';
        }

        function calculateGlassThickness(height, length) {
            if (height <= 30) return '6-8';
            if (height <= 40) return '8-10';
            if (height <= 50) return '10-12';
            if (height <= 60) return '12-15';
            return '15-19';
        }

        function calculateSubstrate() {
            const length = parseFloat(document.getElementById('substratLength').value);
            const width = parseFloat(document.getElementById('substratWidth').value);
            const depth = parseFloat(document.getElementById('substratDepth').value);
            const resultDiv = document.getElementById('substrateResult');

            if (!length || !width || !depth) {
                resultDiv.style.display = 'none';
                return;
            }

            const volumeCm = length * width * depth;
            const volumeLiters = volumeCm / 1000;
            const weightKg = volumeLiters * 1.6; // средняя плотность грунта

            resultDiv.innerHTML = `
                <h5>🏔️ Количество грунта</h5>
                <p><strong>Объем грунта:</strong> ${volumeLiters.toFixed(1)} литров</p>
                <p><strong>Примерный вес:</strong> ${weightKg.toFixed(1)} кг</p>

                <div class="expert-advice" style="margin-top: 15px;">
                    <h4>💡 Советы по грунту</h4>
                    <p><strong>Для растений:</strong> Питательная подложка + инертный грунт сверху</p>
                    <p><strong>Фракция:</strong> 2-4 мм для большинства растений</p>
                    <p><strong>Укладка:</strong> Более толстый слой у задней стенки создает перспективу</p>
                    <p><strong>Промывка:</strong> Промойте грунт до прозрачной воды</p>
                </div>
            `;
            resultDiv.style.display = 'block';
        }

        function calculateLighting() {
            const volume = parseFloat(document.getElementById('lightVolume').value);
            const type = document.getElementById('lightType').value;
            const resultDiv = document.getElementById('lightingResult');

            if (!volume) {
                resultDiv.style.display = 'none';
                return;
            }

            let wattPerLiter, parMin, parMax, description;

            switch (type) {
                case 'fish':
                    wattPerLiter = 0.2;
                    parMin = 10;
                    parMax = 30;
                    description = 'Минимальное освещение для рыб';
                    break;
                case 'easy-plants':
                    wattPerLiter = 0.4;
                    parMin = 20;
                    parMax = 40;
                    description = 'Неприхотливые растения';
                    break;
                case 'planted':
                    wattPerLiter = 0.7;
                    parMin = 40;
                    parMax = 60;
                    description = 'Средние требования растений';
                    break;
                case 'high-tech':
                    wattPerLiter = 1.0;
                    parMin = 60;
                    parMax = 100;
                    description = 'Требовательные растения';
                    break;
            }

            const totalWatts = volume * wattPerLiter;

            resultDiv.innerHTML = `
                <h5>💡 Расчет освещения</h5>
                <p><strong>Мощность LED:</strong> ${totalWatts.toFixed(0)} Вт</p>
                <p><strong>PAR на дне:</strong> ${parMin}-${parMax} мкмоль/м²/с</p>
                <p><strong>Тип освещения:</strong> ${description}</p>

                <div class="expert-advice" style="margin-top: 15px;">
                    <h4>🌈 Рекомендации</h4>
                    <p><strong>Спектр:</strong> 6000-6500K полный спектр</p>
                    <p><strong>Режим:</strong> 8-10 часов в день</p>
                    <p><strong>Размещение:</strong> 20-30 см над водой</p>
                    <p><strong>Диммирование:</strong> Рассвет/закат по 2 часа</p>
                </div>
            `;
            resultDiv.style.display = 'block';
        }

        function calculateWaterChange() {
            const volume = parseFloat(document.getElementById('changeVolume').value);
            const percent = parseFloat(document.getElementById('changePercent').value);
            const frequency = document.getElementById('changeFrequency').value;
            const resultDiv = document.getElementById('waterChangeResult');

            if (!volume || !percent) {
                resultDiv.style.display = 'none';
                return;
            }

            const changeVolume = (volume * percent) / 100;
            let frequencyText, monthlyVolume;

            switch (frequency) {
                case 'weekly':
                    frequencyText = 'еженедельно';
                    monthlyVolume = changeVolume * 4.3;
                    break;
                case 'biweekly':
                    frequencyText = 'раз в 2 недели';
                    monthlyVolume = changeVolume * 2.15;
                    break;
                case 'monthly':
                    frequencyText = 'ежемесячно';
                    monthlyVolume = changeVolume;
                    break;
            }

            resultDiv.innerHTML = `
                <h5>💧 График подмен воды</h5>
                <p><strong>За одну подмену:</strong> ${changeVolume.toFixed(1)} литров</p>
                <p><strong>Частота:</strong> ${frequencyText}</p>
                <p><strong>В месяц:</strong> ${monthlyVolume.toFixed(1)} литров</p>

                <div class="expert-advice" style="margin-top: 15px;">
                    <h4>🎯 Советы по подменам</h4>
                    <p><strong>Температура:</strong> ±2°C от аквариумной</p>
                    <p><strong>Дехлоратор:</strong> Используйте кондиционер для воды</p>
                    <p><strong>Сифонка:</strong> Чистите грунт сифоном</p>
                    <p><strong>Постепенность:</strong> Добавляйте воду медленно</p>
                </div>
            `;
            resultDiv.style.display = 'block';
        }

        function calculateGlass() {
            const height = parseFloat(document.getElementById('glassHeight').value);
            const length = parseFloat(document.getElementById('glassLength').value);
            const type = document.getElementById('glassType').value;
            const resultDiv = document.getElementById('glassResult');

            if (!height || !length) {
                resultDiv.style.display = 'none';
                return;
            }

            let thickness = calculateDetailedGlassThickness(height, length, type);
            let safetyFactor = type === 'reinforced' ? 0.8 : type === 'frameless' ? 1.3 : 1.0;

            resultDiv.innerHTML = `
                <h5>🏗️ Расчет стекла</h5>
                <p><strong>Рекомендуемая толщина:</strong> ${thickness} мм</p>
                <p><strong>Тип конструкции:</strong> ${getConstructionType(type)}</p>
                <p><strong>Коэффициент запаса:</strong> ${safetyFactor}</p>

                <div class="expert-advice" style="margin-top: 15px;">
                    <h4>🔨 Советы по сборке</h4>
                    <p><strong>Силикон:</strong> Только аквариумный, без фунгицидов</p>
                    <p><strong>Подготовка:</strong> Обезжирьте стекла спиртом</p>
                    <p><strong>Сборка:</strong> На ровной поверхности, контролируйте углы</p>
                    <p><strong>Сушка:</strong> Минимум 24 часа до заливки воды</p>
                    <p><strong>Тест:</strong> Проверьте на протечки перед запуском</p>
                </div>
            `;
            resultDiv.style.display = 'block';
        }

        function calculateDetailedGlassThickness(height, length, type) {
            let baseThickness = 6;

            if (height <= 30) baseThickness = 6;
            else if (height <= 40) baseThickness = 8;
            else if (height <= 50) baseThickness = 10;
            else if (height <= 60) baseThickness = 12;
            else if (height <= 70) baseThickness = 15;
            else baseThickness = 19;

            // Корректировка по длине
            if (length > 120) baseThickness += 2;
            if (length > 150) baseThickness += 2;

            // Корректировка по типу
            if (type === 'reinforced') baseThickness -= 2;
            if (type === 'frameless') baseThickness += 2;

            return Math.max(6, baseThickness);
        }

        function getConstructionType(type) {
            switch (type) {
                case 'standard': return 'Стандартная склейка';
                case 'reinforced': return 'С ребрами жесткости';
                case 'frameless': return 'Безрамная конструкция';
                default: return type;
            }
        }

        function calculateCabinet() {
            const volume = parseFloat(document.getElementById('cabinetVolume').value);
            const material = document.getElementById('cabinetMaterial').value;
            const resultDiv = document.getElementById('cabinetResult');

            if (!volume) {
                resultDiv.style.display = 'none';
                return;
            }

            const totalWeight = volume * 1.3; // вес воды + аквариум + декор
            const safetyWeight = totalWeight * 1.5; // запас прочности

            let materialInfo = getMaterialInfo(material);

            resultDiv.innerHTML = `
                <h5>🪑 Расчет тумбы</h5>
                <p><strong>Вес аквариума:</strong> ~${totalWeight.toFixed(0)} кг</p>
                <p><strong>Расчетная нагрузка:</strong> ${safetyWeight.toFixed(0)} кг</p>
                <p><strong>Материал:</strong> ${materialInfo.name}</p>

                <div class="expert-advice" style="margin-top: 15px;">
                    <h4>🏗️ Рекомендации по материалу</h4>
                    <p><strong>Преимущества:</strong> ${materialInfo.pros}</p>
                    <p><strong>Недостатки:</strong> ${materialInfo.cons}</p>
                    <p><strong>Конструкция:</strong> ${materialInfo.construction}</p>
                    <p><strong>Обработка:</strong> ${materialInfo.treatment}</p>
                </div>
            `;
            resultDiv.style.display = 'block';
        }

        function getMaterialInfo(material) {
            const materials = {
                chipboard: {
                    name: 'ДСП (древесно-стружечная плита)',
                    pros: 'Недорогая, легко обрабатывается',
                    cons: 'Боится влаги, может разбухнуть',
                    construction: 'Толщина от 18 мм, обязательна каркасная конструкция',
                    treatment: 'Влагостойкая пропитка, кромка ПВХ'
                },
                plywood: {
                    name: 'Фанера',
                    pros: 'Прочная, устойчива к влаге',
                    cons: 'Дороже ДСП, требует обработки',
                    construction: 'Березовая фанера от 15 мм, можно без каркаса',
                    treatment: 'Лакировка или пропитка влагостойким составом'
                },
                wood: {
                    name: 'Массив дерева',
                    pros: 'Максимальная прочность, красивый вид',
                    cons: 'Дорогой, требует профессиональной обработки',
                    construction: 'Брус 40x60 мм, доска от 20 мм',
                    treatment: 'Антисептик + лак или воск'
                },
                metal: {
                    name: 'Металлический каркас',
                    pros: 'Максимальная надежность, не боится влаги',
                    cons: 'Требует сварки, может ржаветь',
                    construction: 'Профильная труба 40x20 мм, полки из стекла/камня',
                    treatment: 'Грунтовка + порошковая покраска'
                }
            };
            return materials[material] || materials.chipboard;
        }

        function calculateDetailedLighting() {
            const length = parseFloat(document.getElementById('lightCalcLength').value);
            const width = parseFloat(document.getElementById('lightCalcWidth').value);
            const height = parseFloat(document.getElementById('lightCalcHeight').value);
            const type = document.getElementById('lightCalcType').value;
            const resultDiv = document.getElementById('detailedLightResult');

            if (!length || !width || !height) {
                resultDiv.style.display = 'none';
                return;
            }

            const volume = (length * width * height) / 1000;
            const area = (length * width) / 10000; // площадь в м²

            let lightingData = getDetailedLightingData(type);
            const totalWatts = volume * lightingData.wattPerLiter;
            const lightPerM2 = totalWatts / area;

            resultDiv.innerHTML = `
                <h5>💡 Детальный расчет освещения</h5>
                <p><strong>Объем:</strong> ${volume.toFixed(1)} л</p>
                <p><strong>Площадь:</strong> ${area.toFixed(2)} м²</p>
                <p><strong>Мощность LED:</strong> ${totalWatts.toFixed(0)} Вт (${lightingData.wattPerLiter} Вт/л)</p>
                <p><strong>Плотность:</strong> ${lightPerM2.toFixed(0)} Вт/м²</p>
                <p><strong>PAR на дне:</strong> ${lightingData.parMin}-${lightingData.parMax} мкмоль/м²/с</p>

                <div class="expert-advice" style="margin-top: 15px;">
                    <h4>🎯 Рекомендации для ${lightingData.typeName}</h4>
                    <p>${lightingData.description}</p>
                    <p><strong>Световой день:</strong> ${lightingData.photoperiod} часов</p>
                    <p><strong>Спектр:</strong> ${lightingData.spectrum}</p>
                    <p><strong>Размещение:</strong> ${lightingData.placement}</p>
                    <p><strong>Дополнительно:</strong> ${lightingData.additional}</p>
                </div>
            `;
            resultDiv.style.display = 'block';
        }

        function getDetailedLightingData(type) {
            const lightingTypes = {
                'fish-only': {
                    typeName: 'аквариума только с рыбами',
                    wattPerLiter: 0.15,
                    parMin: 10,
                    parMax: 25,
                    photoperiod: '6-8',
                    spectrum: '6000-8000K белый свет',
                    placement: 'Любое расположение светильников',
                    description: 'Минимальное освещение для наблюдения за рыбами',
                    additional: 'Можно использовать простые LED панели'
                },
                'low-light': {
                    typeName: 'теневыносливых растений',
                    wattPerLiter: 0.3,
                    parMin: 20,
                    parMax: 40,
                    photoperiod: '8-10',
                    spectrum: '6500K полный спектр',
                    placement: 'Равномерное распределение по площади',
                    description: 'Анубиас, яванский мох, криптокорины',
                    additional: 'CO₂ не обязательно, жидкие удобрения'
                },
                'medium-light': {
                    typeName: 'растений со средними требованиями',
                    wattPerLiter: 0.5,
                    parMin: 40,
                    parMax: 60,
                    photoperiod: '8-10',
                    spectrum: '6000-6500K + красный/синий',
                    placement: 'Планки LED с хорошим покрытием',
                    description: 'Валлиснерия, эхинодорусы, большинство растений',
                    additional: 'Желательно CO₂, регулярные удобрения'
                },
                'high-light': {
                    typeName: 'светолюбивых растений',
                    wattPerLiter: 0.8,
                    parMin: 60,
                    parMax: 80,
                    photoperiod: '8-9',
                    spectrum: 'Полный спектр с пиками красного/синего',
                    placement: 'Мощные светильники, возможно несколько',
                    description: 'Людвигии, роталы, альтернантеры',
                    additional: 'Обязательно CO₂, полный набор удобрений'
                },
                'carpet': {
                    typeName: 'почвопокровных растений',
                    wattPerLiter: 1.0,
                    parMin: 80,
                    parMax: 120,
                    photoperiod: '6-8',
                    spectrum: 'Интенсивный полный спектр',
                    placement: 'Максимально близко к поверхности воды',
                    description: 'Глоссостигма, хемиантус куба, элеохарис',
                    additional: 'Высокое давление CO₂, ежедневные удобрения'
                }
            };
            return lightingTypes[type] || lightingTypes['medium-light'];
        }

        function quickWaterTest() {
            const testStrip = document.getElementById('test-strip').value;
            const waterClarity = document.getElementById('water-clarity').value;
            const waterSmell = document.getElementById('water-smell').value;

            if (!testStrip && !waterClarity && !waterSmell) {
                showToast('⚠️ Заполните хотя бы один параметр для быстрого анализа');
                return;
            }

            let analysisHTML = `
                <div class="modal-overlay" onclick="closeModal()">
                    <div class="modal-content" onclick="event.stopPropagation()">
                        <h3>🚀 Результаты быстрого анализа воды</h3>
            `;

            if (testStrip) {
                analysisHTML += `<div class="parameter-card"><strong>Тест-полоски:</strong> ${getTestStripResult(testStrip)}</div>`;
            }

            if (waterClarity) {
                analysisHTML += `<div class="parameter-card"><strong>Прозрачность:</strong> ${getWaterClarityResult(waterClarity)}</div>`;
            }

            if (waterSmell) {
                analysisHTML += `<div class="parameter-card"><strong>Запах:</strong> ${getWaterSmellResult(waterSmell)}</div>`;
            }

            analysisHTML += `
                        <div class="expert-advice">
                            <h4>💡 Рекомендации</h4>
                            <p>Для более точного анализа используйте капельные тесты и введите результаты в основную панель тестирования.</p>
                        </div>
                        <button class="btn btn-primary" onclick="closeModal()">Понятно</button>
                    </div>
                </div>
            `;

            const modal = document.createElement('div');
            modal.innerHTML = analysisHTML;
            document.body.appendChild(modal);
        }

        function getTestStripResult(value) {
            const results = {
                'perfect': '✅ Идеальные параметры - продолжайте в том же духе!',
                'good': '👍 Хорошие показатели - небольшие отклонения в норме',
                'warning': '⚠️ Требует внимания - проверьте основные параметры',
                'danger': '🚨 Критично - срочно примите меры!'
            };
            return results[value] || 'Неизвестный результат';
        }

        function getWaterClarityResult(value) {
            const results = {
                'crystal': '✅ Кристально чистая - отличное качество воды',
                'slightly-hazy': '⚡ Слегка мутная - возможно, бактериальная вспышка',
                'hazy': '⚠️ Мутная - требуется подмена воды и проверка фильтра',
                'very-hazy': '🚨 Очень мутная - срочные меры: подмена 50% воды'
            };
            return results[value] || 'Неизвестная прозрачность';
        }

        function getWaterSmellResult(value) {
            const results = {
                'neutral': '✅ Нейтральный - вода в отличном состоянии',
                'earthy': '🌿 Землистый - норма для аквариума с растениями',
                'rotten': '🚨 Гнилостный - признак разложения органики, опасно!',
                'chemical': '⚗️ Химический - возможно, передозировка кондиционера'
            };
            return results[value] || 'Неизвестный запах';
        }

        function saveAquariumData() {
            try {
                localStorage.setItem('aquariumData', JSON.stringify(aquariumData));
            } catch (error) {
                console.error('Ошибка сохранения данных:', error);
                showToast('⚠️ Ошибка сохранения данных');
            }
        }

        function loadAquariumData() {
            renderPhotoGallery();
            renderNotes();
            renderSpeciesLists();
            updateIndividualResults();

            // Восстанавливаем выбранных рыб для совместимости
            if (aquariumData.selectedFishForCompatibility) {
                aquariumData.selectedFishForCompatibility.forEach(fishKey => {
                    const card = document.querySelector(`[data-fish-key="${fishKey}"]`);
                    if (card) {
                        card.classList.add('selected');
                    }
                });

                if (aquariumData.selectedFishForCompatibility.length >= 2) {
                    showCompatibilityResults(aquariumData.selectedFishForCompatibility);
                }
            }
        }

        // Глобальные функции для HTML onclick событий
        window.closeModal = function() {
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => modal.remove());
        };

        window.addSpeciesToAquarium = addSpeciesToAquarium;
        window.deletePhoto = deletePhoto;
        window.deleteNote = deleteNote;
        window.removeSpecies = removeSpecies;
        window.showSpeciesInfo = showSpeciesInfo;
        window.findSuitablePlants = findSuitablePlants;
        window.calculateVolume = calculateVolume;
        window.calculateSubstrate = calculateSubstrate;
        window.calculateLighting = calculateLighting;
        window.calculateWaterChange = calculateWaterChange;
        window.calculateGlass = calculateGlass;
        window.calculateCabinet = calculateCabinet;
        window.calculateDetailedLighting = calculateDetailedLighting;
        window.quickWaterTest = quickWaterTest;
        window.purchaseSubscription = purchaseSubscription;

</script>
<script>
// ============================================
// УЛУЧШЕННАЯ СИСТЕМА ПОДПИСОК С АДМИНКОЙ
// ============================================

// Конфигурация подписок
const subscriptionPlans = {
    trial: {
        name: 'Пробная версия',
        price: 0,
        duration: 14,
        features: [
            'Базовый анализ воды',
            'Совместимость до 3 рыб',
            'Ограниченная база растений',
            'Реклама в приложении'
        ],
        limitations: {
            maxFishCompatibility: 3,
            advancedTests: false,
            expertAdvice: false,
            offlineMode: false
        }
    },
    pro: {
        name: 'PRO версия',
        price: 399,
        duration: 30,
        features: [
            'Расширенный анализ воды',
            'Неограниченная совместимость рыб',
            'Полная база растений и рыб',
            'Экспертные советы и рекомендации',
            'Работа офлайн',
            'Приоритетная поддержка',
            'Экспорт данных'
        ],
        limitations: {
            maxFishCompatibility: null,
            advancedTests: true,
            expertAdvice: true,
            offlineMode: true
        }
    }
};

// Глобальные переменные для подписок
let subscriptionRequests = JSON.parse(localStorage.getItem('subscriptionRequests')) || [];
let adminSubscriptions = JSON.parse(localStorage.getItem('adminSubscriptions')) || [];
let currentSubscription = JSON.parse(localStorage.getItem('currentSubscription')) || {
    plan: 'trial',
    expires: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString(),
    isActive: true
};

// ============================================
// ФУНКЦИИ ДЛЯ РАБОТЫ С ПОДПИСКАМИ
// ============================================

function saveSubscription() {
    localStorage.setItem('currentSubscription', JSON.stringify(currentSubscription));
}

function saveSubscriptionRequests() {
    localStorage.setItem('subscriptionRequests', JSON.stringify(subscriptionRequests));
}

function saveAdminSubscriptions() {
    localStorage.setItem('adminSubscriptions', JSON.stringify(adminSubscriptions));
}

function updateUIForSubscription() {
    const proElements = document.querySelectorAll('.pro-feature');
    const trialElements = document.querySelectorAll('.trial-feature');
    
    if (currentSubscription.plan === 'pro' && currentSubscription.isActive) {
        proElements.forEach(el => el.style.display = 'block');
        trialElements.forEach(el => el.style.display = 'none');
        document.getElementById('subscriptionStatus').textContent = 'PRO версия активна';
        document.getElementById('upgradeBtn').style.display = 'none';
    } else {
        proElements.forEach(el => el.style.display = 'none');
        trialElements.forEach(el => el.style.display = 'block');
        document.getElementById('subscriptionStatus').textContent = 'Пробная версия';
        document.getElementById('upgradeBtn').style.display = 'inline-block';
    }
}

// ============================================
// СИСТЕМА ПОКУПКИ ПОДПИСКИ
// ============================================

function showSubscriptionModal() {
    const modalHTML = `
        <div class="modal-overlay">
            <div class="modal-content" style="max-width: 500px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">💎 АкваСбор PRO - Оформление заявки</h3>
                    <button onclick="closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
                </div>
                
                <div class="subscription-plans" style="display: grid; gap: 20px; margin: 20px 0;">
                    <div class="plan-card" style="border: 2px solid #FFD700; border-radius: 10px; padding: 20px; background: #FFF9E6;">
                        <h4 style="margin: 0 0 10px 0;">${subscriptionPlans.pro.name}</h4>
                        <div class="price" style="font-size: 2rem; color: #159895; margin: 10px 0;">
                            ${subscriptionPlans.pro.price} ₽
                            <small style="font-size: 1rem; color: #666;">/месяц</small>
                        </div>
                        <ul style="text-align: left; margin: 15px 0; padding-left: 20px;">
                            ${subscriptionPlans.pro.features.map(feature => `<li style="margin-bottom: 8px;">✅ ${feature}</li>`).join('')}
                        </ul>
                        
                        <div class="subscription-form" style="margin-top: 20px;">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Ваше имя:</label>
                                <input type="text" id="subscriptionName" class="form-input" 
                                       style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;"
                                       placeholder="Введите ваше имя" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Номер телефона:</label>
                                <input type="tel" id="subscriptionPhone" class="form-input"
                                       style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px;"
                                       placeholder="+7 (XXX) XXX-XX-XX" required>
                            </div>
                        </div>
                        
                        <div class="payment-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <h4 style="margin: 0 0 10px 0;">💳 Способ оплаты</h4>
                            <p style="margin: 5px 0;"><strong>Перевод на Сбербанк:</strong></p>
                            <p style="font-size: 1.2rem; font-weight: bold; color: #159895; margin: 10px 0;">8952 200 39 90</p>
                            <p style="font-size: 0.9rem; color: #666; margin: 0;">После оплаты отправьте чек в поддержку для подтверждения</p>
                        </div>
                        
                        <button class="btn btn-primary" onclick="submitSubscriptionRequest()" style="width: 100%; padding: 12px; margin-bottom: 10px;">
                            📝 Отправить заявку на PRO
                        </button>
                    </div>
                </div>
                
                <div class="current-plan" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    <h4 style="margin: 0 0 10px 0;">Текущий план</h4>
                    <p style="margin: 5px 0;">${currentSubscription.plan === 'pro' ? 'PRO' : 'Пробная'} версия</p>
                    <p style="margin: 5px 0;">Истекает: ${new Date(currentSubscription.expires).toLocaleDateString('ru-RU')}</p>
                </div>
            </div>
        </div>
    `;
    
    const modal = document.createElement('div');
    modal.innerHTML = modalHTML;
    document.body.appendChild(modal);
}

function submitSubscriptionRequest() {
    const nameInput = document.getElementById('subscriptionName');
    const phoneInput = document.getElementById('subscriptionPhone');
    
    if (!nameInput || !phoneInput) {
        showToast('Ошибка формы. Попробуйте еще раз.', 'error');
        return;
    }
    
    const name = nameInput.value.trim();
    const phone = phoneInput.value.trim();
    
    if (!name || !phone) {
        showToast('⚠️ Заполните все поля формы', 'error');
        return;
    }
    
    // Создаем заявку
    const request = {
        id: Date.now(),
        name: name,
        phone: phone,
        plan: 'pro',
        price: subscriptionPlans.pro.price,
        date: new Date().toISOString(),
        status: 'pending',
        paymentConfirmed: false,
        adminId: 'admin_' + Date.now()
    };
    
    subscriptionRequests.push(request);
    adminSubscriptions.push(request);
    
    saveSubscriptionRequests();
    saveAdminSubscriptions();
    
    // Отправляем уведомление на почту
    sendEmailNotification(request);
    
    closeModal();
    showToast('✅ Заявка отправлена! Мы свяжемся с вами после проверки оплаты.', 'success');
}

function sendEmailNotification(request) {
    // В реальном приложении здесь был бы AJAX запрос к серверу
    const emailData = {
        to: 'artcopy78@bk.ru',
        subject: 'Новая заявка на подписку АкваСбор PRO',
        body: `
            Новая заявка на подписку:
            
            Имя: ${request.name}
            Телефон: ${request.phone}
            План: ${request.plan}
            Стоимость: ${request.price} руб.
            Дата: ${new Date(request.date).toLocaleDateString('ru-RU')}
            ID заявки: ${request.id}
            
            Для подтверждения перейдите в админ-панель.
        `
    };
    
    console.log('Уведомление отправлено на почту:', emailData);
    // Здесь должен быть реальный код отправки email
}

// ============================================
// АДМИН-ПАНЕЛЬ
// ============================================

function showAdminPanel() {
    const pendingRequests = adminSubscriptions.filter(req => req.status === 'pending');
    const approvedRequests = adminSubscriptions.filter(req => req.status === 'approved');
    const rejectedRequests = adminSubscriptions.filter(req => req.status === 'rejected');
    
    const modalHTML = `
        <div class="modal-overlay">
            <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">👑 Админ-панель АкваСбор</h3>
                    <button onclick="closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
                </div>
                <p style="color: #666; margin-bottom: 20px;">Управление заявками на подписку</p>
                
                <div class="admin-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                    <div class="stat-card" style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 2rem;">${pendingRequests.length}</div>
                        <div>Ожидают</div>
                    </div>
                    <div class="stat-card" style="background: #d4edda; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 2rem;">${approvedRequests.length}</div>
                        <div>Подтверждены</div>
                    </div>
                    <div class="stat-card" style="background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 2rem;">${rejectedRequests.length}</div>
                        <div>Отклонены</div>
                    </div>
                </div>
                
                <div class="admin-tabs" style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <button class="admin-tab-btn btn btn-secondary active" onclick="switchAdminTab('pending')">⏳ Ожидающие (${pendingRequests.length})</button>
                    <button class="admin-tab-btn btn btn-secondary" onclick="switchAdminTab('approved')">✅ Подтвержденные (${approvedRequests.length})</button>
                    <button class="admin-tab-btn btn btn-secondary" onclick="switchAdminTab('rejected')">❌ Отклоненные (${rejectedRequests.length})</button>
                </div>
                
                <div id="adminPendingTab" class="admin-tab-content">
                    ${renderAdminRequests(pendingRequests, 'pending')}
                </div>
                
                <div id="adminApprovedTab" class="admin-tab-content" style="display: none;">
                    ${renderAdminRequests(approvedRequests, 'approved')}
                </div>
                
                <div id="adminRejectedTab" class="admin-tab-content" style="display: none;">
                    ${renderAdminRequests(rejectedRequests, 'rejected')}
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <button class="btn btn-primary" onclick="closeModal()">Закрыть админ-панель</button>
                    <button class="btn btn-info" onclick="exportSubscriptionsData()">📊 Экспорт данных</button>
                </div>
            </div>
        </div>
    `;
    
    const modal = document.createElement('div');
    modal.innerHTML = modalHTML;
    document.body.appendChild(modal);
}

function renderAdminRequests(requests, status) {
    if (requests.length === 0) {
        return '<p style="text-align: center; color: #666; padding: 20px;">Заявок нет</p>';
    }
    
    return `
        <div class="requests-list">
            ${requests.map(request => `
                <div class="request-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 10px 0;">${request.name}</h4>
                            <p style="margin: 5px 0;"><strong>Телефон:</strong> ${request.phone}</p>
                            <p style="margin: 5px 0;"><strong>План:</strong> ${request.plan} - ${request.price} руб.</p>
                            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                                Заявка от: ${new Date(request.date).toLocaleDateString('ru-RU')}
                            </p>
                            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                                ID: ${request.id}
                            </p>
                        </div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            ${status === 'pending' ? `
                                <button class="btn btn-success" onclick="approveSubscription('${request.adminId}')" style="padding: 8px 12px;">✅ Подтвердить</button>
                                <button class="btn btn-danger" onclick="rejectSubscription('${request.adminId}')" style="padding: 8px 12px;">❌ Отклонить</button>
                            ` : ''}
                            ${status === 'approved' ? '<span style="color: #28a745; font-weight: bold;">✅ Подтверждено</span>' : ''}
                            ${status === 'rejected' ? '<span style="color: #dc3545; font-weight: bold;">❌ Отклонено</span>' : ''}
                        </div>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

function switchAdminTab(tabName) {
    // Скрыть все вкладки
    document.querySelectorAll('.admin-tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Показать выбранную вкладку
    const activeTab = document.getElementById(`admin${tabName.charAt(0).toUpperCase() + tabName.slice(1)}Tab`);
    if (activeTab) {
        activeTab.style.display = 'block';
    }
    
    // Обновить активные кнопки
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

function approveSubscription(adminId) {
    const requestIndex = adminSubscriptions.findIndex(req => req.adminId === adminId);
    if (requestIndex !== -1) {
        adminSubscriptions[requestIndex].status = 'approved';
        
        // Обновляем также в основном массиве заявок
        const mainRequestIndex = subscriptionRequests.findIndex(req => req.adminId === adminId);
        if (mainRequestIndex !== -1) {
            subscriptionRequests[mainRequestIndex].status = 'approved';
        }
        
        // Активируем подписку для пользователя
        activateUserSubscription(adminId);
        
        saveAdminSubscriptions();
        saveSubscriptionRequests();
        
        showToast('✅ Подписка подтверждена!', 'success');
        showAdminPanel(); // Перезагружаем админ-панель
    }
}

function rejectSubscription(adminId) {
    const requestIndex = adminSubscriptions.findIndex(req => req.adminId === adminId);
    if (requestIndex !== -1) {
        adminSubscriptions[requestIndex].status = 'rejected';
        
        // Обновляем также в основном массиве заявок
        const mainRequestIndex = subscriptionRequests.findIndex(req => req.adminId === adminId);
        if (mainRequestIndex !== -1) {
            subscriptionRequests[mainRequestIndex].status = 'rejected';
        }
        
        saveAdminSubscriptions();
        saveSubscriptionRequests();
        
        showToast('❌ Подписка отклонена', 'error');
        showAdminPanel(); // Перезагружаем админ-панель
    }
}

function activateUserSubscription(adminId) {
    const request = adminSubscriptions.find(req => req.adminId === adminId);
    
    if (request) {
        // Активируем PRO подписку
        currentSubscription = {
            plan: 'pro',
            expires: new Date(Date.now() + subscriptionPlans.pro.duration * 24 * 60 * 60 * 1000).toISOString(),
            isActive: true
        };
        
        saveSubscription();
        updateUIForSubscription();
        
        console.log(`PRO подписка активирована для пользователя: ${request.name}`);
        showToast('🎉 PRO подписка активирована!', 'success');
    }
}

function exportSubscriptionsData() {
    try {
        const dataStr = JSON.stringify(adminSubscriptions, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = `aquasbor_subscriptions_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('📊 Данные экспортированы', 'success');
    } catch (error) {
        showToast('Ошибка при экспорте данных', 'error');
        console.error('Export error:', error);
    }
}

// ============================================
// ИНИЦИАЛИЗАЦИЯ АДМИН-ПАНЕЛИ
// ============================================

function addAdminButton() {
    // Проверяем, не добавлена ли уже кнопка
    if (document.getElementById('adminPanelBtn')) return;
    
    const adminBtn = document.createElement('button');
    adminBtn.id = 'adminPanelBtn';
    adminBtn.innerHTML = '👑';
    adminBtn.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        z-index: 10000;
        background: #ffd700;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: transform 0.2s ease;
    `;
    adminBtn.title = 'Админ-панель';
    
    adminBtn.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
    });
    
    adminBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
    
    adminBtn.addEventListener('click', showAdminPanel);
    adminBtn.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        exportSubscriptionsData();
    });
    
    document.body.appendChild(adminBtn);
}

// ============================================
// ОБНОВЛЕННАЯ ИНИЦИАЛИЗАЦИЯ ПРИЛОЖЕНИЯ
// ============================================

function initializeApp() {
    console.log('🚀 АкваСбор Pro инициализирован');

    // Инициализация системы подписок
    initializeSubscriptionSystem();

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('✅ Service Worker зарегистрирован');
                showToast('✅ Приложение готово к работе офлайн');
            })
            .catch(error => {
                console.log('❌ Ошибка Service Worker:', error);
            });
    }

    setupNavigation();
    setupMyAquarium();
    setupFishCompatibility();
    setupPlantCompatibility();
    loadAquariumData();
    setupPWAInstall();
    setupSubscriptionButton();
    
    // Добавляем кнопку админки
    setTimeout(() => {
        addAdminButton();
    }, 2000);
}

function initializeSubscriptionSystem() {
    const savedSubscription = localStorage.getItem('currentSubscription');
    
    if (savedSubscription) {
        currentSubscription = JSON.parse(savedSubscription);
        // Проверяем не истекла ли подписка
        currentSubscription.isActive = new Date(currentSubscription.expires) > new Date();
    } else {
        // Start trial period
        currentSubscription = {
            plan: 'trial',
            expires: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString(), // 14 days
            isActive: true
        };
        saveSubscription();
    }
    
    updateUIForSubscription();
}

function setupSubscriptionButton() {
    const upgradeBtn = document.getElementById('upgradeBtn');
    if (upgradeBtn) {
        upgradeBtn.addEventListener('click', showSubscriptionModal);
    }
}

// ============================================
// ДОБАВЛЯЕМ ПРОВЕРКИ ПОДПИСКИ В ФУНКЦИОНАЛ
// ============================================

// Обновляем функцию проверки совместимости рыб
function toggleFishSelection(fishKey, cardElement) {
    const selected = aquariumData.selectedFishForCompatibility || [];
    const index = selected.indexOf(fishKey);

    // Проверка ограничений подписки
    if (currentSubscription.plan === 'trial' && 
        index === -1 && // если добавляем новую рыбу
        selected.length >= subscriptionPlans.trial.limitations.maxFishCompatibility) {
        showToast(`⚠️ Пробная версия позволяет проверять совместимость только ${subscriptionPlans.trial.limitations.maxFishCompatibility} рыб. Апгрейдните до PRO!`);
        return;
    }

    if (index > -1) {
        // Убираем из выбранных
        selected.splice(index, 1);
        cardElement.classList.remove('selected');
    } else {
        // Добавляем в выбранные
        selected.push(fishKey);
        cardElement.classList.add('selected');
    }

    aquariumData.selectedFishForCompatibility = selected;
    saveAquariumData();

    // Обновляем результаты совместимости
    if (selected.length >= 2) {
        showCompatibilityResults(selected);
    } else {
        const resultsSection = document.getElementById('compatibilityResults');
        if (resultsSection) {
            resultsSection.style.display = 'none';
        }
    }
}

// Добавляем проверку подписки при доступе к расширенным функциям
function checkProAccess(featureName) {
    if (currentSubscription.plan !== 'pro') {
        showToast(`⚠️ ${featureName} доступна только в PRO версии. Апгрейдните для доступа!`);
        return false;
    }
    return true;
}

// ============================================
// ОБНОВЛЯЕМ СТИЛИ ДЛЯ АДМИН-ПАНЕЛИ
// ============================================

const adminStyles = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .admin-tab-btn.active {
        background: #159895 !important;
        color: white !important;
    }
    
    .request-card {
        transition: all 0.3s ease;
    }
    
    .request-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }
    
    .stat-card {
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: scale(1.05);
    }
    
    #toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10001;
        max-width: 300px;
    }
    
    .toast-item {
        background: #323232;
        color: white;
        padding: 12px 16px;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease;
    }
    
    .toast-item.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .toast-item.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .toast-item.info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
`;

// Добавляем стили в документ
const styleSheet = document.createElement('style');
styleSheet.textContent = adminStyles;
document.head.appendChild(styleSheet);

// ============================================
// УЛУЧШЕННАЯ ФУНКЦИЯ TOAST
// ============================================

function showToast(message, type = 'info') {
    // Создаем или находим контейнер для уведомлений
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10001;
            max-width: 300px;
        `;
        document.body.appendChild(toastContainer);
    }

    // Создаем уведомление
    const toast = document.createElement('div');
    toast.className = `toast-item ${type}`;
    toast.textContent = message;
    toastContainer.appendChild(toast);

    // Удаляем уведомление через 4 секунды
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

// ============================================
// ФУНКЦИЯ ЗАКРЫТИЯ МОДАЛЬНЫХ ОКОН
// ============================================

function closeModal() {
    const modals = document.querySelectorAll('.modal-overlay');
    modals.forEach(modal => modal.remove());
}

// Обновляем обработчик DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        document.getElementById('preloader').classList.add('hidden');
        initializeApp();
    }, 1500);
});
</script>

</body>
</html>
