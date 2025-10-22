<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактор визиток — Premium Cards Constructor</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/editor.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- CDN библиотеки -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <style>
        /* Дополнительные стили для правильного отображения canvas */
        .canvas-wrapper {
            display: inline-block;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            overflow: hidden;
        }

        .canvas-wrapper canvas {
            display: block !important;
        }

        /* Клипарты */
        .cliparts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
            gap: 12px;
        }

        .clipart-item {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            cursor: pointer;
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all 0.2s;
        }

        .clipart-item:hover {
            border-color: var(--primary);
            transform: scale(1.05);
        }

        .clipart-item svg {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body class="editor-page">

    <!-- Top Toolbar -->
    <div class="editor-toolbar">
        <div class="toolbar-section toolbar-left">
            <a href="index.html" class="toolbar-logo">
                <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                    <rect width="32" height="32" rx="6" fill="#6366F1"/>
                    <path d="M8 12h16M8 16h16M8 20h12" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>Premium Cards</span>
            </a>

            <div class="toolbar-divider"></div>

            <div class="toolbar-group">
                <button class="toolbar-btn" id="btn-undo" title="Отменить (Ctrl+Z)">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M7 7L3 11L7 15M3 11H13C15.2091 11 17 12.7909 17 15V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
                <button class="toolbar-btn" id="btn-redo" title="Повторить (Ctrl+Y)">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M13 7L17 11L13 15M17 11H7C4.79086 11 3 12.7909 3 15V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="toolbar-divider"></div>

            <div class="toolbar-group">
                <button class="toolbar-btn" id="btn-zoom-out" title="Уменьшить (-)">−</button>
                <span class="toolbar-text" id="zoom-level">100%</span>
                <button class="toolbar-btn" id="btn-zoom-in" title="Увеличить (+)">+</button>
                <button class="toolbar-btn" id="btn-zoom-fit" title="По размеру">⊡</button>
            </div>
        </div>

        <div class="toolbar-section toolbar-center">
            <input type="text" class="project-name-input" id="project-name" value="Новая визитка">
        </div>

        <div class="toolbar-section toolbar-right">
            <button class="toolbar-btn" id="btn-preview">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M10 6C6 6 3 10 3 10C3 10 6 14 10 14C14 14 17 10 17 10C17 10 14 6 10 6Z" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="10" cy="10" r="2" stroke="currentColor" stroke-width="1.5"/>
                </svg>
                <span>Предпросмотр</span>
            </button>
            <button class="toolbar-btn" id="btn-save" title="Сохранить (Ctrl+S)">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M5 3H13L17 7V15C17 15.5523 16.5523 16 16 16H5C4.44772 16 4 15.5523 4 15V4C4 3.44772 4.44772 3 5 3Z" stroke="currentColor" stroke-width="1.5"/>
                </svg>
                <span>Сохранить</span>
            </button>
            <button class="btn-primary" id="btn-export">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M17 13V15C17 15.5523 16.5523 16 16 16H4C3.44772 16 3 15.5523 3 15V13M10 3V12M10 3L7 6M10 3L13 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Экспорт
            </button>
        </div>
    </div>

    <!-- Main Editor Layout -->
    <div class="editor-layout">

        <!-- Left Sidebar -->
        <aside class="editor-sidebar editor-sidebar-left">
            <div class="sidebar-tabs">
                <button class="sidebar-tab active" data-panel="elements">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M4 7H20M12 7V17M9 17H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <span>Элементы</span>
                </button>
                <button class="sidebar-tab" data-panel="backgrounds">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <rect x="3" y="3" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    <span>Фоны</span>
                </button>
                <button class="sidebar-tab" data-panel="cliparts">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M10 3L12 8L17 8L13 11L15 16L10 13L5 16L7 11L3 8L8 8L10 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    </svg>
                    <span>Клипарты</span>
                </button>
            </div>

            <!-- Elements Panel -->
            <div class="sidebar-panel active" id="panel-elements">
                <div class="panel-header">
                    <h3 class="panel-title">Добавить элементы</h3>
                </div>
                <div class="panel-content">
                    <div class="elements-grid">
                        <button class="element-btn" id="add-text">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M4 7H20M12 7V17M9 17H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Текст</span>
                        </button>
                        <button class="element-btn" id="add-heading">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M6 5V19M18 5V19M6 12H18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Заголовок</span>
                        </button>
                        <button class="element-btn" id="add-logo">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M8 12L12 16L16 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Логотип</span>
                        </button>
                        <button class="element-btn" id="add-qr">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <rect x="4" y="4" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                                <rect x="13" y="4" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                                <rect x="4" y="13" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>QR-код</span>
                        </button>
                        <button class="element-btn" id="add-shape">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>Фигура</span>
                        </button>
                        <button class="element-btn" id="add-circle">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>Круг</span>
                        </button>
                        <button class="element-btn" id="add-line">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Линия</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Backgrounds Panel -->
            <div class="sidebar-panel" id="panel-backgrounds">
                <div class="panel-header">
                    <h3 class="panel-title">Фоны</h3>
                </div>
                <div class="panel-content">
                    <div class="bg-tabs">
                        <button class="bg-tab active" data-bg-type="color">Цвет</button>
                        <button class="bg-tab" data-bg-type="gradient">Градиент</button>
                        <button class="bg-tab" data-bg-type="image">Изображение</button>
                    </div>

                    <div class="bg-panel active" id="bg-color-panel">
                        <div class="color-presets">
                            <button class="color-preset" style="background: #FFFFFF; border: 1px solid #ddd;" data-color="#FFFFFF"></button>
                            <button class="color-preset" style="background: #000000;" data-color="#000000"></button>
                            <button class="color-preset" style="background: #1F2937;" data-color="#1F2937"></button>
                            <button class="color-preset" style="background: #6366F1;" data-color="#6366F1"></button>
                            <button class="color-preset" style="background: #8B5CF6;" data-color="#8B5CF6"></button>
                            <button class="color-preset" style="background: #EC4899;" data-color="#EC4899"></button>
                            <button class="color-preset" style="background: #EF4444;" data-color="#EF4444"></button>
                            <button class="color-preset" style="background: #F59E0B;" data-color="#F59E0B"></button>
                            <button class="color-preset" style="background: #10B981;" data-color="#10B981"></button>
                            <button class="color-preset" style="background: #3B82F6;" data-color="#3B82F6"></button>
                        </div>
                        <div class="color-picker-wrapper">
                            <label>Свой цвет:</label>
                            <input type="color" id="bg-color-picker" value="#FFFFFF">
                        </div>
                    </div>

                    <div class="bg-panel" id="bg-gradient-panel">
                        <div class="gradient-presets">
                            <button class="gradient-preset" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);" data-gradient="667eea,764ba2"></button>
                            <button class="gradient-preset" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);" data-gradient="f093fb,f5576c"></button>
                            <button class="gradient-preset" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);" data-gradient="4facfe,00f2fe"></button>
                            <button class="gradient-preset" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);" data-gradient="43e97b,38f9d7"></button>
                            <button class="gradient-preset" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);" data-gradient="fa709a,fee140"></button>
                            <button class="gradient-preset" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);" data-gradient="30cfd0,330867"></button>
                        </div>
                    </div>

                    <div class="bg-panel" id="bg-image-panel">
                        <button class="upload-btn" id="upload-background">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Загрузить изображение
                        </button>
                        <input type="file" id="bg-image-input" accept="image/*" style="display: none;">
                    </div>
                </div>
            </div>

            <!-- Cliparts Panel -->
            <div class="sidebar-panel" id="panel-cliparts">
                <div class="panel-header">
                    <h3 class="panel-title">Клипарты и соцсети</h3>
                </div>
                <div class="panel-content">
                    <div class="cliparts-grid" id="cliparts-grid">
                        <!-- Клипарты загружаются динамически -->
                    </div>
                </div>
            </div>
        </aside>

        <!-- Canvas Area -->
        <main class="editor-canvas-area">
            <!-- Side Switcher -->
            <div class="canvas-side-switcher">
                <button class="side-btn active" data-side="front">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <rect x="3" y="4" width="14" height="12" rx="1" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M6 8h8M6 11h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <span>Лицевая сторона</span>
                </button>
                <button class="side-btn" data-side="back">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <rect x="3" y="4" width="14" height="12" rx="1" stroke="currentColor" stroke-width="1.5"/>
                        <circle cx="10" cy="10" r="3" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    <span>Оборотная сторона</span>
                </button>
                <button class="side-btn-action" id="copy-style" title="Копировать стиль между сторонами">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
                        <path d="M8 3H5C4.44772 3 4 3.44772 4 4V7M8 17H5C4.44772 17 4 16.5523 4 16V13M12 3H15C15.5523 3 16 3.44772 16 4V7M12 17H15C15.5523 17 16 16.5523 16 16V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M10 7V13M7 10H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <!-- Canvas Container -->
            <div class="canvas-container">
                <div class="canvas-wrapper">
                    <canvas id="canvas-front"></canvas>
                    <canvas id="canvas-back" style="display: none;"></canvas>
                </div>

                <!-- Canvas Info -->
                <div class="canvas-info">
                    <span>90×50 мм</span>
                    <span class="canvas-info-divider">|</span>
                    <span>300 DPI</span>
                </div>
            </div>
        </main>

        <!-- Right Sidebar -->
        <aside class="editor-sidebar editor-sidebar-right">
            <div class="sidebar-tabs">
                <button class="sidebar-tab active" data-panel="properties">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <circle cx="10" cy="5" r="2" stroke="currentColor" stroke-width="1.5"/>
                        <circle cx="10" cy="10" r="2" stroke="currentColor" stroke-width="1.5"/>
                        <circle cx="10" cy="15" r="2" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    <span>Свойства</span>
                </button>
                <button class="sidebar-tab" data-panel="layers">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M3 7L10 3L17 7L10 11L3 7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        <path d="M3 10L10 14L17 10M3 13L10 17L17 13" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    </svg>
                    <span>Слои</span>
                </button>
            </div>

            <!-- Properties Panel -->
            <div class="sidebar-panel active" id="panel-properties">
                <div class="panel-header">
                    <h3 class="panel-title">Свойства объекта</h3>
                </div>
                <div class="panel-content" id="properties-content">
                    <div class="properties-empty">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <rect x="12" y="12" width="24" height="24" rx="2" stroke="currentColor" stroke-width="2" opacity="0.3"/>
                            <path d="M24 20v8M20 24h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <p>Выберите объект для редактирования</p>
                    </div>
                </div>
            </div>

            <!-- Layers Panel -->
            <div class="sidebar-panel" id="panel-layers">
                <div class="panel-header">
                    <h3 class="panel-title">Слои</h3>
                    <button class="panel-header-btn" id="delete-layer" title="Удалить слой">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                            <path d="M6 4V3C6 2.44772 6.44772 2 7 2H13C13.5523 2 14 2.44772 14 3V4M3 4H17M15 4V16C15 16.5523 14.5523 17 14 17H6C5.44772 17 5 16.5523 5 16V4H15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
                <div class="panel-content">
                    <div class="layers-list" id="layers-list">
                        <!-- Слои загружаются динамически -->
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Export Modal -->
    <div class="modal" id="export-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content modal-medium">
            <div class="modal-header">
                <h3 class="modal-title">Экспорт визитки</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="export-options">
                    <div class="export-option">
                        <div class="export-option-icon">
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <rect x="6" y="6" width="20" height="20" rx="2" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="export-option-content">
                            <h4>PNG (высокое качество)</h4>
                            <p>Идеально для печати, 300 DPI</p>
                        </div>
                        <button class="btn-primary" data-export="png">Скачать PNG</button>
                    </div>

                    <div class="export-option">
                        <div class="export-option-icon">
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <rect x="6" y="6" width="20" height="20" rx="2" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="export-option-content">
                            <h4>JPEG</h4>
                            <p>Компактный формат для интернета</p>
                        </div>
                        <button class="btn-primary" data-export="jpeg">Скачать JPEG</button>
                    </div>

                    <div class="export-option">
                        <div class="export-option-icon">
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <path d="M20 6H9C7.89543 6 7 6.89543 7 8V24C7 25.1046 7.89543 26 9 26H23C24.1046 26 25 25.1046 25 24V11L20 6Z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="export-option-content">
                            <h4>PDF для печати</h4>
                            <p>Готовый файл для типографии</p>
                        </div>
                        <button class="btn-primary" data-export="pdf">Скачать PDF</button>
                    </div>

                    <div class="export-option">
                        <div class="export-option-icon">
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                                <rect x="6" y="10" width="20" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="export-option-content">
                            <h4>ZIP архив (2 стороны)</h4>
                            <p>Лицевая и оборотная в одном файле</p>
                        </div>
                        <button class="btn-primary" data-export="zip">Скачать ZIP</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal" id="preview-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 class="modal-title">Реалистичный предпросмотр</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="preview-container">
                    <div class="preview-card" id="preview-card">
                        <div class="preview-card-front" id="preview-front"></div>
                        <div class="preview-card-back" id="preview-back"></div>
                    </div>
                </div>
                <div class="preview-controls">
                    <button class="btn-secondary" id="preview-flip">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M3 10h14M14 6l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        Перевернуть
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Modal -->
    <div class="modal" id="qr-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Создать QR-код</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Тип QR-кода:</label>
                    <select id="qr-type" class="form-control">
                        <option value="url">Веб-сайт (URL)</option>
                        <option value="tel">Телефон</option>
                        <option value="email">Email</option>
                        <option value="text">Текст</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Содержимое:</label>
                    <input type="text" id="qr-content" class="form-control" placeholder="Введите URL, телефон или текст">
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary modal-close">Отмена</button>
                    <button class="btn-primary" id="qr-generate">Создать QR-код</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div class="notifications" id="notifications"></div>

    <!-- ПОЛНЫЙ ВСТРОЕННЫЙ СКРИПТ РЕДАКТОРА -->
    <script src="js/premium-editor.js"></script>
</body>
</html>
